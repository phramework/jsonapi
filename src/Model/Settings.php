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
class Settings
{
    /**
     * @var \stdClass
     */
    protected $settings;

    public function __construct()
    {
        $this->settings = new \stdClass();
    }

    public function add(string $key, $value) {
        $this->settings->{$key} = $value;
    }

    public function get(string $key, $default = null) {
        if (property_exists($this->settings, $key)) {
            return $this->settings;
        }

        return $default;

        /*throw new \Exception(sprintf(
            'key "%s" not found',
            $key
        ));*/
    }

    public function isset(string $key) : bool 
    {
        return property_exists($this->settings, $key);
    }
}