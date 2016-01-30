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
namespace Phramework\JSONAPI\APP\Models;

use \Phramework\Database\Database;
use \Phramework\JSONAPI\Relationship;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\UnsignedIntegerValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class User extends \Phramework\JSONAPI\Model
{
    protected static $type     = 'user';
    protected static $endpoint = 'user';
    protected static $table    = 'user';

    /**
     * @param Page|null $page       *[Optional]*
     * @param Filter|null $filter   *[Optional]*
     * @param Sort|null $sort       *[Optional]*
     * @param Fields|null $fields   *[Optional]*
     * @param mixed ...$additionalParameters *[Optional]*
     * @throws NotImplementedException
     * @return Resource[]
     * @todo apply Page, Filter and Sort rules to arrays as helper utility
     */
    public static function get(
        Page   $page = null,
        Filter $filter = null,
        Sort   $sort = null,
        Fields $fields = null,
        ...$additionalParameters)
    {
        return self::collection(
            Database::executeAndFetchAll(
                'SELECT * FROM "user"
                WHERE "status" = 1'
            )
        );
    }

    /**
    public static function getById($id)
    {
        return self::resource(
            Database::executeAndFetch(
                'SELECT * FROM "user"
                WHERE "status" = 1
                AND "id" = ?
                LIMIT 1',
                [$id]
            )
        );
    }**/
}
