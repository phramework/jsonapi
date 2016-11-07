<?php
declare(strict_types=1);
/*
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
use Phramework\JSONAPI\Controller\Helper\RequestWithBody;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo modify to allow batch, remove id ?
 */
trait Patch
{
    //prototype
    //@todo figure out view callback arguments
    public static function handlePatch(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ResourceModel $model,
        array $validationCallbacks = [],
        callable $viewCallback = null,
        int $bulkLimit = 1,
        array $directives = []
    ) : ResponseInterface {
        list($data, $isBulk) = RequestWithBody::prepareData(
            $request,
            $bulkLimit
        );

        //gather data as a queue

        //prefer PATCH validation model
        $validationModel = $model->getValidationModel(
            'PATCH'
        );

        //on each

          // validate

          //check if exists

          //call validation callbacks

          //gather output for view callback

        //return view callback, it MUST return a ResponseInterface
        if ($viewCallback !== null) {
            return $viewCallback(
                $request,
                $response
            );
        }

        return Patch::defaultPatchViewCallback(
            $request,
            $response
        );
    }

    public static function defaultPatchViewCallback(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        //Return 204 No Content
        return Response::noContent($response);
    }
}
