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
namespace Phramework\JSONAPI\Model;

use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Sort;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\Get
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @afterClass CacheTest
 */
class GetTest extends TestCase
{
    /**
     * Helper method
     * @param int $limit
     * @param int $offset
     * @return Resource
     */
    protected function get($limit = 1, $offset = 0)
    {
        //Get first trend
        $collection = Article::get(
            new Page($limit, $offset)
        );

        return $collection;
    }

    /**
     * @covers ::get
     * @expectedException \Phramework\Exceptions\NotImplementedException
     */
    public function testGet(): void
    {
        Get::get();
    }

    /**
     * Assuming at least one trend exists
     * Assuming trend with id `$notFoundId` does't exist
     * Assuming at least one trend_template exists
     * @covers ::getById
     */
    public function testGetById(): string
{
        //Get 2 first items
        $collection = self::get(2);

        $id = $collection[0]->id;

        //Request single resource by id
        $collectionResource = Article::getById($id);
        //Request again to access cached
        $collectionResource = Article::getById($id);

        $this->assertIsObject($collectionResource);
        $this->assertSame($id, $collectionResource->id);
        $this->assertSame(Article::getType(), $collectionResource->type);

        //test multiple
        $ids = [];

        foreach ($collection as $collectionResource) {
            $ids[] = $collectionResource->id;
        }

        //Fetch multiple resources
        $resources = Article::getById($ids);

        //Request again to access cached
        $resources = Article::getById($ids);

        $this->assertIsObject($resources);

        $resourcesCount = 0;
        foreach ($resources as $r) {
            ++$resourcesCount;
        }

        $this->assertSame(count($ids), $resourcesCount, 'Expect same number of properties as number of ids');

        foreach ($collection as $collectionResource) {
            $resourceId = $collectionResource->id;

            //Property exists
            $this->assertTrue(
                property_exists($resources, (string)$resourceId)
            );

            $resource = $resources->{$resourceId};

            $this->assertIsObject($resource);
            $this->assertSame($resourceId, $resource->id);
            $this->assertSame(Article::getType(), $resource->type);
        }

        //test not existing

        $notFoundId = 9999999999999;

        $resource = Article::getById($notFoundId);

        $this->assertNull($resource, 'Expect resource to be null');

        $ids[] = $notFoundId;

        //Fetch multiple resources
        $resources = Article::getById($ids);

        $this->assertIsObject($resources);

        $resourcesCount = 0;
        foreach ($resources as $r) {
            ++$resourcesCount;
        }

        $this->assertSame(count($ids), $resourcesCount, 'Expect same number of properties as number of ids');

        //Property exists
        $this->assertTrue(
            property_exists($resources, (string)$notFoundId)
        );

        $this->assertNull($resources->{$notFoundId}, 'Expect resource property to be null');

        //Check rest items fom ids
        $resourceId = $ids[0];
        $resource = $resources->{$resourceId};

        $this->assertIsObject($resource);
        $this->assertSame($resourceId, $resource->id);
        $this->assertSame(Article::getType(), $resource->type);

        //Check if trend and trend_template resources aren't messing up cache
        $trendTemplates = Tag::get(new Page(1));

        $trendTemplateId = $trendTemplates[0]->id;

        $trendTemplate = Tag::getById($trendTemplateId);

        $this->assertSame($trendTemplateId, $trendTemplate->id);
        $this->assertSame(Tag::getType(), $trendTemplate->type);

        return $id;
    }

    /**
     * @covers ::parseSort
     */
    public function testParseSort(): void
    {
       $this->assertInstanceOf(Sort::class, Article::parseSort((object) []));
    }

    /**
     * @covers ::parsePage
     */
    public function testParsePage(): void
    {
        $this->assertNull(Article::parsePage((object) []));
    }

    /**
     * @covers ::parseFields
     */
    public function testParseFields(): void
    {
        $this->assertNull(Article::parseFields((object) []));
    }

    /**
     * @covers ::parseFilter
     */
    public function testParseFilter(): void
    {
        $this->assertNull(Article::parseFilter((object) []));
    }
}
