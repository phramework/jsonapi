<?php
/**
 * Copyright 2015 - 2016 Xenofon Spafaridis
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
 * Fields helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-write string $attribute
 * @property-write string $operator
 * @property-write string $operand
 */
class FilterAttribute
{
    /**
     * @var string
     */
    protected $attribute;
    /**
     * @var string
     */
    protected $operator;
    /**
     * @var string
     */
    protected $operand;

    /**
     * FilterAttribute constructor.
     * @param string $attribute
     * @param string $operator
     * @param string $operand
     */
    public function __construct(
        $attribute,
        $operator,
        $operand
    ) {
        $this->attribute = $attribute;
        $this->operator = $operator;
        $this->operand = $operand;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'operand':
                return $this->operand;
            case 'operator':
                return $this->operator;
            case 'attribute':
                return $this->attribute;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
}
