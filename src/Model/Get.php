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
namespace Phramework\JSONAPI\Model;

use Phramework\JSONAPI\Controller\GET\Fields;
use Phramework\JSONAPI\Controller\GET\Filter;
use Phramework\JSONAPI\Controller\GET\Page;
use Phramework\JSONAPI\Controller\GET\Sort;
use Phramework\JSONAPI\Resource;
use \Phramework\Phramework;
use \Phramework\Models\Operator;
use \Phramework\JSONAPI\Relationship;

/**
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add cache
 */
abstract class Get
{
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
        throw new NotImplementedException('This resource model doesn\'t have a get method');
    }

    /**
     * @param string|string[] $id An id of a single resource or ids of multiple resources
     * @param mixed ...$additionalParameters
     * @return Resource|Object
     */
    public static function getById($id, ...$additionalParameters)
    {

    }

    /**
     * Create a filter instance by parsing request parameters and using current implementation model's rules.
     * @param  object $params   Request parameters
     * @param  string $method   Request method
     * @param  array  $headers  Request headers
     */
    public static function createFilter($params, $method, $headers)
    {

    }
}