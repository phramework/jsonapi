<?php
/**
 * Copyright 2015 - 2016 Xenofon Spafaridis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Phramework\JSONAPI\Controller;

use Phramework\Exceptions\ServerException;
use Phramework\JSONAPI\Relationship;
use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;
use Phramework\Phramework;
use Phramework\Validate\ObjectValidator;

/**
 * Patch related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class PATCH extends \Phramework\JSONAPI\Controller\POST
{
    /**
     * @param  object $parameters                      Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  integer|string $id                      Requested resource's id
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @param  callable[] $validationCallbacks         Signature:
     * ```function ($id,
     * $requestAttributes,
     * $requestRelationships,
     * $attributes,
     * $parsedRelationshipAttributes)```
     * @param  callable|null $viewCallback
     * @throws \Phramework\Exceptions\NotFound         If resource not found
     * @throws \Phramework\Exceptions\RequestException If no fields are changed
     * @uses model's `getById` method to fetch resource
     * @uses $modelClass::patch method to update resources
     * @todo allow nulls
     * @throws \Exception When Validation model for an attribute  is not set
     * @return boolean
     * @example ```php
     * self::handlePATCH(
     *     $params,
     *     $method,
     *     $headers,
     *     $id,
     *     Message::class,
     *     [$user->id],
     *     [
     *         function (
     *             $id,
     *             $requestAttributes,
     *             $requestRelationships,
     *             $attributes,
     *             $parsedRelationshipAttributes
     *         ) {
     *             if ($requestAttributes->state != Message::STATE_NEW) {
     *                 throw new RequestException('Cannot be changed');
     *             }
     *         }
     *     ]
     * );
     * ```
     */
    protected static function handlePATCH(
        $parameters,
        $method,
        $headers,
        $id,
        $modelClass,
        $primaryDataParameters = [],
        $validationCallbacks = [],
        $viewCallback = null
    ) {
        Request::requireParameters($parameters, ['data']);
        Request::requireParameters($parameters->data, ['id', 'type']);

        if ($parameters->data->type !== $modelClass::getType()) {
            throw new RequestException(sprintf(
                'Expecting type "%s" got "%s"',
                $modelClass::getType(),
                $parameters->data->type
            ));
        }

        if ($parameters->data->id != $id) {
            throw new RequestException(sprintf(
                'Expecting id "%s" got "%s"',
                $id,
                $parameters->data->id
            ));
        }

        $validationModel = $modelClass::getValidationModel();

        if (($patchValidationModel = $modelClass::getPatchValidationModel()) !== null) {
            $validationModel = $patchValidationModel;
            $validator       = $patchValidationModel->attributes;
        } else {

            //Construct a validator
            $validator = new ObjectValidator(
                (object)[],
                [],
                false
            );

            $attributeValidator = $modelClass::getValidationModel()->attributes;

            if ($attributeValidator === null) {
                //TODO ???
            }

            foreach ($modelClass::getMutable() as $mutable) {
                if (!isset($attributeValidator->properties->{$mutable})) {
                    throw new \Exception(sprintf(
                        'Validation model for attribute "%s" is not set!',
                        $mutable
                    ));
                }

                //Push property to validator
                $validator->addProperty(
                    $mutable,
                    $attributeValidator->properties->{$mutable}
                );
            }
        }


        if (($requestData = self::getRequestData($parameters)) !== null
            && property_exists($requestData, 'attributes')) {
            $requestAttributes = $requestData->attributes;
        } else {
            $requestAttributes = new \stdClass();
        }

        if (($requestData = self::getRequestData($parameters)) !== null
            && property_exists($requestData, 'relationships')) {
            $requestRelationships = $requestData->relationships;
        } else {
            $requestRelationships = new \stdClass();
        }

        if (count((array) $requestAttributes) === 0 && count((array) $requestRelationships) === 0) {
            throw new RequestException('No fields updated');
        }

        $attributes = $validator->parse($requestAttributes);

        if (empty((array) $attributes)) {
            $attributes = new \stdClass();
        }

        //Parse relationship attributes, NOTE type TO_ONE relationships are writing their data back to $attributes object
        $parsedRelationshipAttributes = self::getParsedRelationshipAttributes(
            $modelClass,
            $attributes,
            $requestRelationships,
            $validationModel,
            []
        );

        //Check if callbacks for TO_MANY relationships are set
        foreach ($parsedRelationshipAttributes as $relationshipKey => $relationshipValues) {
            $relationship = $modelClass::getRelationship($relationshipKey);

            if ($relationship->type == Relationship::TYPE_TO_MANY) {
                if (!isset($relationship->callbacks->{Phramework::METHOD_PATCH})) {
                    throw new ServerException(sprintf(
                        'PATCH callback is not implemented for relationship "%s"',
                        $relationshipKey
                    ));
                }
            }
        }

        //TODO allow nulls
        foreach ($attributes as $key => $attribute) {
            if ($attribute === null) {
                unset($attributes->{$key});
            }
        }

        //Fetch data, to check if resource exists
        $currentResource = $modelClass::getById(
            $id,
            null, //fields todo maybe add []
            ...$primaryDataParameters
        );

        //Check if resource exists (MUST exist!)
        static::exists($currentResource);

        //Call validation callbacks if set
        foreach ($validationCallbacks as $callback) {
            call_user_func(
                $callback,
                $id,
                $requestAttributes,
                $requestRelationships,
                $attributes,
                $parsedRelationshipAttributes
            );
        }

        //Update the resource's attributes directly if any of then is requested to PATCH
        if (count((array) $attributes) > 0) {
            $patch = $modelClass::patch($id, (array) $attributes); //todo remove array
            self::testUnknownError($patch, 'PATCH operation was not successful');
        }

        //Call TO_MANY callbacks to PATCH relationships
        foreach ($parsedRelationshipAttributes as $relationshipKey => $relationshipValues) {
            $relationship = $modelClass::getRelationship($relationshipKey);

            if ($relationship->type == Relationship::TYPE_TO_MANY) {
                call_user_func(
                    $relationship->callbacks->{Phramework::METHOD_PATCH},
                    $relationshipValues, //complete replacement
                    $id,
                    null //$additionalAttributes
                );
            }
        }

        if ($viewCallback !== null) {
            return call_user_func(
                $viewCallback,
                $id
            );
        }

        static::viewData(
            $modelClass::resource(['id' => $id]),
            ['self' => $modelClass::getSelfLink($id)]
        );

        return true;
    }
}
