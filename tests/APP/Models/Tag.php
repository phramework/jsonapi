<?php
/**
 * Copyright 2015 Xenofon Spafaridis
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
class Tag extends \Phramework\JSONAPI\Model
{
    protected static $type     = 'tag';
    protected static $endpoint = 'tag';
    protected static $table    = 'tag';

    public static function get()
    {
        return self::collection(
            Database::executeAndFetchAll(
                'SELECT * FROM "tag"
                WHERE "status" = 1'
            )
        );
    }

    public static function getById($id)
    {
        return self::resource(
            Database::executeAndFetch(
                'SELECT * FROM "tag"
                WHERE "status" = 1
                AND "id" = ?
                LIMIT 1',
                [$id]
            )
        );
    }

    /**
     * post article-tag relationship
     * @param  integer $tagId                [description]
     * @param  integer $articleId            [description]
     * @param  null|object|array $additionalAttributes    Will be ignored
     * @param  integer $return               [description]
     * @return integer                       [description]
     */
    public static function postRelationshipByArticle(
        $tagId,
        $articleId,
        $additionalAttributes = null,
        $return = \Phramework\Database\Operations\Create::RETURN_NUMBER_OF_RECORDS
    ) {
        return \Phramework\Database\Operations\Create::create(
            [
                'tag-id' => $tagId,
                'article-id' => $articleId,
                'status' => 1
            ],
            'article-tag',
            static::getSchema(),
            $return
        );
    }

    public static function getRelationshipByArticle($articleId)
    {
        return Database::executeAndFetchAllArray(
            'SELECT "t"."id"
            FROM "article-tag" as "a-t"
            JOIN "tag" AS "t"
              ON "a-t"."tag-id" = "t"."id"
            WHERE "a-t"."article-id" = ?
              AND "a-t"."status" = 1
              AND "t"."status" = 1',
            [$articleId]
        );
    }
}
