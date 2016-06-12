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

use Phramework\JSONAPI\Directive\AdditionalParameter;
use Phramework\JSONAPI\Directive\AdditionalRelationshipsParameter;
use Phramework\JSONAPI\Directive\IncludeResources;
use Phramework\JSONAPI\Directive\Fields;
use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait GetById
{
    use Controller;

    /**
     * @param ServerRequestInterface $request
     * @param string                 $id
     * @param ResourceModel          $model
     * @param  Directive[]           $directives
     * @throws \Phramework\Exceptions\NotFoundException
     */
    public static function handleGetById(
        ServerRequestInterface $request,
        string $id,
        ResourceModel $model,
        array $directives
    ) {
        //todo filter id if model filter is set

        //Parse request related directives from request
        $directives = static::parseDirectives(
            [
                Fields::class,
                IncludeResources::class
            ],
            $model,
            $request,
            $directives
        );

        $resource = static::getById(
            $id,
            $model,
            ...$directives
        );

        static::assertExists($resource);

        static::viewData(
            $resource,
            (object) [
               //todo 
               // 'self' => $model->getSelfLink($id)
            ],
            null,
            //todo
            static::includeRelationshipResources(
                $request,
                $model,
                [$resource],
                ...$directives
            )
        );
    }
}