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

use Phramework\Exceptions\MissingParametersException;
use Phramework\Exceptions\Source\ISource;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\IncludeResources;
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

/**
 * Common controller methods
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class Controller
{
    /**
     * @param string        $id
     * @param ResourceModel $model
     * @param Directive[]   ...$directives
     * @return Resource|null
     */
    public static function getById(
        string $id,
        ResourceModel $model,
        Directive ...$directives
    ) {
        return $model->getById(
            $id,
            ...$directives
        );
    }


    /**
     * @param ResourceModel                             $model
     * @param array                                     $resources
     * @param \Phramework\JSONAPI\Directive\Directive[] ...$directives
     * @return null|\Resource[]
     * @throws \Phramework\Exceptions\RequestException
     */
    public static function includeRelationshipResources(
        ResourceModel $model,
        array $resources,
        Directive ...$directives
    ) {
        $include = Directive::getByClass(
            IncludeResources::class,
            $directives
        );

        if ($include === null || empty($include->getInclude())) {
            return null;
        }

        return $model::getIncludedData(
            $model,
            $resources,
            $include->getInclude(),
            ...$directives
        );
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
        $assert,
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
     * @todo write
     */
    public static function viewData(
        ResponseInterface $response,
        $data,
        \stdClass $links = null,
        \stdClass $meta = null,
        array $included = null
    ) : ResponseInterface {
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

        $response = $response
            ->withStatus(200)
            ->withHeader(
                'Content-Type',
                'application/vnd.api+json;charset=utf-8'
            );

        $response->getBody()->write(json_encode($viewParameters));

        return $response;
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

        foreach ($classes as $directiveClass) {
            if ($ignoreIfExists && in_array($directiveClass, $existClasses)) {
                continue;
            }

            $parsed = $directiveClass::parseFromRequest(
                $request,
                $model
            );

            if ($parsed !== null) {
                //overwrite
                if ($overwrite
                    && (
                    $key = array_search(
                        $directiveClass,
                        $existClasses,
                        true
                    ) !== false)
                ) {
                    $directives[$key] = $parsed;
                } else {
                    $directives[] = $parsed;
                }
            }
        }

        return $directives;
    }

    /**
     * @param              $object
     * @param ISource|null $source
     * @param string[]    ...$properties
     * @throws MissingParametersException
     */
    public static function requireProperties(
        $object,
        ISource $source = null,
        string ...$properties
    ) {
        $missing = [];

        //Work with objects
        if (is_array($object)) {
            $object = (object) $object;
        }

        foreach ($properties as $key) {
            if (!property_exists($object, $key)) {
                array_push($missing, $key);
            }
        }

        if (!empty($missing)) {
            throw new MissingParametersException(
                $missing,
                $source
            );
        }
    }
}
