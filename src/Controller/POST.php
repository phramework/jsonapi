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

use Phramework\Exceptions\ForbiddenException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\JSONAPI\Controller\POST\QueueItem;
use Phramework\JSONAPI\ValidationModel;
use Phramework\Models\Request;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\ServerException;
use Phramework\JSONAPI\Relationship;
use Phramework\Phramework;
use Phramework\Util\Util;
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
     * @param  string $modelClass                      Resource's primary model
     * @param  array $primaryDataParameters           *[Optional]* Array with any
     * additional arguments that the primary data is requiring
     * @param  array $relationshipParameters *[Optional]* Array with any
     * additional argument primary data's relationships are requiring
     * @param  callable[] $validationCallbacks
     * @param  callable|null $viewCallback
     * @param  int|null      $bulkLimit Null is no limit.
     * @todo handle as transaction queue, Since models usually are not producing exceptions.
     * Prepare data until last possible moment,
     * so that any exceptions can be thrown, and finally invoke the execution of the queue.
     * @uses $modelClass::post method to create resources
     * @return int[]
     * @throws RequestException
     * @throws IncorrectParametersException
     * @throws ForbiddenException
     * @throws ServerException when view callback is not callable
     * @example ```php
     * self::handlePOST(
     *     $params,
     *     $method,
     *     $headers,
     *     Message::class,
     *     [$user->id],
     *     [],
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
    protected static function handlePOST(
        $params,
        $method,
        $headers,
        $modelClass,
        $primaryDataParameters = [],
        $relationshipParameters = [],
        $validationCallbacks = [],
        $viewCallback = null,
        $bulkLimit = null
    ) {
        if ($viewCallback !== null && !is_callable($viewCallback)) {
            throw new ServerException('View callback is not callable!');
        }

        $queue = new \SplQueue();

        $data = static::getRequestData($params);

        //Treat single requests as an array of resources
        if (is_object($data) || is_array($data) && Util::isArrayAssoc($data)) {
            $data = [$data];
        }

        if ($bulkLimit !== null && count($data) > $bulkLimit) {
            throw new RequestException('Number of batch requests is exceeding the maximum of ' . $bulkLimit);
        }

        //Iterate multiple resources
        foreach ($data as $resource) {
            if (is_array($resource)) {
                $resource = (object) $resource;
            }

            Request::requireParameters($resource, 'type');
            if ($resource->type != $modelClass::getType()) {
                throw new IncorrectParametersException(
                    ['type'],
                    sprintf(
                        'Incorrect type "%s"',
                        $resource->type
                    )
                );
            }

            //Throw a Forbidden exception if resource's id is set.     *
            //Unsupported request to create a resource with a client-generated ID
            if (isset($resource->id)) {
                throw new ForbiddenException(
                    'Unsupported request to create a resource with a client-generated ID'
                );
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
         * @var string[]
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
                        $id,
                        $resourceId,
                        null //$additionalAttributes
                    );
                }
            }

            unset($queueItem);

            $ids[] = $id;
        }


        if ($viewCallback !== null) {
            return call_user_func(
                $viewCallback,
                $ids
            );
        }

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
        $validationModel = $modelClass::getValidationModel();

        $attributesValidator = (
            isset($validationModel->attributes) && $validationModel->attributes
            ? $validationModel->attributes
            : new ObjectValidator()
        );


        //Parse request attributes using $validationModel to validate the data
        $attributes = $attributesValidator->parse($requestAttributes);

        if (empty((array) $attributes)) {
            $attributes = new \stdClass();
        }

        $parsedRelationshipAttributes = self::getParsedRelationshipAttributes(
            $modelClass,
            $attributes,
            $requestRelationships,
            $relationshipParameters,
            $validationModel
        );

        //Call Validation callbacks
        foreach ($validationCallbacks as $callback) {
            call_user_func(
                $callback,
                $requestAttributes,
                $requestRelationships,
                $attributes,
                $parsedRelationshipAttributes
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

                if (!isset($relationship->callbacks->{Phramework::METHOD_POST})) {
                    throw new ServerException(sprintf(
                       'POST callback is not implemented for relationship "%s"',
                        $relationshipKey
                    ));
                }

                //Push to queueRelationships
                $queueRelationships->{$relationshipKey} = (object) [
                    'callback' => $relationship->callbacks->{Phramework::METHOD_POST}, //callable
                    'resources' => $parsedRelationshipValue //array
                ];
            }
        }

        return new \Phramework\JSONAPI\Controller\POST\QueueItem(
            $attributes,
            $queueRelationships
        );
    }

    /**
     * @param string $modelClass
     * @param object $attributes
     * @param object $requestRelationships
     * @throws RequestException
     * @throws \Exception
     * @throws \Phramework\Exceptions\NotFoundException
     * @return object
     */
    protected static function getParsedRelationshipAttributes(
        $modelClass,
        &$attributes,
        $requestRelationships,
        $relationshipParameters = [],
        ValidationModel $validationModel
    ) {
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
        $parsedRelationshipAttributes = (
            isset($validationModel->relationships)
            ? $validationModel->relationships->parse(
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

            $currentRelationshipParameters = (
                isset($relationshipParameters[$relationshipKey])
                ? $relationshipParameters[$relationshipKey]
                : []
            );

            //Check if relationship resources exists
            foreach ($tempIds as $tempId) {
                self::exists(
                    $relationshipModelClass::getById(
                        $tempId,
                        null, //fields
                        ...$currentRelationshipParameters
                    ),
                    sprintf(
                        'Resource of type "%s" and id "%d" is not found',
                        $relationshipModelClass::getType(),
                        $tempId
                    )
                );
            }

            if ($relationship->type == Relationship::TYPE_TO_ONE) {
                //TODO investigate
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

        return (
            $parsedRelationshipAttributes
            ? $parsedRelationshipAttributes
            : new \stdClass()
        );
    }
}
