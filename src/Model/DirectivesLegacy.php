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

use Phramework\Exceptions\NotImplementedException;
use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\FilterAttribute;
use Phramework\JSONAPI\FilterJSONAttribute;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Sort;
use Phramework\JSONAPI\Relationship;
use Phramework\Models\Operator;

/**
 * Implementation of directives
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @deprecated 
 */
abstract class DirectivesLegacy extends \Phramework\JSONAPI\Model\Cache
{
    /**
     * Apply  page, filter, sort and fields directives to query.
     * This method will replace `{{table}}`, `{{sort}}`, `{{page}}`, `{{filter}}` and `{{fields}}` in query.
     * @uses static::handlePage
     * @uses static::handleSort
     * @uses static::handleFilter
     * @uses static::handleFields
     * @param  string       $query    Query string
     * @param  Page|null    $page     See handlePage $page parameter
     * @param  Filter|null  $filter   See handleFilter $filter parameter
     * @param  Sort|null    $sort     See handleSort $sort parameter
     * @param  Fields|null  $fields   See handleFields $fields parameter
     * @param  boolean      $hasWhere If query already has an WHERE, default is true
     * @return string       Query
     * @example
     * ```php
     * $query = static::handleGet(
     *     'SELECT {{fields}}
     *      FROM "{{table}}"
     *      WHERE
     *        "{{table}}"."status" <> \'DISABLED\'
     *        {{filter}}
     *        {{sort}}
     *        {{page}}',
     *     $page,
     *     $filter,
     *     $sort,
     *     $fields,
     *     $page,
     *     true //query contains WHERE directive
     * );
     * ```
     */
    protected static function handleGet(
        $query,
        Page $page = null,
        Filter $filter = null,
        Sort $sort = null,
        Fields $fields = null,
        $hasWhere = true
    ) {
        $query = str_replace('{{table}}', static::getTable(), $query);

        $query = trim(self::handleFields(
            self::handlePage(
                self::handleSort(
                    self::handleFilter(
                        $query,
                        $filter,
                        $hasWhere
                    ),
                    $sort
                ),
                $page
            ),
            $fields
        ));

        return $query;
    }

}
