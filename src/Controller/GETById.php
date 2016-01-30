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

use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;

/**
 * GETById related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class GETById extends \Phramework\JSONAPI\Controller\Relationships
{

    /**
     * handles GETById requests
     * @param  object $params                          Request parameters
     * @param  integer|string $id                      Requested resource's id
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @param  array $relationshipParameters [Optional] Array with any
     * additional argument primary data's relationships are requiring
     * @uses model's `getById` method to fetch resource
     * @return boolean
     */
    protected static function handleGETByid(
        $params,
        $id,
        $modelClass,
        $primaryDataParameters = [],
        $relationshipParameters = []
    ) {
        //Rewrite resource's id
        $id = Request::requireId($params);

        $filterValidationModel = $modelClass::getFilterValidationModel();

        //Filter id attribute value
        if (!empty($filterValidationModel) && isset($filterValidationModel->{$idAttribute})) {
            $id = $filterValidationModel->{$idAttribute}->parse($id);
        }

        //validate if set

        $data = $modelClass::getById($id, $primaryDataParameters);

        //Check if resource exists
        static::exists($data);

        $requestInclude = static::getRequestInclude($params);

        $includedData = $modelClass::getIncludedData(
            $data,
            $requestInclude,
            $relationshipParameters
        );

        return static::viewData(
            $data,
            (object)[
                'self' => $modelClass::getSelfLink($id)
            ],
            null,
            (empty($requestInclude) ? null : $includedData)
        );
    }
}
