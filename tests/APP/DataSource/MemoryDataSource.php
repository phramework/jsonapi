<?php
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
namespace APP\DataSource;

use Phramework\Database\Operations\Create;
use Phramework\JSONAPI\DataSource\IDataSource;
use Phramework\JSONAPI\ResourceModel;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class MemoryDataSource implements IDataSource
{
    protected static $database = [];

    public static function addTable(string $table)
    {
        if (property_exists(static::$database, $table)) {
            throw new \LogicException('Table name exists');
        }
        
        static::$database->{$table} = [];
    }

    public static function insert(string $table, \stdClass $record)
    {
        static::$database->{$table}[] = $record;
    }

    public static function select(string $table) : array
    {

        return static::$database->{$table};
    }

    /**
     * @var ResourceModel
     */
    protected $model;

    public function __construct(ResourceModel $model)
    {
        $this->model = $model;
    }

    public function get(
        array $directives
    ) : array {
        // TODO: Implement get() method.

        $table = $this->model->getVariable('table');

        $data = static::select($table);

        //apply directives
        //todo

        //filter
        //todo

        //sort
        //todo

        //fields
        //todo

        //pagination
        //todo

        return $data;
    }

    public function post(
        \stdClass $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    ) {
        //todo insert id
        if (!property_exists($attributes, $this->model->getIdAttribute())) {
            $attributes->{$this->model->getIdAttribute()} = md5(mt_rand());
        }

        $id = $attributes->{$this->model->getIdAttribute()};

        $table = $this->model->getVariable('table');

        static::insert($table, $attributes);

        switch ($return) {
            case Create::RETURN_NUMBER_OF_RECORDS:
                return 1;
            case Create::RETURN_RECORDS:
                return $attributes;
            case Create::RETURN_ID:
            default:
                return $attributes->{$this->model->getIdAttribute()};
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
