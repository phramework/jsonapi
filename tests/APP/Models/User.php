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
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Sort;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\UnsignedIntegerValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class User extends \Phramework\JSONAPI\APP\Model
{
    protected static $type     = 'user';
    protected static $endpoint = 'user';
    protected static $table    = 'user';

    public static $records = [
        [
            'id'       => '1',
            'username' => 'nohponex',
            'email'    => 'nohponex@gmail.com'
        ],
        [
            'id'       => '1',
            'username' => 'alkallio',
            'email'    => 'alkallio@gmail.com'
        ]
    ];

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


        return self::collection(self::handleGetWithArrayOfRecords(
            self::$records,
            $page,
            $filter,
            $sort,
            $fields,
            ...$additionalParameters
        ));
    }

    /**
     * TYPE_TO_ONE callback
     * @param $articleId
     * @return string
     */
    public static function getRelationshipByArticle($articleId)
    {
        return '1';
    }
}
