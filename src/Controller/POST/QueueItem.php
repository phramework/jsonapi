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
namespace Phramework\JSONAPI\Controller\POST;

use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;
use \Phramework\JSONAPI\Relationship;

/**
 * QueueItem
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class QueueItem
{
    /**
     * @var object
     */
    private $attributes;

    /**
     * @var object of
     * - callable           $callback
     * - integer[]|string[] $resources
     */
    private $relationships;

    /**
     * @param object $attributes
     * @param object $relationships
     */
    public function __construct(
        $attributes,
        $relationships = null
    ) {
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    /**
     * Get the value of QueueItem
     *
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get the value of Relationships
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return $this->relationships;
    }


    /**
     * Set the value of Relationships
     *
     * @param mixed relationships
     *
     * @return self
     */
    public function setRelationships($relationships)
    {
        $this->relationships = $relationships;

        return $this;
    }
}
