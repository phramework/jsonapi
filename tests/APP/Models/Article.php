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
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\Page;
use \Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Sort;
use Phramework\JSONAPI\ValidationModel;
use Phramework\Models\Operator;
use \Phramework\Validate\ArrayValidator;
use Phramework\Validate\IntegerValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\UnsignedIntegerValidator;
use \Phramework\Validate\BooleanValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Article extends \Phramework\JSONAPI\APP\Model
{
    protected static $type     = 'article';
    protected static $endpoint = 'article';
    protected static $table    = 'article';

    /**
     * @return string[]
     */
    public static function getSortable()
    {
        return ['id'];
    }

    /**
     * @return string[]
     */
    public static function getFields()
    {
        return [
            'title',
            'updated'
        ];
    }

    /**
     * @return string[]
     */
    public static function getMutable()
    {
        return ['title'];
    }

    /**
     * @return ValidationModel
     */
    public static function getValidationModel()
    {
        return new ValidationModel(
            new ObjectValidator(
                [
                    'title'  => new StringValidator(2, 32),
                    'status' => (new BooleanValidator())
                        ->setDefault(true)
                ],
                ['title']
            ), // Attributes
            new ObjectValidator(
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
            ) // Relationships
        );
    }

    /**
     * @return ObjectValidator
     */
    public static function getFilterValidationModel()
    {
        return new ObjectValidator([
            'id'      => new UnsignedIntegerValidator(0, 10),
            'title'   => new StringValidator(2, 32),
            'updated' => new UnsignedIntegerValidator(),
            'created' => new UnsignedIntegerValidator(),
            'meta'    => new ObjectValidator([
                'timestamp' => new UnsignedIntegerValidator(),
                'keywords'  => new StringValidator()
            ]),
            'order'   => new UnsignedIntegerValidator(),
            'tag'     => new StringValidator()
        ]);
    }

    /**
     * @return object
     */
    public static function getFilterable()
    {
        return (object) [
            'no-validator' => Operator::CLASS_COMPARABLE,
            'status'       => Operator::CLASS_COMPARABLE,
            'title'        => Operator::CLASS_COMPARABLE | Operator::CLASS_LIKE,
            'updated'      => Operator::CLASS_ORDERABLE  | Operator::CLASS_NULLABLE,
            'created'      => Operator::CLASS_ORDERABLE  | Operator::CLASS_NULLABLE,
            'meta'         => Operator::CLASS_JSONOBJECT | Operator::CLASS_NULLABLE | Operator::CLASS_COMPARABLE,
            'tag'          => Operator::CLASS_IN_ARRAY,
            'order'        => Operator::CLASS_ORDERABLE
        ];
    }

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
        return self::collection(
            self::handleGetWithArrayOfRecords(
                static::getRecords(),
                $page,
                $filter,
                $sort,
                $fields,
                ...$additionalParameters
            ),
            $fields
        );
    }

    public static function getRelationships()
    {
        return (object)[
            'creator' => new Relationship(
                User::class,
                Relationship::TYPE_TO_ONE,
                'creator-user_id',
                null,
                Relationship::FLAG_DEFAULT | Relationship::FLAG_DATA
            ),
            'tag' => new Relationship(
                Tag::class,
                Relationship::TYPE_TO_MANY,
                null,
                [Tag::class, 'getRelationshipByArticle'],
                Relationship::FLAG_DEFAULT | Relationship::FLAG_DATA
            )
        ];
    }

    public static function post(
        $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    ) {
        $records = static::getRecords();

        return $records[0]['id'];
    }

    public static function patch($id, $attributes)
    {
        return 1;
    }

    /**
     * Will return false on `id` = 3
     * @return bool
     */
    public static function delete($id, $additionalAttributes = null)
    {
        return (
            $id == '3'
            ? false
            : true
        );
    }

    public static function getRecords()
    {
        return [
            [
                'id' => '1',
                'creator-user_id' => '1',
                'status' => 1,
                'title' => 'First post',
                'updated' => null,
                'meta' => (object)[
                    'keywords' => 'blog'
                ],
                Resource::META_MEMBER => (object)[
                    'view' => 1000,
                    'unique' => 100
                ]
            ],
            [
                'id' => '2',
                'creator-user_id' => '1',
                'status' => 1,
                'title' => 'Second post',
                'updated' => time(),
                'meta' => null,
                Resource::META_MEMBER => [
                    'some_key' => [
                        1,
                        2,
                        3
                    ]
                ]
            ],
            [
                'id' => '3',
                'creator-user_id' => '2',
                'status' => 1,
                'title' => 'Third post',
                'updated' => time() + 100,
                'meta' => null
            ],
            [
                'id' => '4',
                'creator-user_id' => '1',
                'status' => 0,
                'title' => 'Fourth post',
                'updated' => time() + 1000,
                'meta' => null
            ]
        ];
    }
}
