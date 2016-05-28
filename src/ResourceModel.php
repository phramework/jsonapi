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
 * @method Resource[] get
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
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
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
    }
}
