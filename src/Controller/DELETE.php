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

use Phramework\Exceptions\ServerException;
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
     * @param  object $parameters                          Request parameters
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
     * @return bool
     */
    protected static function handleDELETE(
        $parameters,
        $method,
        $headers,
        $id,
        $modelClass,
        $primaryDataParameters = [],
        $validationCallbacks = [],
        $viewCallback = null
    ) {
        if ($viewCallback !== null && !is_callable($viewCallback)) {
            throw new ServerException('View callback is not callable!');
        }

        //Fetch data, in order to check if resource exists (and/or is accessible)
        $resource = $modelClass::getById(
            $id,
            null, //fields
            ...$primaryDataParameters
        );

        //Check if resource exists
        static::exists($resource);

        //Call validation callbacks if set
        foreach ($validationCallbacks as $callback) {
            call_user_func(
                $callback,
                $id,
                $resource
            );
        }

        $delete = $modelClass::delete($id, $primaryDataParameters);

        if (!$delete) {
            throw new RequestException(
                'Unable to delete record'
            );
        }

        if ($viewCallback !== null) {
            return call_user_func(
                $viewCallback,
                $id
            );
        }

        \Phramework\JSONAPI\Viewers\JSONAPI::header();

        \Phramework\Models\Response::noContent();

        return true;
    }
}
