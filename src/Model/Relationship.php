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

namespace Phramework\JSONAPI\Model;

use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Phramework\Phramework;

abstract class Relationship extends Get
{
    /**
     * Get resource's relationships
     * @return object Object with Relationship objects as values
     */
    public static function getRelationships()
    {
        return new \stdClass();
    }

    public static function getRelationship($relationshipKey)
    {
        $relationships = static::getRelationships();

        if (!isset($relationships->{$relationshipKey})) {
            throw new \Exception('Not a valid relationship key');
        }

        return $relationships->{$relationshipKey};
    }
    /**
     * Check if relationship exists
     * @param  string $relationshipKey Relationship's key (alias)
     * @return Boolean
     */
    public static function relationshipExists($relationshipKey)
    {
        $relationships = static::getRelationships();

        return isset($relationships->{$relationshipKey});
    }

    /**
     * Get records from a relationship link
     * @param string $relationshipKey
     * @param string $id
     * @param Fields|null $fields
     * @return RelationshipResource|RelationshipResource[]
     * @throws \Phramework\Exceptions\ServerException If relationship doesn't exist
     * @throws \Phramework\Exceptions\ServerException If relationship's class method is
     * not defined
     */
    public static function getRelationshipData(
        $relationshipKey,
        $id,
        Fields $fields = null,
        $primaryDataParameters = [],
        $relationshipParameters = []
    ) {
        if (!static::relationshipExists($relationshipKey)) {
            throw new \Phramework\Exceptions\ServerException(sprintf(
                '"%s" is not a valid relationship key',
                $relationshipKey
            ));
        }

        $relationship = static::getRelationship($relationshipKey);

        switch ($relationship->type) {
            case \Phramework\JSONAPI\Relationship::TYPE_TO_ONE:
                $resource = $callMethod = static::getById(
                    $id,
                    $fields,
                    ...$primaryDataParameters
                );

                if (!$resource) {
                    return null;
                }

                //And use it's relationships data for this relationship
                return (
                    isset($resource->relationships->{$relationshipKey}->data)
                    ? $resource->relationships->{$relationshipKey}->data
                    : null
                );
            case \Phramework\JSONAPI\Relationship::TYPE_TO_MANY:
            default:
                if (!isset($relationship->callbacks->{Phramework::METHOD_GET})) {
                    return [];
                }

                $callMethod = $relationship->callbacks->{Phramework::METHOD_GET};

                if (!is_callable($callMethod)) {
                    throw new \Phramework\Exceptions\ServerException(
                        $callMethod[0] . '::' . $callMethod[1]
                        . ' is not implemented'
                    );
                }

                //also we could attempt to use getById like the above TO_ONE
                //to use relationships data

                return call_user_func(
                    $callMethod,
                    $id,
                    $fields,
                    ...$relationshipParameters
                );
        }
    }

    /**
     * Get jsonapi's included object, selected by include argument,
     * using id's of relationship's data from resources in primary data object
     * @param Resource|Resource[]  $primaryData Primary data resource or resources
     * @param string[]             $include     An array with the keys of relationships to include
     * @param Fields|null $fields
     * @param array $additionalResourceParameters *[Optional]*
     * @return Resource[]           An array with all included related data
     * @throws \Phramework\Exceptions\RequestException When a relationship is not found
     * @throws \Phramework\Exceptions\ServerException
     * @todo handle Relationship resource cannot be accessed
     * @todo include second level relationships
     * @example
     * ```php
     * Relationship::getIncludedData(
     *     Article::get(),
     *     ['tag', 'author']
     * );
     * ```
     */
    public static function getIncludedData(
        $primaryData,
        $include = [],
        Fields $fields = null,
        $additionalResourceParameters = []
    ) {
        /**
         * Store relationshipKeys as key and ids of their related data as value
         * @example
         * ```php
         * (object) [
         *     'author'  => [1],
         *     'comment' => [1, 2, 3, 4]
         * ]
         * ```
         */
        $tempRelationshipIds = new \stdClass();

        //check if relationship exists
        foreach ($include as $relationshipKey) {
            if (!static::relationshipExists($relationshipKey)) {
                throw new RequestException(sprintf(
                    'Relationship "%s" not found',
                    $relationshipKey
                ));
            }

            //Will hold ids of related data
            $tempRelationshipIds->{$relationshipKey} = [];
        }

        if (empty($include) || empty($primaryData)) {
            return [];
        }

        //iterate all primary data

        //if a single resource convert it to array
        //so it can be iterated in the same way
        if (!is_array($primaryData)) {
            $primaryData = [$primaryData];
        }

        foreach ($primaryData as $resource) {
            //Ignore resource if it's relationships are not set or empty
            if (empty($resource->relationships)) {
                continue;
            }

            foreach ($include as $relationshipKey) {
                //ignore if requested relationship is not set
                if (!isset($resource->relationships->{$relationshipKey})) {
                    continue;
                }

                //ignore if requested relationship data are not set
                if (!isset($resource->relationships->{$relationshipKey}->data)) {
                    continue;
                }

                $relationshipData = $resource->relationships->{$relationshipKey}->data;

                if (!$relationshipData || empty($relationshipData)) {
                    continue;
                }

                //if single relationship resource convert it to array
                //so it can be iterated in the same way
                if (!is_array($relationshipData)) {
                    $relationshipData = [$relationshipData];
                }

                //Push relationship id for this requested relationship
                foreach ($relationshipData as $primaryKeyAndType) {
                    //push primary key (use type? $primaryKeyAndType->type)
                    $tempRelationshipIds->{$relationshipKey}[] = $primaryKeyAndType->id;
                }
            }
        }

        $included = [];

        foreach ($include as $relationshipKey) {
            $relationship = static::getRelationship($relationshipKey);

            $relationshipModelClass = $relationship->modelClass;

            $ids = array_unique($tempRelationshipIds->{$relationshipKey});

            $additionalArgument = (
                isset($additionalResourceParameters[$relationshipKey])
                ? $additionalResourceParameters[$relationshipKey]
                : []
            );

            $resources = $relationshipModelClass::getById(
                $ids,
                $fields,
                ...$additionalArgument
            );

            foreach ($resources as $key => $resource) {
                if ($resource === null) {
                    continue;
                }

                $included[] = $resource;
            }
        }

        return $included;
    }
}
