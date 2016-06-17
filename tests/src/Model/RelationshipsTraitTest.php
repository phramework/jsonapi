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
namespace Phramework\JSONAPI\APP\Models;

use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\Model\RelationshipsTrait;
use Phramework\JSONAPI\Resource;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\RelationshipsTrait
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class RelationshipsTraitTest extends \PHPUnit_Framework_TestCase
{
    use RelationshipsTrait;

    /**
     * @covers ::getIncludedData
     */
    public function testGetIncludedData()
    {
        $included = static::getIncludedData(
            User::getResourceModel(),
            User::get(), //primary data
            ['group'] //test include relationship
        );

        $this->assertInternalType(
            'array',
            $included
        );

        $this->assertContainsOnlyInstancesOf(
            Resource::class,
            $included
        );
    }

    /**
     * @covers ::getIncludedData
     */
    public function testGetIncludedDataToMany()
    {
        $included = static::getIncludedData(
            User::getResourceModel(),
            User::get(), //primary data
            ['tag'] //test include relationship
        );

        $this->assertInternalType(
            'array',
            $included
        );

        $this->assertContainsOnlyInstancesOf(
            Resource::class,
            $included
        );
    }
}
