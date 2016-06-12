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
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait Controller
{
    /**
     * @param string        $id
     * @param ResourceModel $model
     * @param Directive[]   ...$directives
     * @return Resource|null
     */
    protected static function getById(
        string $id,
        ResourceModel $model,
        Directive ...$directives
    ) {
        return $model->getById(
            $id,
            ...$directives
        );
    }

    protected static function includeRelationshipResources(
        ServerRequestInterface $request,
        ResourceModel $model,
        array $resources,
        Directive ...$directives
    ) {
        //todo

        if ($includeResources === null) {
            return null;
        }
    }

    /**
     * If !assert then a NotFoundException exceptions is thrown.
     *
     * @param mixed  $assert
     * @param string $exceptionMessage [Optional] Default is
     * 'Resource not found'
     * @throws \Phramework\Exceptions\NotFoundException
     */
    public static function assertExists(
        $assert,
        $exceptionMessage = 'Resource not found'
    ) {
        if (!$assert) {
            throw new \Phramework\Exceptions\NotFoundException(
                $exceptionMessage
            );
        }
    }

    /**
     * If !assert then a Exception exception is thrown.
     *
     * @param mixed  $assert
     * @param string $exceptionMessage [Optional] Default is 'unknown_error'
     *
     * @throws \Exception
     */
    public static function assertUnknownError(
        mixed $assert,
        string $exceptionMessage = 'Unknown error'
    ) {
        if (!$assert) {
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * @param Resource|Resource[] $data
     * @param \stdClass $links
     * @param \stdClass $meta
     * @param (Resource|RelationshipResource)[]     $included
     * @return bool
     * @todo write
     */
    public static function viewData(
        $data,
        \stdClass $links = null,
        \stdClass $meta = null,
        array $included = null
    ) {
        $viewParameters = new \stdClass();

        if ($links) {
            $viewParameters->links = $links;
        }

        $viewParameters->data = $data;

        if ($included !== null) {
            $viewParameters->included = $included;
        }

        if ($meta) {
            $viewParameters->meta = $meta;
        }


        unset($viewParameters);

        return true;
    }

    /**
     * @param string[]    $classes
     * @param Directive[] $directives
     * @return (Directive|null)[]
     */
    public static function getByClasses(
        array $classes,
        array $directives
    ) : array {
        $list = array_fill(0, count($classes), null);

        $directives = array_values($directives); //clean any keys set

        foreach ($directives as $directive) {
            if (($key = array_search(get_class($directive), $classes, true)) !== false) {
                $list[$key] = $directive;
            }
        }

        return $list;
    }

    /**
     * @param string[]               $classes
     * @param ResourceModel          $model
     * @param ServerRequestInterface $request
     * @param Directive[]            $directives
     * @param bool                   $ignoreIfExists
     * @param bool                   $overwrite
     * @return Directive[]
     */
    public static function parseDirectives(
        array $classes,
        ResourceModel $model,
        ServerRequestInterface $request,
        array $directives,
        bool $ignoreIfExists = true,
        bool $overwrite = false
    ) {
        $existClasses = array_map(
            function ($d) {
                return get_class($d);
            },
            $directives
        );

        foreach($classes as $directiveClass) {
            if ($ignoreIfExists && in_array($directiveClass, $existClasses)) {
                continue;
            }

            $parsed = $directiveClass::parseFromRequest(
                $request,
                $model
            );


            if ($parsed !== null) {
                //overwrite
                if (
                    $overwrite
                    && (
                    $key = array_search(
                        $directiveClass,
                        $existClasses,
                        true
                    )  !== false)
                ) {
                    $directives[$key] = $parsed;
                } else {
                    $directives[] = $parsed;
                }
            }
        }

        return $directives;
    }
}
