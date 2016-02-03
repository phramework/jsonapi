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
            ) //Attributes validator
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
     * @param  array[]|object[] $records Multiple records fetched from database
     * [Optional] Write resource and relationship links, defaults is false
     * @return Resource[]
     * @uses Resource::parseFromRecords
     * @todo Add example
     */
    public static function collection($records = [], Fields $fields = null, $flags = Resource::PARSE_DEFAULT)
    {
        return Resource::parseFromRecords(
            $records,
            static::class,
            $fields,
            $flags
        );
    }

    /**
     * Prepare an individual resource
     * @param  array|object $record A single record fetched from database
     * @return Resource|null
     * @throws \Exception
     * @uses Resource::parseFromRecord
     * @todo Add example
     */
    public static function resource($record, Fields $fields = null, $flags = Resource::PARSE_DEFAULT)
    {
        return Resource::parseFromRecord(
            $record,
            static::class,
            $fields,
            $flags
        );
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
     * **MAY** be overwritten
     * @return array
     */
    public static function getFilterable()
    {
        return [];
    }

    /**
     * Get attribute keys that can be updated using PATCH
     * **MAY** be overwritten
     * @return string[]
     */
    public static function getMutable()
    {
        return [];
    }

    /**
     * Get attribute keys that allowed to be used for sort
     * **MAY** be overwritten
     * @return string[]
     * @since 1.0.0
     */
    public static function getSortable()
    {
        return [];
    }

    /**
     * Get attribute keys that allowed to be used for sort
     * **MAY** be overwritten
     * @return string[]
     * @since 1.0.0
     */
    public static function getFields()
    {
        return [];
    }

    /**
     * Get sort attributes and default
     * **MAY** be overwritten
     * @return object Returns an object with attribute `attributes` containing
     * an string[] with allowed sort attributes
     * and attribute `default` a string|null having the value of default, boolean `ascending`
     * sorting attribute
     */
    public static function getSort()
    {
        return new Sort(
            static::getTable()
        );
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
     * @param  Filter|null  $filter
     * @param  bool $hasWhere *[Optional]* If query already has an WHERE, default is true
     * @return string            Query
     * @throws \Phramework\Exceptions\NotImplementedException
     * @throws \Exception
     * @todo check if query work both in MySQL and postgre
     */
    protected static function handleFilter(
        $query,
        $filter = null,
        $hasWhere = true
    ) {
        $additionalFilter = [];

        if ($filter === null) {
            return $query;
        }

        if ($filter->primary) {
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

        foreach ($filter->relationships as $relationshipKey => $relationshipFilterValue) {
            if (!static::relationshipExists($relationshipKey)) {
                throw new \Exception(sprintf(
                    'Relationship "%s" not found',
                    $relationshipKey
                ));
            }

            $relationship = $relationships->{$relationshipKey};
            $relationshipModelClass = $relationship->modelClass;

            if ($relationship->type === Relationship::TYPE_TO_ONE) {
                $additionalFilter[] = sprintf(
                    '%s "%s"."%s" IN (%s)',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    static::$table,
                    $relationship->recordDataAttribute,
                    implode(',', $relationshipFilterValue)
                );
                $hasWhere = true;
            } else {
                throw new \Phramework\Exceptions\NotImplementedException(
                    'Filtering by TYPE_TO_MANY relationships are not implemented'
                );
            }
        }

        foreach ($filter->attributes as $filterValue) {
            if (!($filterValue instanceof FilterAttribute)) {
                continue;
            }

            $attribute  = $filterValue->attribute;
            $operator   = $filterValue->operator;
            $operand    = $filterValue->operand;

            if (in_array($operator, Operator::getOrderableOperators())) {
                $additionalFilter[] = sprintf(
                    '%s "%s"."%s" %s \'%s\'',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    static::$table,
                    $attribute,
                    $operator,
                    $operand
                );
                $hasWhere = true;
            } elseif (in_array($operator, Operator::getNullableOperators())) {
                //Define a transformation matrix, operator to SQL operator
                $transformation = [
                    Operator::OPERATOR_NOT_ISNULL => 'IS NOT NULL'
                ];

                $additionalFilter[] = sprintf(
                    '%s "%s"."%s" %s ',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    static::$table,
                    $attribute,
                    (
                        array_key_exists($operator, $transformation)
                        ? $transformation[$operator]
                        : $operator
                    )
                );
                $hasWhere = true;
            } elseif (in_array($operator, Operator::getInArrayOperators())) {
                //Define a transformation matrix, operator to SQL operator
                $transformation = [
                    Operator::OPERATOR_IN_ARRAY => '= ANY',
                    Operator::OPERATOR_NOT_IN_ARRAY => '= ANY' // External not
                ];

                $additionalFilter[] = sprintf( //$operand ANY (array)
                    '%s %s (\'%s\' %s("%s"."%s")) ',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    (
                        in_array($operator, [Operator::OPERATOR_NOT_IN_ARRAY])
                        ? 'NOT'
                        : ''
                    ),
                    $operand,
                    (
                        array_key_exists($operator, $transformation)
                        ? $transformation[$operator]
                        : $operator
                    ),
                    static::$table,
                    $attribute
                );
                $hasWhere = true;
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
                    $attribute,
                    (
                        array_key_exists($operator, $transformation)
                        ? $transformation[$operator]
                        : $operator
                    ),
                    strtolower($operand)
                );
                $hasWhere = true;
            } else {
                throw new \Phramework\Exceptions\NotImplementedException(sprintf(
                    'Filtering by operator "%s" is not implemented',
                    $operator
                ));
            }

            $hasWhere = true;
        }

        //Only JSON
        foreach ($filter->attributes as $relationshipFilterValue) {
            if (!($filterValue instanceof FilterJSONAttribute)) {
                continue;
            }

            if (in_array($operator, Operator::getOrderableOperators())) {
                $additionalFilter[] = sprintf(
                    '%s "%s"."%s"->>\'%s\' %s \'%s\'',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    static::$table,
                    $filterValue->attribute,
                    $filterValue->key,
                    $filterValue->operator,
                    $filterValue->operand
                );
                $hasWhere = true;
            } else {
                throw new \Phramework\Exceptions\NotImplementedException(sprintf(
                    'Filtering JSON by operator "%s" is not implemented',
                    $operator
                ));
            }
            $hasWhere = true;
        }

        $query = str_replace(
            '{{filter}}',
            implode("\n", $additionalFilter),
            $query
        );

        return $query;
    }

    /**
     * This method will update `{{fields}}` string inside query parameter with
     * the provided fields directives
     * @param string $query
     * @param Fields $fields
     * @return string
     * @since 1.0.0
     */
    protected static function handleFields(
        $query,
        Fields $fields
    ) {
        $type = static::getType();

        //Get field attributes for this type and force id attribute
        $attributes = array_unique(array_merge(
            $fields->get($type),
            [static::$idAttribute]
        ));

        $escape = function ($input) {
            if ($input === '*') {
                return $input;
            }
            return sprintf('"%s"', $input);
        };

        /**
         * This method will prepare the attributes by prefixing then with "
         * - * ->
         * - id -> "id"
         * - table.id -> "table"."id"
         * and glue them with comma separator
         */
        $queryPart = implode(
            ',',
            array_map(
                function ($attribute) use ($escape) {
                    return implode(
                        '.',
                        array_map($escape, explode('.', $attribute))
                    );
                },
                $attributes
            )
        );

        $query = str_replace(
            '{{fields}}',
            $queryPart,
            $query
        );

        return $query;
    }

    /**
     * Apply handle pagination, sort and filter to query,
     * will replace `{{sort}}`, `{{pagination}}`, `{{filter}}` and `{{fields}}` strings in
     * query.
     * @uses static::handlePagination
     * @uses static::handleSort
     * @uses static::handleFilter
     * @uses static::handleFields
     * @param  string       $query    Query
     * @param  Page|null    $page     See handlePagination $page parameter
     * @param  Filter|null  $filter   See handleFilter $filter parameter
     * @param  Sort|null    $sort     See handleSort $sort parameter
     * @param  Fields|null  $fields   See handleFields $fields parameter
     * @param  boolean $hasWhere If query already has an WHERE, default is true
     * @return string       Query
     */
    protected static function handleGet(
        $query,
        Page $page,
        Filter $filter,
        Sort $sort,
        Fields $fields,
        $hasWhere = true
    ) {
        return trim(static::handleFields(
            static::handlePagination(
                static::handleSort(
                    static::handleFilter(
                        $query,
                        $filter,
                        $hasWhere
                    ),
                    $sort
                ),
                $page
            ),
            $fields
        ));
    }
}
