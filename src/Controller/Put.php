<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
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

use Phramework\JSONAPI\Controller\Helper\RequestBodyQueue;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait Put
{
    use Controller;
    use RequestBodyQueue;

    //prototype
    public static function handlePut(
        ServerRequestInterface $request,
        ResourceModel $model,
        string $id,
        array $validationCallbacks = [],
        callable $viewCallback = null,
        int $bulkLimit = null,
        array $directives
    ) {
        //gather data as a queue

        //check bulk limit

        //on each validate

        //prefer PATCH validation model
        $validationModel = $model->getValidationModel(
            'PUT'
        );

        //check if exists

        //on each call validation callback

        //204 or view callback
    }
}