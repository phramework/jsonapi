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

use \Phramework\Phramework;
use \Phramework\Models\Operator;
use \Phramework\JSONAPI\Relationship;

/**
 * Base JSONAPI Model
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class Model
{
    /**
     * Resource's type, used to describe resource objects that share
     * common attributes and relationships.
     * **MUST** be overwritten
     * @var string
     */
    protected static $type = null;

    /**
     * Resource's table name.
     * **MAY** be overwritten, default is null (no database)
     * @var string|null
     */
    protected static $table = null;

    /**
     * Resource's table's schema name.
     * **MAY** be overwritten, default is null (no schema)
     * @var string|null
     */
    protected static $schema = null;

    /**
     * Resource's identification attribute (Primary key in database).
     * **MAY** be overwritten, default is `"id"`
     * @var string
     */
    protected static $idAttribute = 'id';

    /**
     * Resource's endpoint, used for access by external request, usually it the same as type.
     * **MUST** be overwritten
     * @var string
     */
    protected static $endpoint = null;

    /**
     * Records's type casting schema for database records.
     * **MAY** be overwritten
     * Also it can be set to empty array to disable type
     * casting for this resource.
     * @var array|null
     * @deprecated
     */
    protected static $cast = null;

    /**
     * Records's type casting schema
     *
     * This object contains the rules applied to fetched data from database in
     * order to have correct data types.
     * @uses static::$cast If cast is not null
     * @uses static::getValidationModel If static::$cast is null, it uses
     * validationModel's attributes to extract the cast schema
     * @return array
     * @todo Rewrite validationModel's attributes
     * @deprecated
     */
    public static function getCast()
    {
        //If cast is not null
        if (static::$cast !== null) {
            return static::$cast;
        }

        return [];
    }

    /**
     * Get resource's type
     * @return string
     */
    public static function getType()
    {
        return static::$type;
    }

    /**
     * Get resource's table name
     * @return string|null
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * Get resource's table schema name
     * @return string|null
     */
    public static function getSchema()
    {
        return static::$schema;
    }

    /**
     * Resource's identification attribute (Primary key column in database)
     * @return string
     */
    public static function getIdAttribute()
    {
        return static::$idAttribute;
    }

    /**
     * Get resource's endpoint
     * @return string
     */
    public static function getEndpoint()
    {
        return static::$endpoint;
    }

    /**
     * Get link to resource's self
     * @param  string $appendString
     * @return string
     * @uses Phramework::getSetting with key `"base"`
     */
    public static function getSelfLink($appendString = '')
    {
        return Phramework::getSetting('base') . static::getEndpoint() . '/' . $appendString;
    }
}
