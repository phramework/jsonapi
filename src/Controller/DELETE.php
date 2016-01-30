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
 * DELETE related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class DELETE extends \Phramework\JSONAPI\Controller\PATCH
{
    /**
     * Handle DELETE method
     * On success will respond with 204 No Content
     * @param  object $params                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  integer|string $id                      Requested resource's id
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @throws \Phramework\Exceptions\NotFound If resource not found
     * @throws \Phramework\Exceptions\RequestException If unable to delete
     * @uses model's `getById` method to fetch resource
     * @uses $modelClass::delete method to
     *     delete resources
     * @return void
     */
    protected static function handleDELETE(
        $params,
        $method,
        $headers,
        $id,
        $modelClass,
        $primaryDataParameters = []
    ) {
        //Fetch data, in order to check if resource exists (and/or is accessible)
        $data = $modelClass::getById($id, $primaryDataParameters);

        //Check if resource exists
        static::exists($data);

        $delete = $modelClass::delete($id, $primaryDataParameters);

        if (!$delete) {
            throw new \Phramework\Exceptions\RequestException(
                'Unable to delete record'
            );
        }

        \Phramework\JSONAPI\Viewers\JSONAPI::header();

        \Phramework\Models\Response::noContent();

        return;
    }
}
