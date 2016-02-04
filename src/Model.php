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
            static::getTable(),
            static::getIdAttribute()
        );
    }
}
