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
namespace Phramework\JSONAPI\Controller;

use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;

/**
 * DELETE
 * @package JSONAPI
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class POST extends \Phramework\JSONAPI\Controller\GET
{
    /**
     * @param  array  $params                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  string $modelClass                      Resource's primary model
     */
    protected static function handlePOST(
        $params,
        $method,
        $headers,
        $modelClass
    ) {
        $validationModel = $modelClass::getValidationModel();

        $requestAttributes = static::getRequestAttributes($params);

        $attributes = $validationModel->parse($requestAttributes);

        $id = $modelClass::post((array)$attributes);

        //Prepare response with 201 Created status code
        \Phramework\Models\Response::created(
            $modelClass::getSelfLink($id)
        );

        return static::viewData(
            $modelClass::resource(['id' => $id]),
            ['self' => $modelClass::getSelfLink($id)]
        );
    }
}
