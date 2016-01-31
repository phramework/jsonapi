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

use Gitonomy\Git\Reference\Tag;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Resource;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\Cache
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class CacheTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::getCache
     */
    public function testGetCache()
    {
    }

    /**
     * @covers ::setCache
     */
    public function testSetCache()
    {
        //Get with offset (another resource)
        //$collection = self::get(1, 3);
    }
}