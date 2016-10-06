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
namespace Phramework\JSONAPI\Directive;

use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
abstract class Directive
{
    abstract public function validate(ResourceModel $model) : bool;

    /**
     * @todo define request object in phramework
     * @param ServerRequestInterface $request
     * @param ResourceModel          $model
     * @return null|Directive
     */
    abstract public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    );

    /**
     * @param string      $class
     * @param Directive[] $directives
     * @return static|null
     */
    public static function getByClass(string $class, array $directives)
    {
        foreach ($directives as $directive) {
            if (get_class($directive) === $class) {
                return $directive;
            }
        }

        return null;
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

    public static function parseByClasses(
        array $classes,
        ResourceModel $model,
        \stdClass $request,
        array &$directives,
        bool $ignoreIfExists = true,
        bool $overwrite = true
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
    }
}
