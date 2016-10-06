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
namespace Phramework\JSONAPI;

use Phramework\JSONAPI\Directive\Directive;

/**
 * Abstract Model, implementation classes must include "use ModelTrait;"
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
abstract class Model
{
    /**
     * @see Model::defineModel Will be invoked if $model is not defined
     * @return ResourceModel
     */
    public static function getResourceModel() : ResourceModel
    {
        if (static::$model === null) {
            static::$model = static::defineModel();
        }

        return static::$model;
    }

    /**
     * MUST be implemented
     * This method is used to define resource Model
     */
    abstract protected static function defineModel() : ResourceModel;

    /**
     * Alias of ResourceModel's getById, used as shortcut
     * @param Directive[] ...$directives
     * @return Resource[]
     * @see ResourceModel::get
     */
    public static function get(Directive ...$directives) : array
    {
        return static::getResourceModel()->get(...func_get_args());
    }

    /**
     * Alias of ResourceModel's getById, used as shortcut
     * @param string       $id
     * @param Directive[] ...$directives
     * @return Resource|null
     * @see ResourceModel::getById
     */
    public static function getById(
        string $id,
        Directive ...$directives
    ) {
        return static::getResourceModel()->getById(...func_get_args());
    }

    /**
     * Alias of ResourceModel's getResourceType, used as shortcut
     * @return string
     * @see ResourceModel::getResourceType
     */
    public static function getResourceType() : string
    {
        return static::getResourceModel()->getResourceType();
    }
}
