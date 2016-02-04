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

use Phramework\JSONAPI\Controller\POST\QueueItem;
use Phramework\JSONAPI\Util;
use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;
use \Phramework\JSONAPI\Relationship;
use Phramework\Validate\ObjectValidator;

/**
 * POST related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class POST extends \Phramework\JSONAPI\Controller\GET
{
    /**
     * @param  object $params                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  \Phramework\JSONAPI\Model $modelClass   Resource's primary model
     * @param  array $primaryDataParameters           *[Optional]* Array with any
     * additional arguments that the primary data is requiring
     * @param  array $relationshipParameters *[Optional]* Array with any
     * additional argument primary data's relationships are requiring
     * @param  callable[] $validationCallbacks
     * @todo handle as transaction queue, Since models usually are not producing exceptions.
     * Prepare data until last possible moment,
     * so that any exceptions can be thrown, and finally invoke the execution of the queue.
     * @uses $modelClass::post method to create resources
     * @return int[]
     * @todo validate type
     */
    protected static function handlePOST(
        $params,
        $method,
        $headers,
        $modelClass,
        $primaryDataParameters = [],
        $relationshipParameters = [],
        $validationCallbacks = []
    ) {
        $queue = new \SplQueue();

        $data = static::getRequestData($params);

        //Treat single requests as an array of resources
        if (is_object($data) || is_array($data) && Util::isArrayAssoc($data)) {
            $data = [$data];
        }

        foreach ($data as $resource) {
            if (is_array($resource)) {
                $resource = (object)$resource;
            }

            $requestAttributes = (
                isset($resource->attributes) && $resource->attributes
                ? $resource->attributes
                : new \stdClass()
            );

            if (property_exists($resource, 'relationships')) {
                $requestRelationships = $resource->relationships;
            } else {
                $requestRelationships = new \stdClass();
            }

            //Prepare queue item
            $queueItem = self::handlePOSTResource(
                $params,
                $method,
                $headers,
                $modelClass,
                $primaryDataParameters, //unused ?
                $relationshipParameters,
                $requestAttributes,
                $validationCallbacks,
                $requestRelationships
            );

            $queue->push($queueItem);
        }

        /**
         * @var int[]
         */
        $ids = [];

        //process queue
        while (!$queue->isEmpty()) {
            $queueItem = $queue->pop();

            //POST item's attributes
            $id = $modelClass::post((array)$queueItem->getAttributes());

            //Just to be sure
            self::testUnknownError($id);

            //POST item's relationships
            $relationships = $queueItem->getRelationships();

            foreach ($relationships as $key => $relationship) {
                //Call post relationship method to post each of relationships pairs
                foreach ($relationship->resources as $resourceId) {
                    call_user_func(
                        $relationship->callback,
                        $resourceId,
                        $id
                    );
                }
            }

            unset($queueItem);

            $ids[] = $id;
        }

        return $ids;

        if (count($ids) === 1) {
            //Prepare response with 201 Created status code
            \Phramework\Models\Response::created(
                $modelClass::getSelfLink($ids[0])
            );
        }

        \Phramework\JSONAPI\Viewers\JSONAPI::header();

        //Will overwrite 201 with 204
        \Phramework\Models\Response::noContent();

        return $ids;
    }

    /**
     * Helper method
     * @param object $params
     * @param $method
     * @param $headers
     * @param string $modelClass
     * @param object $primaryDataParameters
     * @param object $relationshipParameters
     * @param object $requestAttributes
     * @param $validationCallbacks
     * @param $requestRelationships
     * @return POST\QueueItem
     * @throws RequestException
     * @throws \Exception
     * @throws \Phramework\Exceptions\NotFoundException
     * @throws \Phramework\Exceptions\ServerException
     * @todo validate request and resource's type
     */
    private static function handlePOSTResource(
        $params,
        $method,
        $headers,
        $modelClass,
        $primaryDataParameters,
        $relationshipParameters,
        $requestAttributes,
        $validationCallbacks,
        $requestRelationships
    ) {
        $validator = $modelClass::getValidationModel();

        $attributesValidator = (
            isset($validator->attributes) && $validator->attributes
            ? $validator->attributes
            : new ObjectValidator()
        );


        //Parse request attributes using $validationModel to validate the data
        $attributes = $attributesValidator->parse($requestAttributes);

        $relationships = $modelClass::getRelationships();

        /**
         * Format, object with
         * - relationshipKey1 -> id1
         * - relationshipKey2 -> [id1, id2]
         */
        $relationshipAttributes = new \stdClass();

        /**
         * Foreach request relationship
         * - check if relationship exists
         * - if TYPE_TO_ONE check if data is object with type and id
         * - if TYPE_TO_MANY check if data is an array of objects with type and id
         * - check if types are correct
         * - copy ids to $relationshipAttributes object
         */
        foreach ($requestRelationships as $relationshipKey => $relationshipValue) {
            //Work with objects
            if (is_array($relationshipValue)) {
                $relationshipValue = (object) $relationshipValue;
            }

            if (!isset($relationshipValue->data)) {
                throw new RequestException(sprintf(
                    'Relationship "%s" must have a member data defined',
                    $relationshipKey
                ));
            }

            $relationshipData = $relationshipValue->data;

            if (is_array($relationshipData) && Util::isArrayAssoc($relationshipData)) {
                $relationshipData = (object) $relationshipData;
            }

            //Check if relationship exists
            static::exists(
                $modelClass::relationshipExists($relationshipKey),
                sprintf(
                    'Relationship "%s" not found',
                    $relationshipKey
                )
            );

            $relationship = $modelClass::getRelationship($relationshipKey);

            $relationshipModelClass   = $relationship->modelClass;
            $relationshipResourceType = $relationshipModelClass::getType();

            if ($relationship->type == Relationship::TYPE_TO_ONE) {
                $value = $relationshipData;

                if (!is_object($value)) {
                    throw new RequestException(sprintf(
                        'Expected data to be an object for relationship "%s"',
                        $relationshipKey
                    ));
                }

                if (!isset($value->id) || !isset($value->type)) {
                    throw new RequestException(sprintf(
                        'Attributes "id" and "type" required for relationship "%s"',
                        $relationshipKey
                    ));
                }

                if ($value->type !== $relationshipResourceType) {
                    throw new RequestException(sprintf(
                        'Invalid resource type "%s" for relationship "%s"',
                        $value->type,
                        $relationshipKey
                    ));
                }
                //Push relationship attributes for this $relationshipKey
                $relationshipAttributes->{$relationshipKey} = $value->id;
            } elseif ($relationship->type == Relationship::TYPE_TO_MANY) {
                $parsedValues = [];

                if (!is_array($relationshipData)) {
                    throw new RequestException(sprintf(
                        'Expected data to be an array for relationship "%s"',
                        $relationshipKey
                    ));
                }

                foreach ($relationshipData as $value) {
                    if (is_array($value) && Util::isArrayAssoc($value)) {
                        $value = (object) $value;
                    }

                    if (!is_object($value)) {
                        throw new RequestException(sprintf(
                            'Expected data properties to be object for relationship "%s"',
                            $relationshipKey
                        ));
                    }

                    if (!isset($value->id) || !isset($value->type)) {
                        throw new RequestException(sprintf(
                            'Attributes "id" and "type" required for relationship "%s"',
                            $relationshipKey
                        ));
                    }

                    if ($value->type !== $relationshipResourceType) {
                        throw new RequestException(sprintf(
                            'Invalid resource type "%s" for relationship "%s"',
                            $relationshipResourceType,
                            $relationshipKey
                        ));
                    }

                    $parsedValues[] = $value->id;
                }

                //Push relationship attributes for this $relationshipKey
                $relationshipAttributes->{$relationshipKey} = $parsedValues;
            } else {
                throw new \Exception('Unknown relationship type');
            }
        }

        //Parse attributes using relationship's validation model
        $parsedRelationshipAttributes =
        (
            isset($validator->relationships)
            ? $validator->relationships->parse(
                $relationshipAttributes
            )
            : new \stdClass()
        );

        /**
         * Foreach request relationship
         * Check if requested relationships resources exist
         * Copy TYPE_TO_ONE attributes to primary data's attributes
         */
        foreach ($requestRelationships as $relationshipKey => $relationshipValue) {
            $relationship = $modelClass::getRelationship($relationshipKey);

            if (!isset($parsedRelationshipAttributes->{$relationshipKey})) {
                continue;
            }

            $parsedRelationshipValue = $parsedRelationshipAttributes->{$relationshipKey};

            $tempIds = (
                is_array($parsedRelationshipValue)
                ? $parsedRelationshipValue
                : [$parsedRelationshipValue]
            );

            $relationship = $modelClass::getRelationship($relationshipKey);
            $relationshipModelClass = $relationship->modelClass;

            $relationshipCallMethod = [
                $relationshipModelClass,
                'getById'
            ];

            //Check if relationship resources exists
            foreach ($tempIds as $tempId) {
                self::exists(
                    call_user_func_array(
                        $relationshipCallMethod,
                        array_merge(
                            [$tempId],
                            (
                                isset($relationshipParameters[$relationshipKey])
                                ? $relationshipParameters[$relationshipKey]
                                : []
                            )
                        )
                    ),
                    sprintf(
                        'Resource of type "%s" and id "%d" is not found',
                        $relationshipModelClass::getType(),
                        $tempId
                    )
                );
            }

            if ($relationship->type == Relationship::TYPE_TO_ONE) {
                /*if ($parsedRelationshipValue) {
                    //check if exists
                    self::exists(
                        call_user_func_array(
                            $relationshipCallMethod,
                            array_merge(
                                [$value->id],
                                (
                                    isset($additionalRelationshipsArguments[$relationshipKey])
                                    ? $additionalRelationshipsArguments[$relationshipKey]
                                    : []
                                )
                            )
                        ),
                        sprintf(
                            'Resource of type "%s" and id "%d" is not found',
                            $value->type,
                            $value->id
                        )
                    );
                }*/

                //Copy to primary attributes
                $attributes->{$relationship->recordDataAttribute} = $parsedRelationshipValue;
            }
        }

        //Call Validation callbacks
        foreach ($validationCallbacks as $callback) {
            call_user_func(
                $callback,
                $requestAttributes,
                $requestRelationships,
                $attributes
            );
        }

        $queueRelationships = new \stdClass();

        /**
         * Call POST_RELATIONSHIP_BY_PREFIX handler for TO_MANY relationships
         * This handler should post into database these relationships
         */
        foreach ($requestRelationships as $relationshipKey => $relationshipValue) {
            $relationship = $modelClass::getRelationship($relationshipKey);

            if ($relationship->type == Relationship::TYPE_TO_MANY) {
                $parsedRelationshipValue = $parsedRelationshipAttributes->{$relationshipKey};

                $relationship = $modelClass::getRelationship($relationshipKey);
                $relationshipModelClass = $relationship->modelClass;

                $relationshipCallMethod = [
                    $relationshipModelClass,
                    $relationshipModelClass::POST_RELATIONSHIP_BY_PREFIX
                    . ucfirst($modelClass::getType())
                ];

                if (!is_callable($relationshipCallMethod)) {
                    throw new \Phramework\Exceptions\ServerException(
                        $relationshipCallMethod[0]
                        . '::'
                        . $relationshipCallMethod[1]
                        . ' is not implemented'
                    );
                }

                //Push to queueRelationships
                $queueRelationships->{$relationshipKey} = (object) [
                    'callback' => $relationshipCallMethod, //callable
                    'resources' => $parsedRelationshipValue //array
                ];

                //Call post relationship method to post each of relationships pairs
                //foreach ($parsedRelationshipValue as $tempId) {
                //    call_user_func(
                //        $relationshipCallMethod,
                //        $tempId,
                //        $id
                //    );
                //}
            }
        }

        return new \Phramework\JSONAPI\Controller\POST\QueueItem(
            $attributes,
            $queueRelationships
        );
    }
}
