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

use Phramework\JSONAPI\APP\Models\Article;

/**
 * @coversDefaultClass Phramework\JSONAPI\Resource
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new Resource(
            Article::getType(),
            'id'
        );
    }

    /**
     * @covers ::isArrayOf
     */
    public function testIsArrayOf()
    {
        $strings = ['1', '10', '612ea09b-934d-4d0a-ac33-f26509feb91c'];

        $resources = [
            new Resource(Article::getType(), '1'),
            new Resource(Article::getType(), '1')
        ];

        $relationshipResources = [
            new RelationshipResource(Article::getType(), '1'),
            new RelationshipResource(Article::getType(), '1')
        ];

        $this->assertTrue(Resource::isArrayOf($strings, 'string'));
        $this->assertTrue(Resource::isArrayOf([], 'string'));
        $this->assertFalse(Resource::isArrayOf($resources, 'string'));

        $this->assertTrue(Resource::isArrayOf($resources, Resource::class));
        $this->assertTrue(Resource::isArrayOf($relationshipResources, Resource::class));
        $this->assertFalse(Resource::isArrayOf($strings, Resource::class));

        $this->assertTrue(Resource::isArrayOf($relationshipResources, RelationshipResource::class));
        $this->assertFalse(Resource::isArrayOf($resources, RelationshipResource::class));
    }
}
