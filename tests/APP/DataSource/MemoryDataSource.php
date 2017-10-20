<?php
declare(strict_types=1);
/*
 * Copyright 2015-2017 Xenofon Spafaridis
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
namespace Phramework\JsonApi\APP\DataSource;

use Phramework\JsonApi\DataSource\AbstractDataSource;
use Phramework\JsonApi\Directive\AbstractDirective;

/**
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @since 4.0.0
 */
class MemoryDataSource extends AbstractDataSource
{
    protected static $database = [];

    public static function addTable(string $table)
    {
        if (array_key_exists($table, static::$database)) {
            throw new \LogicException('Table name exists');
        }

        static::$database[$table] = [];
    }

    public static function insert(string $table, \stdClass $record)
    {
        static::$database[$table][] = $record;
    }

    public static function select(string $table) : array
    {
        return static::$database[$table];
    }

    public function __construct(ResourceModel $resourceModel = null)
    {
        $this->resourceModel = $resourceModel;
    }

    public function get(
        AbstractDirective ...$directives
    ) : array {

        //todo throw exception if table is not defined

        $table = $this->resourceModel->getVariable('table');

        $records = static::select($table);
        $records = json_decode(json_encode($records)); //deep clone

        $idAttribute = $this->resourceModel->getIdAttribute();

        //apply directives
        //todo

        //filter

        //todo don't ignore default

        /**
         * @var Filter
         */
        $filter = Filter::getByClass(
            Filter::class,
            $directives
        );

        if ($filter !== null) {
            if (!empty($primary = $filter->getPrimary())) {
                $records = array_filter(
                    $records,
                    function ($record) use ($primary, $idAttribute) {
                        return in_array($record->{$idAttribute}, $primary);
                    }
                );
            }
        }

        //sort
        $sort = Sort::getByClass(
            Sort::class,
            $directives
        );

        if ($sort !== null) {
            $sortCallback =
                $sort->getAscending()
                    ? function ($a, $b) {
                    return $a > $b;
                }
                    : function ($a, $b) {
                    return $a < $b;
                };

            $sortAttribute = $sort->getAttribute();

            usort(
                $records,
                function ($a, $b) use ($sortCallback, $sortAttribute) {
                    return $sortCallback(
                        $a->{$sortAttribute} ?? null,
                        $b->{$sortAttribute} ?? null
                    );
                }
            );
        }

        //fields
        //todo

        //pagination
        $page = Directive::getByClass(
            Page::class,
            $directives
        );

        if ($page !== null) {
            $records = array_slice(
                $records,
                $page->getOffset(),
                $page->getLimit()
            );
        }

        return Resource::parseFromRecords(
            $records,
            $this->resourceModel,
            $directives
        );
    }

    public function post(
        \stdClass $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    ) {
        $idAttribute = $this->resourceModel->getIdAttribute();

        if (!property_exists($attributes, $idAttribute)) {
            //generate an id
            $attributes->{$idAttribute} = md5(mt_rand());
        }

        $table = $this->resourceModel->getVariable('table');

        static::insert($table, $attributes);

        switch ($return) {
            case Create::RETURN_NUMBER_OF_RECORDS:
                return 1;
            case Create::RETURN_RECORDS:
                return $attributes;
            case Create::RETURN_ID:
            default:
                return $attributes->{$idAttribute};
        }
    }

    public function patch(string $id, \stdClass $attributes, $return = null)
    {
        // TODO: Implement patch() method.
    }

    public function delete(string $id, \stdClass $additionalAttributes = null)
    {
        // TODO: Implement delete() method.
    }
}
