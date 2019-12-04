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

use Phramework\Exceptions\NotImplementedException;
use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\FilterAttribute;
use Phramework\JSONAPI\FilterJSONAttribute;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Sort;
use Phramework\JSONAPI\Relationship;
use Phramework\Models\Operator;

/**
 * Implementation of directives
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class Directives extends \Phramework\JSONAPI\Model\Cache
{
    /**
     * Apply  page, filter, sort and fields directives to query.
     * This method will replace `{{table}}`, `{{sort}}`, `{{page}}`, `{{filter}}` and `{{fields}}` in query.
     * @uses static::handlePage
     * @uses static::handleSort
     * @uses static::handleFilter
     * @uses static::handleFields
     * @param  string       $query    Query string
     * @param  Page|null    $page     See handlePage $page parameter
     * @param  Filter|null  $filter   See handleFilter $filter parameter
     * @param  Sort|null    $sort     See handleSort $sort parameter
     * @param  Fields|null  $fields   See handleFields $fields parameter
     * @param  boolean      $hasWhere If query already has an WHERE, default is true
     * @return string       Query
     * @example
     * ```php
     * $query = static::handleGet(
     *     'SELECT {{fields}}
     *      FROM "{{table}}"
     *      WHERE
     *        "{{table}}"."status" <> \'DISABLED\'
     *        {{filter}}
     *        {{sort}}
     *        {{page}}',
     *     $page,
     *     $filter,
     *     $sort,
     *     $fields,
     *     $page,
     *     true //query contains WHERE directive
     * );
     * ```
     */
    protected static function handleGet(
        $query,
        Page $page = null,
        Filter $filter = null,
        Sort $sort = null,
        Fields $fields = null,
        $hasWhere = true
    ) {
        $query = str_replace('{{table}}', static::getTable(), $query);

        $query = trim(self::handleFields(
            self::handlePage(
                self::handleSort(
                    self::handleFilter(
                        $query,
                        $filter,
                        $hasWhere
                    ),
                    $sort ?? static::getSort()
                ),
                $page
            ),
            $fields
        ));

        return $query;
    }

    /**
     * This method will update `{{sort}}` string inside query parameter with
     * the provided sort directive
     * @param  string       $query    Query
     * @param  Sort|null    $sort
     * @return string       Query
     */
    private static function handleSort($query, $sort = null)
    {
        $replace = '';

        if ($sort !== null) {
            $sort ->validate(static::class);

            $tableAttribute = (
                $sort->table === null
                ? $sort->attribute
                : $sort->table . '"."' .$sort->attribute
            );

            $replace = "\n" . sprintf(
                'ORDER BY "%s" %s',
                $tableAttribute,
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
     * This method will update `{{page}}` string inside query parameter with
     * the provided pagination directive
     * @param  string    $query    Query
     * @param  Page|null $page
     * @return string            Query
     * @uses Model::getDefaultPage if page is null
     * @throws RequestException
     */
    private static function handlePage($query, $page = null)
    {
        /**
         * string[]
         */
        $additionalQuery = [];

        if ($page === null) {
            //Use resource model's default page
            $page = static::getDefaultPage();
        }

        if ($page->limit !== null) {
            if ($page->limit > static::getMaxPageLimit()) {
                throw new RequestException(
                    'Page object limit is greater than resource model`s maximum limit'
                );
            }

            $additionalQuery[] = sprintf(
                'LIMIT %s',
                $page->limit
            );
        }

        if ($page->offset) {
            $additionalQuery[] = sprintf(
                'OFFSET %s',
                $page->offset
            );
        }

        $query = str_replace(
            '{{page}}',
            implode("\n", $additionalQuery),
            $query
        );

        return $query;
    }

    /**
     * @param array $array
     * @return string
     */
    private static function handleFilterParseIn(array $array)
    {
        return implode(
            ',',
            array_map(
                /**
                 * Apply single quotes around key
                 */
                function ($key) {
                    return '\'' . $key . '\'';
                },
                $array
            )
        );
    }

    /**
     * This method will update `{{filter}}` string inside query parameter with
     * the provided filter directives
     * @param  string       $query    Query
     * @param  Filter|null  $filter
     * @param  bool         $hasWhere *[Optional]* If query already has an WHERE directive, default is true
     * @return string Query
     * @throws \Phramework\Exceptions\NotImplementedException
     * @throws \Exception
     * @todo check if query work both in MySQL and postgreSQL
     */
    protected static function handleFilter(
        $query,
        $filter = null,
        $hasWhere = true
    ) {
        /**
         * string[]
         */
        $additionalQuery = [];

        if ($filter !== null) {
            $filter->validate(static::class);

            if (count($filter->primary)) {
                $additionalQuery[] = sprintf(
                    '%s "%s" IN (%s)', //'%s "%s"."%s" IN (%s)',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    //static::$table,
                    static::$idAttribute,
                    self::handleFilterParseIn($filter->primary)
                );

                $hasWhere = true;
            }

            /**
             * object
             */
            $relationships = static::getRelationships();

            //Apply filters for relationships
            foreach ($filter->relationships as $relationshipKey => $relationshipFilterValue) {
                if (!static::relationshipExists($relationshipKey)) {
                    throw new \Exception(sprintf(
                        'Relationship "%s" not found',
                        $relationshipKey
                    ));
                }

                $relationship = $relationships->{$relationshipKey};

                if ($relationship->type === Relationship::TYPE_TO_ONE) {
                    if ($relationshipFilterValue === Operator::OPERATOR_EMPTY) {
                        $additionalQuery[] = sprintf(
                            '%s "%s" IS NULL', //'%s "%s"."%s" IS NULL',
                            ($hasWhere ? 'AND' : 'WHERE'),
                            //static::$table,
                            $relationship->recordDataAttribute
                        );
                    } else {
                        $additionalQuery[] = sprintf(
                            '%s "%s" IN (%s)', //'%s "%s"."%s" IN (%s)',
                            ($hasWhere ? 'AND' : 'WHERE'),
                            //static::$table,
                            $relationship->recordDataAttribute,
                            self::handleFilterParseIn($relationshipFilterValue)
                        );
                    }
                    $hasWhere = true;
                } else {
                    throw new NotImplementedException(
                        'Filtering by relationships type TYPE_TO_MANY are not implemented'
                    );
                }
            }

            //Apply filters for attributes (Not JSON Attributes)
            foreach ($filter->attributes as $filterValue) {
                if (get_class($filterValue) != FilterAttribute::class) {
                    continue;
                }

                $attribute = $filterValue->attribute;
                $operator = $filterValue->operator;
                $operand = $filterValue->operand
                    ? str_replace("'", "''", $filterValue->operand)
                    : $filterValue->operand;

                if (in_array($operator, Operator::getOrderableOperators())) {
                    $additionalQuery[] = sprintf(
                        '%s "%s" %s \'%s\'', //'%s "%s"."%s" %s \'%s\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        //static::$table,
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

                    $additionalQuery[] = sprintf(
                        '%s "%s" %s ', //'%s "%s"."%s" %s ',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        //static::$table,
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

                    $additionalQuery[] = sprintf(//$operand ANY (array)
                        '%s %s (\'%s\' %s("%s")) ', //'%s %s (\'%s\' %s("%s"."%s")) ',
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
                        //static::$table,
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
                    $additionalQuery[] = sprintf(
                        '%s LOWER("%s") %s \'%%%s%%\'', //'%s LOWER("%s"."%s") %s \'%%%s%%\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        //static::$table,
                        $attribute,
                        (
                        array_key_exists($operator, $transformation)
                            ? $transformation[$operator]
                            : $operator
                        ),
                        strtolower($operand)
                    );
                    $hasWhere = true;
                } elseif (in_array($operator, Operator::getInOperators())) {
                    //@todo add operator class in
                    //Define a transformation matrix, operator to SQL operator
                    $transformation = [
                        Operator::OPERATOR_NOT_IN => 'NOT IN'
                    ];

                    $additionalQuery[] = sprintf(
                        '%s "%s" %s (%s)',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        //static::$table,
                        $attribute,
                        (
                        array_key_exists($operator, $transformation)
                            ? $transformation[$operator]
                            : $operator
                        ),
                        self::handleFilterParseIn($operand)
                    );
                    $hasWhere = true;
                } else {
                    throw new NotImplementedException(sprintf(
                        'Filtering by operator "%s" is not implemented',
                        $operator
                    ));
                }

                $hasWhere = true;
            }

            //Apply filters only for JSON attributes
            foreach ($filter->attributes as $filterValue) {
                if (!($filterValue instanceof FilterJSONAttribute)) {
                    continue;
                }

                $attribute = $filterValue->attribute;
                $key = $filterValue->key;
                $operator = $filterValue->operator;
                $operand = $filterValue->operand;

                if (in_array($operator, Operator::getOrderableOperators())) {
                    $additionalQuery[] = sprintf(
                        '%s "%s"->>\'%s\' %s \'%s\'', //'%s "%s"."%s"->>\'%s\' %s \'%s\'',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        //static::$table,
                        $attribute,
                        $key,
                        $operator,
                        $operand
                    );
                    $hasWhere = true;
                } else {
                    throw new NotImplementedException(sprintf(
                        'Filtering JSON by operator "%s" is not implemented',
                        $operator
                    ));
                }
                $hasWhere = true;
            }
        }

        $query = str_replace(
            '{{filter}}',
            implode("\n", $additionalQuery),
            $query
        );

        return $query;
    }

    /**
     * This method will update `{{fields}}` string inside query parameter with
     * the provided fields directives
     * @param string      $query
     * @param Fields|null $fields
     * @return string
     * @since 1.0.0
     * @todo add table prefix
     * @uses Model::getDefaultFields if fields is null
     */
    private static function handleFields(
        $query,
        Fields $fields = null
    ) {
        $type = static::getType();

        if ($fields === null || empty($fields->get($type))) {
            //Use resource model's default fields
            $fields = static::getDefaultFields();

            $attributes =  array_merge(
                $fields->get($type),
                [static::getIdAttribute()] //[static::getTable() . '.' . static::getIdAttribute()]
            );
        } else {
            $attributes = $fields->get($type);

            if ($fields->get($type) !== ['*']) {
                //Get field attributes for this type and force id attribute
                $attributes = array_merge(
                    $fields->get($type),
                    [static::getIdAttribute()]
                );
            }
        }

        //if ($fields !== null && !empty($fields->get($type))) {
        $fields->validate(static::class);

        $attributes = array_unique($attributes);

        /**
         * @param string $column
         * @return string
         */
        $escape = function ($column) {
            if ($column === '*') {
                return $column;
            }

            return sprintf('"%s"', $column);
        };

        /**
         * This method will prepare the attributes by prefixing then with "
         * - * -> *
         * - table.* -> "table".*
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
        //}

        $query = str_replace(
            '{{fields}}',
            $queryPart,
            $query
        );

        return $query;
    }

    /**
     * Returns the an array of current resource's attribute names which are private
     * **MAY** be overwritten
     * @return string[]
     */
    public static function getPrivateAttributes()
    {
        return [];
    }
}
