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
 * @coversDefaultClass Phramework\JSONAPI\Sort
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class SortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $sort = new Sort(
            Article::getTable()
        );
    }

    /**
     * @covers ::setDefault
     */
    public function testSetDefault()
    {
        $sort = new Sort(
            Article::getTable()
        );

        $this->assertInstanceOf(
            Sort::class,
            $sort->setDefault(null)
        );

        $this->assertSame(null, $sort->default);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersEmpty()
    {
        $sort = Sort::parseFromParameters(
            [],
            Article::class
        );

        $this->assertNull($sort);
    }
}
