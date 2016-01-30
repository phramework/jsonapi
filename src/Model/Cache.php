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

use Phramework\JSONAPI\Resource;

/**
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
  */
abstract class Cache extends \Phramework\JSONAPI\Model\Model
{
    /**
     * Cache object
     * @var object|null
     * @example
     * ```php
     * (object) [
     *     'trend' => (object)[
     *         '1' => class stdClass...,
     *         '2' => class stdClass...
     *     ],
     *     'trend_template' => (object)[
     *         '13' => class stdClass...,
     *         '12' => class stdClass...
     *     ]
     * ]
     * ```
     */
    protected static $cache = null;

    /**
     * Initialize cache object for a resource type
     * **NOTE** `self` MUST be used not `static` when accessing `$cache` object
     * @param string $type Resource type
     */
    protected static function initializeCache($type)
    {
        if (self::$cache === null) {
            self::$cache = new \stdClass();
        }

        if (!property_exists(self::$cache, $type)) {
            self::$cache->{$type} = new \stdClass();
        }
    }

    /**
     * @param string $id
     * @return Resource|null
     */
    protected static function getCache($id)
    {
        $type = static::getType();

        static::initializeCache($type);

        $collection = self::$cache->{$type};

        if (!property_exists($collection, $id)) {
            return null;
        }

        return $collection->{$id};
    }

    /**
     * @param string $id
     * @param Resource $resource
     * @return bool
     */
    protected static function setCache($id, $resource)
    {
        if (!static::$caching) {
            return false;
        }

        $type = static::getType();

        static::initializeCache($type);

        $collection = self::$cache->{$type};

        $collection->{$id} = $resource;

        return true;
    }

    /**
     * Remove resource from cache, used by `PUT`, `PATCH` and `DELETE` methods when changing an object
     * @param string $id
     */
    protected static function invalidateCache($id)
    {
        $type = static::getType();

        static::initializeCache($type);

        $collection = self::$cache->{$type};

        unset($collection->{$id});
    }
}
