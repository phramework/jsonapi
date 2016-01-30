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

use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Relationship;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Relationship
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class RelationshipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Relationship
     */
    protected $relationship;

    public function setUp()
    {
        $this->relationship = new Relationship(
            'tag-id',
            Tag::getType(),
            Relationship::TYPE_TO_ONE,
            Tag::class,
            Tag::getIdAttribute()
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new Relationship(
            'tag-id',
            Tag::getType(),
            Relationship::TYPE_TO_ONE,
            Tag::class,
            Tag::getIdAttribute()
        );
    }

    /**
     * @covers ::getRelationshipType
     */
    public function testGetRelationshipType()
    {
        $this->relationship->getRelationshipType();
    }

    /**
     * @covers ::getAttribute
     */
    public function testGetAttribute()
    {
        $this->relationship->getAttribute();
    }


    /**
     * @covers ::getResourceType
     */
    public function testGetResourceType()
    {
        $this->relationship->getResourceType();
    }

    /**
     * @covers ::getRelationshipClass
     */
    public function testGetRelationshipClass()
    {
        $this->relationship->getRelationshipClass();
    }

    /**
     * @covers ::getRelationshipIdAttribute
     */
    public function testGetRelationshipIdAttribute()
    {
        $this->relationship->getRelationshipIdAttribute();
    }
}
