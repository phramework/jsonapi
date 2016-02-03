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

use Phramework\Database\Database;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Sort;
use Phramework\Validate\ArrayValidator;
use Phramework\Validate\ObjectValidator;
use Phramework\Validate\StringValidator;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Tag extends \Phramework\JSONAPI\APP\Model
{
    protected static $type     = 'tag';
    protected static $endpoint = 'tag';
    protected static $table    = 'tag';

    /**
     * @param Page|null $page       *[Optional]*
     * @param Filter|null $filter   *[Optional]*
     * @param Sort|null $sort       *[Optional]*
     * @param Fields|null $fields   *[Optional]*
     * @param mixed ...$additionalParameters *[Optional]*
     * @throws NotImplementedException
     * @return Resource[]
     */
    public static function get(
        Page   $page = null,
        Filter $filter = null,
        Sort   $sort = null,
        Fields $fields = null,
        ...$additionalParameters
    ) {
        $records = [
            [
                'id' => 1,
                'status' => 1,
                'title' => 'Tag 1',
                'created' => null
            ],
            [
                'id' => 2,
                'status' => 1,
                'title' => 'Tag 2',
                'created' => time()
            ],
            [
                'id' => 3,
                'status' => 1,
                'title' => 'Tag 3',
                'created' => time() + 100
            ],
            [
                'id' => 4,
                'status' => 0,
                'title' => 'Tag 4',
                'created' => time() + 1000
            ]
        ];

        return self::collection(self::handleGetWithArrayOfRecords(
            $records,
            $page,
            $filter,
            $sort,
            $fields,
            ...$additionalParameters
        ));
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

    /**
     * @param $articleId
     * @return RelationshipResource[]
     */
    public static function getRelationshipByArticle(
        $articleId,
        $relationshipKey,
        Fields $fields = null,
        $flags = Resource::PARSE_DEFAULT
    ) {
        //*NOTE* idAttribute of tag is `id` so `tag-id` of pairs should be served as `id`
        $matrix = [
            [
                'article-id' => '1',
                'id'         => '1',
                'status'     => 'ENABLED',
                Resource::META_MEMBER => (object) [
                    'weight' => 100
                ]
            ],
            [
                'article-id' => '1',
                'id'         => '2',
                'status'     => 'ENABLED'
            ],
            [
                'article-id' => '2',
                'id'         => '1',
                'status'     => 'ENABLED'
            ],
            [
                'article-id' => '3',
                'id'         => '4',
                'status'     => 'DISABLED'
            ]
        ];

        $records = array_filter(
            $matrix,
            function ($pair) use ($articleId) {
                return $pair['article-id'] == $articleId;
            }
        );

        $class = static::class;

        /*$resources = array_reduce(
            $matrix,
            function ($carry, $item) use ($class) {
                $carry[] = RelationshipResource::parseFromRecord(
                    [
                        'id'     => $item['id'],
                        'status' => $item['status']
                    ],
                    $class
                );

                return $carry;
            },
            []
        );*/

        return RelationshipResource::parseFromRecords(
            $records,
            $class,
            $fields,
            $flags
        );

        /*return Database::executeAndFetchAllArray(
            'SELECT "t"."id"
            FROM "article-tag" as "a-t"
            JOIN "tag" AS "t"
              ON "a-t"."tag-id" = "t"."id"
            WHERE "a-t"."article-id" = ?
              AND "a-t"."status" = 1
              AND "t"."status" = 1',
            [$articleId]
        );*/

        return [];
    }

    /**
     * @todo
     */
    public static function getRelationships()
    {
        return new \stdClass();

        return (object)[
            'article' => new Relationship(
                'article-id',
                'article',
                Relationship::TYPE_TO_MANY,
                Article::class,
                'id'
            ),
        ];
    }
}
