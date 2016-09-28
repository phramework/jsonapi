<?php
declare(strict_types=1);
/**
 * Copyright 2015-2016 Xenofon Spafaridis
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
namespace Phramework\JSONAPI\DataSource;

use Phramework\Database\Database;
use Phramework\Database\Operations\Create;
use Phramework\Database\Operations\Delete;
use Phramework\Database\Operations\Update;
use Phramework\Exceptions\NotImplementedException;
use Phramework\JSONAPI\Directive\Fields;
use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\Directive\FilterAttribute;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\FilterJSONAttribute;
use Phramework\JSONAPI\Directive\Sort;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\Relationship;
use Phramework\Operator\Operator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class DatabaseDataSource extends DataSource
{
    /**
     * DatabaseDataSource constructor.
     * @param ResourceModel $model
     */
    public function __construct(ResourceModel $model = null)
    {
        $this->resourceModel = $model;
    }

    /**
     * @param Directive[] $directives
     * @return array
     */
    public function get(
        Directive ...$directives
    ) : array {
        $this->requireTableSetting();

        foreach ($directives as $directive) {
            $directive->validate($this->resourceModel);
        }

        $query =
            'SELECT {{fields}}
            FROM {{table}}
              {{filter}}
              {{sort}}
              {{page}}';

        $query = $this->handleGet(
            $query,
            false,
            $directives
        );

        $records = Database::executeAndFetchAll(
            $query
        );

        array_walk($records, $this->resourceModel->prepareRecord);

        return $this->resourceModel->collection($records, $directives);
    }

    public function post(
        \stdClass $attributes,
        $return = Create::RETURN_ID
    ) {
        return Create::create(
            $attributes,
            $this->requireTableSetting(),
            $this->resourceModel->getVariable('schema', null),
            $return
        );
    }

    public function patch(string $id, \stdClass $attributes, $return = null)
    {
        //static::invalidateCache($id);

        return Update::update(
            $id,
            (array) $attributes,
            $this->requireTableSetting(),
            $this->resourceModel->getVariable('schema', null)
        );
    }

    public function delete(string $id, \stdClass $additionalAttributes = null)
    {
        //static::invalidateCache($id);

        return Delete::delete(
            $id,
            (
                $additionalAttributes !== null
                ? (array) $additionalAttributes
                : []
            ),
            $this->requireTableSetting(),
            $this->resourceModel->getVariable('schema', null)
        );
    }

    /**
     * @throws \LogicException If setting table is not set
     * @return string
     */
    public function requireTableSetting() : string
    {
        $table = $this->resourceModel->getVariable('table');

        if ($table === null) {
            throw new \LogicException(sprintf(
                'Setting "table" is required for internal resourceModel "%s"',
                get_class($this->resourceModel)
            ));
        }

        return $table;
    }

    public function handleGet(
        string $query,
        bool $queryHasWhere = false,
        array $directives
    ) {
        $model = $this->resourceModel;

        //Replace table is setting is set
        if (($table = $model->getVariable('table', null)) !== null) {
            $query = str_replace(
                '{{table}}',
                $table,
                $query
            );
        }
        //todo what about orphan {{table}} ?

        $definedPassed = new \stdClass();

        //todo create helper method
        foreach ($directives as $directive) {
            $definedPassed->{get_class($directive)} =
                $directive;
        }

        //merge with default
        $activeDirectives = (object) array_merge(
            (array) $model->getDefaultDirectives(),
            (array) $definedPassed
        );

        //to check with supported directives

        $page   = $activeDirectives->{Page::class}   ?? null;
        $fields = $activeDirectives->{Fields::class} ?? null;
        $filter = $activeDirectives->{Filter::class} ?? null;
        $sort   = $activeDirectives->{Sort::class}   ?? null;

        $query = trim(self::handleFields(
            self::handlePage(
                self::handleSort(
                    self::handleFilter(
                        $query,
                        $filter,
                        $queryHasWhere
                    ),
                    $sort
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
    private function handleSort(
        string $query,
        Sort $sort = null
    ) : string {
        $replace = '';

        if ($sort !== null) {
            $sort ->validate($this->resourceModel);

            $sortAttribute = $sort->getAttribute();

            /*$tableAttribute = (
                $sort->table === null
                ? $sort->attribute
                : $sort->table . '"."' .$sort->attribute
            );*/

            $replace = "\n" . sprintf(
                    'ORDER BY "%s" %s',
                    $sortAttribute,
                    ($sort->getAttribute() ? 'ASC' : 'DESC')
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
     * @param  Page      $page
     * @return string            Query
     * @uses Model::getDefaultPage if page is null
     */
    private function handlePage(
        string $query,
        $page = null
    ) : string {
        /**
         * string[]
         */
        $additionalQuery = [];

        if ($page !== null) {
            if ($page->getLimit() !== null) {
                $additionalQuery[] = sprintf(
                    'LIMIT %s',
                    $page->getLimit()
                );
            }

            if ($page->getOffset()) {
                $additionalQuery[] = sprintf(
                    'OFFSET %s',
                    $page->getOffset()
                );
            }
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
    private function handleFilterParseIn(array $array)
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
     * @todo check if query work both in MySQL and PostgreSQL
     */
    protected function handleFilter(
        string $query,
        $filter = null,
        $hasWhere = true
    ) : string {
        /**
         * string[]
         */
        $additionalQuery = [];

        if ($filter !== null) {
            $filter->validate($model = $this->resourceModel);

            if (count($filter->getPrimary())) {
                $additionalQuery[] = sprintf(
                    '%s "%s" IN (%s)',
                    ($hasWhere ? 'AND' : 'WHERE'),
                    $model->getIdAttribute(),
                    self::handleFilterParseIn($filter->getPrimary())
                );

                $hasWhere = true;
            }

            /**
             * object
             */
            $relationships = $model->getRelationships();

            //Apply filters for relationships
            foreach ($filter->getRelationships() as $relationshipKey => $relationshipFilterValue) {
                if (!$this->resourceModel->issetRelationship($relationshipKey)) {
                    throw new \Exception(sprintf(
                        'Relationship "%s" not found',
                        $relationshipKey
                    ));
                }

                $relationship = $relationships->{$relationshipKey};

                if ($relationship->type === Relationship::TYPE_TO_ONE) {
                    if ($relationshipFilterValue === Operator::EMPTY) {
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
            foreach ($filter->getAttributes() as $filterValue) {
                if (get_class($filterValue) != FilterAttribute::class) {
                    continue;
                }

                $attribute = $filterValue->getAttribute();
                $operator  = $filterValue->getOperator();
                $operand   = $filterValue->getOperand();

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
                        Operator::NOT_ISNULL => 'IS NOT NULL'
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
                        Operator::IN_ARRAY => '= ANY',
                        Operator::NOT_IN_ARRAY => '= ANY' // External not
                    ];

                    $additionalQuery[] = sprintf(//$operand ANY (array)
                        '%s %s (\'%s\' %s("%s")) ', //'%s %s (\'%s\' %s("%s"."%s")) ',
                        ($hasWhere ? 'AND' : 'WHERE'),
                        (
                        in_array($operator, [Operator::NOT_IN_ARRAY])
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
                        Operator::LIKE => 'LIKE',
                        Operator::NOT_LIKE => 'NOT LIKE'
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
                        Operator::NOT_IN => 'NOT IN'
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
            foreach ($filter->getAttributes() as $filterValue) {
                if (!($filterValue instanceof FilterJSONAttribute)) {
                    continue;
                }

                $attribute = $filterValue->getAttribute();
                $key = $filterValue->getKey();
                $operator = $filterValue->getOperator();
                $operand = $filterValue->getOperand();

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
     * @param Fields      $fields
     * @return string
     * @since 1.0.0
     * @todo add table prefix
     */
    private function handleFields(
        string $query,
        Fields $fields = null
    ) : string {
        $model = $this->resourceModel;

        $type = $model->getResourceType();

        if ($fields === null || empty($fields->get($type))) {
            //Use resource resourceModel's default fields
            //$fields = static::getDefaultFields();
            $fields = new Fields((object) [
                $type => ['*']
            ]);

            //todo apply column prefixes
            $attributes = array_unique(array_merge(
                $fields->get($type),
                [$this->resourceModel->getIdAttribute()]
            ));
        } else {
            $attributes = $fields->get($type);

            if ($fields->get($type) !== ['*']) {
                //Get field attributes for this type and force id attribute
                $attributes = array_unique(array_merge(
                    $fields->get($type),
                    [$this->resourceModel->getIdAttribute()]
                ));
            }
        }

        //if ($fields !== null && !empty($fields->get($type))) {
        $fields->validate($this->getResourceModel());

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
}
