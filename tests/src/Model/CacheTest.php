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

use Phramework\JSONAPI\APP\Bootstrap;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\NotCachedModel;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Viewers\JSONAPI;
use Phramework\Phramework;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\Cache
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class CacheTest extends TestCase
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
     * @covers ::initializeCache
     */
    public function testInitializeCache(): void
    {
        Bootstrap::invokeStaticMethod(
            Article::class,
            'getCache',
            '1'
        );
    }

    /**
     * @covers ::setCache
     * @after testInitializeCache
     */
    public function testSetCache(): void
    {
        $article = clone Article::getById('1');

        $article->id = 1000;

        $this->assertTrue(Bootstrap::invokeStaticMethod(
            Article::class,
            'setCache',
            1000,
            $article
        ));

        $articleCached = Bootstrap::invokeStaticMethod(
            Article::class,
            'getCache',
            1000
        );

        $this->assertEquals($article, $articleCached);


        $this->assertFalse(Bootstrap::invokeStaticMethod(
            NotCachedModel::class,
            'setCache',
            '1',
            NotCachedModel::getById('1')
        ), 'Expect false when trying to set cache in a model with disabled caching');
    }

    /**
     * @covers ::getCache
     * @after testInitializeCache
     */
    public function testGetCache(): void
    {
        $this->assertInstanceOf(
            Resource::class,
            Bootstrap::invokeStaticMethod(
                Article::class,
                'getCache',
                '1'
            )
        );

        $this->assertNull(Bootstrap::invokeStaticMethod(
            Article::class,
            'getCache',
            'not-found'
        ));
    }

    /**
     * @covers ::invalidateCache
     * @after testGetCache
     */
    public function testInvalidateCache(): void
    {
        Bootstrap::invokeStaticMethod(
            Article::class,
            'invalidateCache',
            1000
        );

        $articleCached = Bootstrap::invokeStaticMethod(
            Article::class,
            'getCache',
            1000
        );

        $this->assertNull($articleCached);
    }
}
