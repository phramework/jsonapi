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
namespace Phramework\JSONAPI\Controller\Helper;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class ResourceQueueItem
{
    /**
     * @var \stdClass
     */
    protected $attributes;

    /**
     * @var \stdClass
     */
    protected $relationships;

    /**
     * ResourceQueueItem constructor.
     * @param \stdClass $attributes
     * @param \stdClass $relationships
     */
    public function __construct(
        \stdClass $attributes,
        \stdClass $relationships
    ) {
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    /**
     * @return \stdClass
     */
    public function getAttributes() : \stdClass
    {
        return $this->attributes;
    }

    /**
     * @return \stdClass
     */
    public function getRelationships() : \stdClass
    {
        return $this->relationships;
    }
}
