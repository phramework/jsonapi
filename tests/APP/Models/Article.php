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
use \Phramework\Validate\BooleanValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Article extends \Phramework\JSONAPI\Model
{
    protected static $type     = 'article';
    protected static $endpoint = 'article';
    protected static $table    = 'article';

    public static function getValidationModel()
    {
        return (object)[
            'attributes' => new ObjectValidator(
                [
                    'title' => new StringValidator(2, 32),
                    'status' => (new BooleanValidator())
                        ->setDefault(true)
                ],
                ['title']
            ),
            'relationships' => new ObjectValidator(
                [
                    'creator' => new UnsignedIntegerValidator(),
                    'tag' => new ArrayValidator(
                        0,
                        null,
                        new UnsignedIntegerValidator() //items
                    )
                ],
                ['creator'],
                false
            )
        ];
    }

    public static function get()
    {
        return self::collection(
            Database::executeAndFetchAll(
                'SELECT * FROM "article"
                WHERE "status" = 1'
            )
        );
    }

    /**
     * [getById description]
     * @param  int|int[] $id [description]
     * @return stdClass|stdClass[]
     */
    public static function getById($id)
    {
        $is_array = is_array($id);

        if (!$is_array) {
            $id = [$id];
        }

        $data = Database::executeAndFetchAll(
            'SELECT * FROM "article"
            WHERE "status" = 1
            AND "id" IN (' . implode(',', array_fill(0, count($id), '?')) . ')
            LIMIT ' . count($id),
            $id
        );

        $resources = array_map('static::resource', $data);

        if (!$is_array) {

            if (!$data) {
                return null;
            }

            return $resources[0];
        } else {
            return $resources;
        }
    }

    public static function getRelationships()
    {
        return (object)[
            'creator' => new Relationship(
                'creator-user-id',
                'user',
                Relationship::TYPE_TO_ONE,
                User::class,
                'id'
            ),
            'tag' => new Relationship(
                'tag-id',
                'tag',
                Relationship::TYPE_TO_MANY,
                Tag::class,
                'id'
            )
        ];
    }
}
