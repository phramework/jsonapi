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
 * @coversDefaultClass Phramework\JSONAPI\Util
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class UtilTest extends \PHPUnit_Framework_TestCase
{

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

        $this->assertTrue(Util::isArrayOf($strings, 'string'));
        $this->assertTrue(Util::isArrayOf([], 'string'));
        $this->assertFalse(Util::isArrayOf($resources, 'string'));

        $this->assertTrue(Util::isArrayOf($resources, Resource::class));
        $this->assertTrue(Util::isArrayOf($relationshipResources, Resource::class));
        $this->assertFalse(Util::isArrayOf($strings, Resource::class));

        $this->assertTrue(Util::isArrayOf($relationshipResources, RelationshipResource::class));
        $this->assertFalse(Util::isArrayOf($resources, RelationshipResource::class));
    }

    /**
     * @covers ::isArrayAssoc
     */
    public function testIsArrayAssoc()
    {
        $array = [1, 2, 3];

        $arrayAssoc1 = [
            '1' => 1,
            'b' => 2
        ];

        $arrayAssoc2 = [
            0 => 1,
            1 => 2
        ];

        $this->assertFalse(Util::isArrayAssoc($array));

        $this->assertTrue(Util::isArrayAssoc($arrayAssoc1));

        $this->assertFalse(Util::isArrayAssoc($arrayAssoc2));

        $this->assertTrue(Util::isArrayAssoc([]));
    }
}
