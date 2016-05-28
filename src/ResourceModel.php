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
namespace Phramework\JSONAPI;

/**
 * Class ResourceModel
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
abstract class ResourceModel
{
    /**
     * @type InternalModel
     */
    //protected static $model = null;  //important to define $model in extended classes

    /**
     * @return InternalModel
     */
    public static function getModel() : InternalModel
    {

        if (static::$model === null) {
            static::$model = static::defineModel();
        }

        return static::$model;
    }

    /**
     * MUST BE IMPLEMENTED
     */
    abstract protected static function defineModel() : InternalModel;

    /**
     * @param IDirective[] ...$directives
     * @return Resource[]
     */
    public static function get(IDirective ...$directives) : array
    {
        return static::getModel()->get(...func_get_args());
    }

    /**
     * @param string       $id
     * @param IDirective[] ...$directives
     * @return Resource|null
     */
    public static function getById(
        string $id,
        IDirective ...$directives
    ) {
        return static::getModel()->getById(...func_get_args());
    }

    /**
     * @return string
     */
    public static function getResourceType()
    {
        return static::getModel()->getResourceType();
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @deprecated
     */
    /*public static function __callStatic(string $name, array $arguments)
    {
        if (in_array(
            $name,
            [
                'get',
                'getById',
                'getResourceType',
            ]
        )) {
            return call_user_func_array(
                [static::getModel(), $name],
                $arguments
            );
        }

        return call_user_func_array(
            [static::getModel(), $name],
            $arguments
        );
    }*/
}
