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
namespace Phramework\JSONAPI\Model;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
trait Variable
{
    /**
     * NOTE needs to be initialized from classes that use it
     * @var \stdClass
     */
    protected $variables;

    /**
     * Set value of a variable
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function addVariable(string $key, $value) {
        $this->variables->{$key} = $value;

        return $this;
    }

    /**
     * Get value of a variable,
     * if it's not set value of the $default argument will be returned
     * @param string $key
     * @param null   $default
     * @return mixed
     */
    public function getVariable(string $key, $default = null) {
        if (property_exists($this->variables, $key)) {
            return $this->variables->{$key};
        }

        return $default;
    }

    /**
     * Check if a variable key is set
     * @param string $key
     * @return bool
     */
    public function issetVariable(string $key) : bool
    {
        return property_exists($this->variables, $key);
    }
}