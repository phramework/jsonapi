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
namespace Phramework\JSONAPI\Controller;

use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Sort;
use Phramework\JSONAPI\Model;
use \Phramework\Models\Request;
use \Phramework\Models\Operator;
use \Phramework\Exceptions\RequestException;

/**
 * GET related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class GET extends \Phramework\JSONAPI\Controller\GETById
{
    /**
     * handles GET requests
     * @param  object $parameters  Request parameters
     * @param  string $modelClass                      Resource's primary model class name
     * to be used
     * @param  array $primaryDataParameters           *[Optional]* Array with any
     * additional arguments that the primary data is requiring
     * @param  array $relationshipParameters [Optional] Array with any
     * additional argument primary data's relationships are requiring
     * @param  boolean $filterable                     *[Optional]* Default is
     * true, if true allows `filter` URI parameters to be parsed for filtering
     * @param  boolean $filterableJSON                 *[Optional]* Default is
     * false, if true allows `filter` URI parameters to be parsed for filtering
     * for JSON encoded fields
     * @param  boolean $sortable                       *[Optional]* Default is
     * true, if true allows sorting
     * @uses $modelClass::get method to fetch resource collection
     * @throws \Exception
     * @throws RequestException
     * @return boolean
     */
    protected static function handleGET(
        $parameters,
        $modelClass,
        $primaryDataParameters = [],
        $relationshipParameters = []
    ) {
        $page   = $modelClass::parsePage($parameters);
        $filter = $modelClass::parseFilter($parameters);
        $sort   = $modelClass::parseSort($parameters);
        $fields = $modelClass::parseFields($parameters);

        $data = $modelClass::get(
            $page,
            $filter,
            $sort,
            $fields,
            $primaryDataParameters
        );

        $requestInclude = static::getRequestInclude($parameters);

        //Get included data
        $includedData = $modelClass::getIncludedData(
            $data,
            $requestInclude,
            $fields,
            $relationshipParameters
        );

        return static::viewData(
            $data,
            (object)[
                'self' => $modelClass::getSelfLink()
            ],
            null,
            (empty($requestInclude) ? null : $includedData)
        );
    }
}
