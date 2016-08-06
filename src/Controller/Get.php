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

use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\Fields;
use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\Directive\IncludeResources;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\Directive\Sort;
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait Get
{
    /**
     * Handle GET requests to collections
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param ResourceModel          $model
     * @param array                  $directives
     * @return ResponseInterface
     * @todo add links
     */
    public static function handleGet(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ResourceModel $model,
        array $directives = []
    ) : ResponseInterface {
        //Parse request related directives from request
        $directives = Controller::parseDirectives(
            [
                Fields::class,
                IncludeResources::class,
                Page::class,
                Sort::class,
                Filter::class
            ],
            $model,
            $request,
            $directives
        );

        $collection = $model->get(
            ...$directives
        );

        $page = Directive::getByClass(
            Page::class,
            $directives
        );

        $meta = (object) [
            'page' => (
            $page === null
                ? $model->getDefaultDirectives()->{Page::class} ?? null
                : $page
            )
        ];

        return Controller::viewData(
            $response,
            $collection,
            (object) [
                //todo
                // 'self' => $resourceModel->getSelfLink($id)
            ],
            $meta,
            Controller::includeRelationshipResources(
                $model,
                $collection,
                ...$directives
            )
        );
    }
}