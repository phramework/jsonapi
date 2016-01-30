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

use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * Page helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Page
{
    /**
     * @var int
     */
    public $offset;

    /**
     * @var int|null
     */
    public $limit;

    /**
     * @param int|null $limit
     * @param int $offset
     */
    public function __construct($limit = null, $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }


    /**
     * @param object $parameters Request parameters
     * @return Page|null
     * @throws IncorrectParametersException
     * @todo add default pagination based on $modelClass
     */
    public static function parseFromParameters($parameters, $modelClass)
    {
        if (!isset($parameters->page)) {
            return null;
        }

        $limit  = null;

        $offset = 0;

        //Work with arrays
        if (is_object($parameters->page)) {
            $parameters->page = (array) $parameters->page;
        }

        if (isset($parameters->page['limit'])) {
            $limit =
                (new UnsignedIntegerValidator())
                    ->parse($parameters->page['limit']);
        }

        if (isset($parameters->page['offset'])) {
            $offset =
                (new UnsignedIntegerValidator())
                    ->parse($parameters->page['offset']);
        }

        return new Page($limit, $offset);
    }
}
