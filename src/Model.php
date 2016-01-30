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
namespace Phramework\JSONAPI;

use Phramework\JSONAPI\Model\Get;
use \Phramework\Phramework;
use \Phramework\Models\Operator;
use \Phramework\JSONAPI\Relationship;
use Phramework\Validate\ObjectValidator;

/**
 * Base JSONAPI Model
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class Model extends \Phramework\JSONAPI\Model\Relationship
{

    /**
     * Enable caching of resources
     * @var bool
     */
    protected static $caching = true;

    /**
     * Get resource's validation model
     * @return ValidationModel
     */
    public static function getValidationModel()
    {
        return new ValidationModel(
            new ObjectValidator(
                (object) [],
                [],
                false //No additional properties
            )
        );
    }

    /**
     * Get validation model used for filters, if set for a property, this
     * will override the validation model defined in getValidationModel for this
     * property
     * @return \Phramework\Validate\ObjectValidator
     */
    public static function getFilterValidationModel()
    {
        return new ObjectValidator(
            (object) [],
            [],
            false //No additional properties
        );
    }

    /**
     * Prepare a collection of resources
     * @param  array[]|\object[] $records Multiple records fetched from database
     * @param  boolean $links
     * [Optional] Write resource and relationship links, defaults is false
     * @return Resource[]
     */
    public static function collection($records = [], $links = true)
    {
        if (!$records) {
            return [];
        }

        $collection = [];

        foreach ($records as $record) {
            //Convert this record to resource object
            $resource = static::resource($record, $links);

            //Attach links.self to this resource
            if ($resource) {
                if ($links) {
                    //Include links object
                    $resource->links = [
                        'self' => static::getSelfLink($resource->id)
                    ];
                }

                //Push to collection
                $collection[] = $resource;
            }

        }

        return $collection;
    }

    /**
     * Prepare an individual resource
     * @param  array|\stdClass $record An individual record fetched from database
     * @param  boolean $links
     * [Optional] Write resource and relationship links, default is false
     * @return Resource|null
     * @throws \Exception
     * @todo add additional arguments to disabled by default fetching of relationship data
     */
    public static function resource($record, $links = true)
    {
        if (!$record) {
            return null;
        }

        if (!is_object($record) && is_array($record)) {
            $record = (object)$record;
        }

        $idAttribute = static::getIdAttribute();

        if (!isset($record->{$idAttribute})) {
            throw new \Exception(sprintf(
                'Attribute "%s" is not set for record',
                $idAttribute
            ));
        }

        //Initialize resource
        $resource = new Resource(
            static::getType(),
            (string)$record->{$idAttribute}
        );

        //Delete $idAttribute from attributes
        unset($record->{$idAttribute});

        //Attach relationships if resource's relationships are set
        if (($relationships = static::getRelationships())) {
            //Initialize relationships object
            //$resource->relationships = new \stdClass();

            //Parse relationships
            foreach ($relationships as $relationship => $relationshipObject) {
                //Initialize an new relationship entry object
                $relationshipEntry = new \stdClass();

                if ($links) {
                    //Set relationship links
                    $relationshipEntry->links = [
                        'self' => static::getSelfLink(
                            $resource->id . '/relationships/' . $relationship
                        ),
                        'related' => static::getSelfLink(
                            $resource->id . '/' . $relationship
                        )
                    ];
                }

                $attribute = $relationshipObject->getAttribute();
                $relationshipType = $relationshipObject->getRelationshipType();
                $type = $relationshipObject->getResourceType();

                if (isset($record->{$attribute}) && $record->{$attribute}) {
                    //If relationship data exists in record's attributes use them

                    //In case of TYPE_TO_ONE attach single object to data
                    if ($relationshipType == Relationship::TYPE_TO_ONE) {
                        $relationshipEntry->data = (object)[
                            'id' => (string)$record->{$attribute},
                            'type' => $type
                        ];

                    //In case of TYPE_TO_MANY attach an array of objects
                    } elseif ($relationshipType == Relationship::TYPE_TO_MANY) {
                        $relationshipEntry->data = [];

                        foreach ($record->{$attribute} as $k => $d) {
                            if (!is_array($d)) {
                                $d = [$d];
                            }

                            foreach ($d as $dd) {
                                //Push object
                                $relationshipEntry->data[] = (object)[
                                    'id' => (string)$dd,
                                    'type' => $type
                                ];
                            }
                        }
                    }
                } else {
                    //Else try to use relationship`s class method to retrieve data
                    if ($relationshipType == Relationship::TYPE_TO_MANY) {
                        $callMethod = [
                            $relationshipObject->getRelationshipClass(),
                            self::GET_RELATIONSHIP_BY_PREFIX . ucfirst($resource->type)
                        ];
                        //Check if method exists
                        if (is_callable($callMethod)) {
                            $relationshipEntry->data = [];

                            $relationshipEntryData = call_user_func(
                                $callMethod,
                                $resource->id
                            );

                            foreach ($relationshipEntryData as $k => $d) {
                                //Push object
                                $relationshipEntry->data[] = (object)[
                                    'id' => (string)$d,
                                    'type' => $type
                                ];
                            }
                        }
                    }
                }

                //Unset this attribute (MUST not be visible in resource's attributes)
                unset($record->{$attribute});

                //Push relationship to relationships
                $resource->relationships->{$relationship} = $relationshipEntry;
            }
        }

        //Attach resource attributes
        $resource->attributes = (object)$record;

        //Return final resource object
        return $resource;
    }

    /**
     * Create a record in database
     * @param  array $attributes
     * @param  \Phramework\Database\Operations\Create::RETURN_ID $return Return type,
     * default is RETURN_ID
     * @return mixed
     * @todo disable post ?
     */
    public static function post(
        $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    ) {
        return \Phramework\Database\Operations\Create::create(
            $attributes,
            static::getTable(),
            static::getSchema(),
            $return
        );
    }

    /**
     * Update selected attributes of a database record
     * @param  mixed $id id attribute's value
     * @param  object $attributes Key-value array with fields to update
     * @return number of updated rows
     * @todo add query limit
     */
    public static function patch($id, $attributes)
    {
        static::invalidateCache($id);

        return \Phramework\Database\Operations\Update::update(
            $id,
            (array)$attributes,
            static::getTable(),
            static::getIdAttribute()
        );
    }

    /**
     * Delete a database record
     * @param  mixed $id id attribute's value
     * @param  object $additionalAttributes Object with additinal fields
     * to use in WHERE clause
     * @return boolean Returns true on success, false on failure
     */
    public static function delete($id, $additionalAttributes = null)
    {
        static::invalidateCache($id);

        return \Phramework\Database\Operations\Delete::delete(
            $id,
            (
                $additionalAttributes !== null
                ? (array)$additionalAttributes
                : []
            ),
            static::getTable(),
            static::getIdAttribute()
        );
    }

    /**
     * Get filterable attribute keys
     * @return string[]
     */
    public static function getFilterable()
    {
        return [];
    }

    /**
     * Get attribute keys that can be updated using PATCH
     * @return string[]
     */
    public static function getMutable()
    {
        return [];
    }

    /**
     * Get sort attributes and default
     * @return object Returns an object with attribute `attributes` containing
     * an string[] with allowed sort attributes
     * and attribute `default` a string|null having the value of default, boolean `ascending`
     * sorting attribute
     */
    public static function getSort()
    {
        return (object)[
            'attributes' => [],
            'default' => null,
            'ascending' => true
        ];
    }

    /**
     * This method will update `{{sort}}` string inside query parameter with
     * the provided sort
     * @param  string       $query    Query
     * @param  Sort|null    $sort     string `table`, string `attribute`, boolean `ascending`
     * @return string       Query
     */
    protected static function handleSort($query, $sort)
    {
        $replace = '';

        if ($sort) {
            $replace = "\n" . sprintf(
                'ORDER BY "%s"."%s" %s',
                $sort->table,
                $sort->attribute,
                ($sort->ascending ? 'ASC' : 'DESC')
            );
        }

        $query = str_replace(
            '{{sort}}',
            $replace,
            $query
        );

        return $query;
    }

    /**
     * This method will update `{{pagination}}` string inside query parameter with
     * the provided pagination directives
     * @param  string    $query    Query
     * @param  Page|null $page
     * @return string            Query
     */
    protected static function handlePagination($query, $page = null)
    {
        $additionalPagination = [];

        if ($page !== null) {
            if (isset($page->limit)) {
                $additionalPagination[] = sprintf(
                    'LIMIT %s',
                    $page->limit
                );
            }

            if (isset($page->offset)) {
                $additionalPagination[] = sprintf(
                    'OFFSET %s',
                    $page->offset
                );
            }
        }

        $query = str_replace(
            '{{pagination}}',
            implode("\n", $additionalPagination),
            $query
        );

        return $query;
    }

    /**
     * This method will update `{{filter}}` string inside query parameter with
     * the provided filter directives
     * @param  string  $query    Query
     * @param  Filter|null  $filter   This object has 3 attributes:
     * primary, relationships and attributes
     * - integer[] $primary
     * - integer[] $relationships
     * - array $attributes (each array item [$attribute, $operator, $operant])
     * - array $attributesJSON (each array item [$attribute, $key, $operator, $operant])
     * @param  boolean $hasWhere If query already has an WHERE, default is true
     * @return string            Query
     * @throws \Phramework\Exceptions\NotImplementedException
     * @todo check if query work both in MySQL and postgre
     */
    protected static function handleFilter(
        $query,
        $filter = null,
        $hasWhere = true
    ) {
        $additionalFilter = [];

        if ($filter && $filter->primary) {
            $additionalFilter[] = sprintf(
                '%s "%s"."%s" IN (%s)',
                ($hasWhere ? 'AND' : 'WHERE'),
                static::$table,
                static::$idAttribute,
                implode(',', $filter->primary)
            );

            $hasWhere = true;
        }

        $relationships = static::getRelationships();

        if ($filter) {
            foreach ($filter->relationships as $key => $value) {
                if (!static::relationshipExists($key)) {
                    throw new \Exception(sprintf(
                        'Relationship "%s" not found',
                        $key
                    ));
                }
                $relationship = $relationships->{$key};
                $relationshipClass = $relationship->getRelationshipClass();

                if ($relationship->getRelationshipType() === Relationship::TYPE_TO_ONE) {
                    $additionalFilter[] = sprintf(
                        '%s "%s"."%s" IN (%s)',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        static::$table, //$relationshipclass::getTable(),
                        $relationship->getAttribute(),
                        implode(',', $value)
                    );
                    $hasWhere = true;
                } else {
                    throw new \Phramework\Exceptions\NotImplementedException(
                        'Filtering by TYPE_TO_MANY relationships are not implemented'
                    );
                }
            }

            foreach ($filter->attributes as $value) {
                list($key, $operator, $operand) = $value;
                if (in_array($operator, Operator::getOrderableOperators())) {
                    $additionalFilter[] = sprintf(
                        '%s "%s"."%s" %s \'%s\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        static::$table,
                        $key,
                        $operator,
                        $operand
                    );
                } elseif (in_array($operator, Operator::getNullableOperators())) {
                    //Define a transformation matrix, operator to SQL operator
                    $transformation = [
                        Operator::OPERATOR_NOT_ISNULL => 'IS NOT NULL'
                    ];

                    $additionalFilter[] = sprintf(
                        '%s "%s"."%s" %s \'%s\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        static::$table,
                        $key,
                        (
                            array_key_exists($operator, $transformation)
                            ? $transformation[$operator]
                            : $operator
                        )
                    );
                } elseif (in_array($operator, Operator::getLikeOperators())) {
                    //Define a transformation matrix, operator to SQL operator
                    $transformation = [
                        Operator::OPERATOR_LIKE => 'LIKE',
                        Operator::OPERATOR_NOT_LIKE => 'NOT LIKE'
                    ];

                    //LIKE '%text%', force lower - case insensitive
                    $additionalFilter[] = sprintf(
                        '%s LOWER("%s"."%s") %s \'%%%s%%\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        static::$table,
                        $key,
                        (
                            array_key_exists($operator, $transformation)
                            ? $transformation[$operator]
                            : $operator
                        ),
                        strtolower($operand)
                    );
                } else {
                    throw new \Phramework\Exceptions\NotImplementedException(sprintf(
                        'Filtering by operator "%s" is not implemented',
                        $operator
                    ));
                }

                $hasWhere = true;
            }

            $filterJSON = $filter->JSONAttributes;
            //hack.

            foreach ($filterJSON as $value) {
                list($attribute, $key, $operator, $operand) = $value;

                if (in_array($operator, Operator::getOrderableOperators())) {
                    $additionalFilter[] = sprintf(
                        '%s "%s"."%s"->>\'%s\' %s \'%s\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        static::$table,
                        $attribute,
                        $key,
                        $operator,
                        $operand
                    );
                } else {
                    throw new \Phramework\Exceptions\NotImplementedException(sprintf(
                        'Filtering JSON by operator "%s" is not implemented',
                        $operator
                    ));
                }
                $hasWhere = true;
            }
        }

        $query = str_replace(
            '{{filter}}',
            implode("\n", $additionalFilter),
            $query
        );

        return $query;
    }

    /**
     * Apply handle pagination, sort and filter to query,
     * will replace `{{sort}}`, `{{pagination}}` and `{{filter}}` strings in
     * query.
     * @uses Model::handlePagination
     * @uses Model::handleSort
     * @uses Model::handleFilter
     * @param  string       $query    Query
     * @param  Page|null    $page     See handlePagination $page parameter
     * @param  Filter|null  $filter   See handleFilter $filter parameter
     * @param  Sort|null    $sort See handleSort $sort parameter
     * @param  boolean $hasWhere If query already has an WHERE, default is true
     * @return string       Query
     */
    protected static function handleGet(
        $query,
        Page $page,
        Filter $filter,
        Sort $sort,
        $hasWhere = true
    ) {
        return trim(static::handlePagination(
            static::handleSort(
                static::handleFilter(
                    $query,
                    $filter,
                    $hasWhere
                ),
                $sort
            ),
            $page
        ));
    }
}
