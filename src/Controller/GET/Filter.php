<?php
/**
 * Copyright 2015 Xenofon Spafaridis
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
namespace Phramework\JSONAPI\Controller\GET;

/**
 * Filter helper methods
 * @since 0.1.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Filter
{
    public $primary = null;
    public $relationships = [];
    public $attributes = [];
    public $attributesJSON = [];

    public function __construct(
        $primary = null,
        $relationships= [],
        $attributes = [],
        $attributesJSON = []
    ) {

        $this->primary = $primary;
        $this->relationships = $relationships;
        $this->attributes = $attributes;
        $this->attributesJSON = $attributesJSON;
    }

}