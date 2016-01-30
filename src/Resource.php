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
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Resource
{
    /**
     * Resource's type
     * @var string
     */
    public $type;

    /**
     * *NOTE* The id member is not required when the resource object originates at the client and represents a new resource to be created on the server.
     * @var string
     */
    public $id;

    /**
     * An attributes object representing some of the resource's data
     * @var object
     */
    public $attributes;

    /**
     * A relationships object describing relationships between the resource and other JSON API resources.
     * @var object
     */
    public $relationships;

    /**
     * A links object containing links related to the resource
     * @var object
     */
    public $links;

    /**
     * Non-standard meta-information about a resource that can not be represented as an attribute or relationship.
     * @var object
     */
    public $meta;

    /**
     * Resource constructor.
     * @param string $type
     * @param string $id
     */
    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id   = $id;

        $this->links         = new \stdClass();
        $this->attributes    = new \stdClass();
        $this->relationships = new \stdClass();
        $this->meta          = new \stdClass();
    }
}