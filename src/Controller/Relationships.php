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

use Phramework\Exceptions\IncorrectParametersException;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Relationship;
use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;
use \Phramework\Models\Filter;
use Phramework\Validate\EnumValidator;
use \Phramework\Validate\Validate;

/**
 * Relationships related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class Relationships extends \Phramework\JSONAPI\Controller\Base
{
    /**
     * Handle handleByIdRelationships requests
     * @param object $parameters                          Request parameters
     * @param string $method                          Request method
     * @param array  $headers                         Request headers
     * @param integer|string $id                      Resource's id
     * @param string $relationship                    Requested relationship
     * key
     * @param string $modelClass                      Resource's primary model
     * to be used
     * @param string[] $allowedMethods                 Allowed methods
     * @param array  $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @param array  $relationshipParameters [Optional] Array with any
     * additional arguments primary data's relationships are requiring
     * @uses model's `getById` method to fetch primary data resource
     * @return boolean
     * @throws IncorrectParametersException When request method is not allowed
     */
    protected static function handleByIdRelationships(
        $parameters,
        $method,
        $headers,
        $id,
        $relationship,
        $modelClass,
        $allowedMethods,
        $primaryDataParameters = [],
        $relationshipParameters = []
    ) {
        $relationship = Filter::string($relationship);

        //Check if relationship exists
        static::exists(
            $modelClass::relationshipExists($relationship),
            sprintf(
                'Relationship "$relationship" not found',
                $relationship
            )
        );

        $fields = Fields::parseFromParameters($parameters, $modelClass);

        $object = $modelClass::getById(
            $id,
            $fields, //$fields
            ...$primaryDataParameters
        );

        //Check if object exists
        static::exists($object);

        //Check if requested method is allowed
        (new EnumValidator($allowedMethods))->parse($method);

        //Fetch relationship data
        $data = $modelClass::getRelationshipData(
            $relationship,
            $id,
            $fields,
            $primaryDataParameters,
            (
                isset($relationshipParameters[$relationship])
                ? $relationshipParameters[$relationship]
                : []
            )
        );

        //Add links
        $links = [
            'self'    =>
                $modelClass::getSelfLink($id) . '/relationships/' . $relationship,
            'related' =>
                $modelClass::getSelfLink($id) . '/' . $relationship
        ];

        return static::viewData($data, $links);
    }

    /**
     * Handle handleByIdRelationships requests
     * @param object $parameters                          Request parameters
     * @param string $method                          Request method
     * @param array  $headers                         Request headers
     * @param integer|string $id                      Resource's id
     * @param string $relationship                    Requested relationship
     * key
     * @param string $modelClass                      Resource's primary model
     * to be used
     * @param string[] $allowedMethods                 Allowed methods
     * @param array  $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @param array  $relationshipParameters [Optional] Array with any
     * additional arguments primary data's relationships are requiring
     * @uses model's `getById` method to fetch primary data resource
     * @return boolean
     * @throws IncorrectParametersException When request method is not allowed
     * @todo remove duplicates with handleByIdRelationships
     * @todo only get is supported
     */
    protected static function handleByIdRelationshipsRelated(
        $parameters,
        $method,
        $headers,
        $id,
        $relationship,
        $modelClass,
        $allowedMethods,
        $primaryDataParameters = [],
        $relationshipParameters = []
    ) {
        $relationship = Filter::string($relationship);

        //Check if relationship exists
        static::exists(
            $modelClass::relationshipExists($relationship),
            sprintf(
                'Relationship "$relationship" not found',
                $relationship
            )
        );

        $fields = Fields::parseFromParameters($parameters, $modelClass);

        $object = $modelClass::getById(
            $id,
            $fields, //$fields
            ...$primaryDataParameters
        );

        //Check if object exists
        static::exists($object);

        //Check if requested method is allowed
        (new EnumValidator($allowedMethods))->parse($method);

        //Fetch relationship data
        $data = $modelClass::getRelationshipData(
            $relationship,
            $id,
            $fields,
            $primaryDataParameters,
            (
            isset($relationshipParameters[$relationship])
                ? $relationshipParameters[$relationship]
                : []
            )
        );

        //Add links
        $links = [
            'self'    =>
                $modelClass::getSelfLink($id) . '/relationships/' . $relationship,
            'related' =>
                $modelClass::getSelfLink($id) . '/' . $relationship
        ];

        $ids = [];

        //Gather ids form relationship
        foreach ($data as $resource) {
            $ids[] = $resource->id;
        }

        $relationshipObject = $modelClass::getRelationship($relationship);

        $relationshipModelClass = $relationshipObject->modelClass;

        $collection = $relationshipModelClass::getById($ids, $fields);

        //Add links
        $links = [
            'self' => $modelClass::getSelfLink($id) . '/relationships/' . $relationship
        ];

        if ($relationshipObject->type == Relationship::TYPE_TO_ONE) {
            return static::viewData($collection, $links);
        } else {
            return static::viewData(array_values((array)$collection), $links);
        }

        return static::viewData($data, $links);
    }
}
