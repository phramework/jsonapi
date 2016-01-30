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
 * @coversDefaultClass Phramework\JSONAPI\Filter
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $filter = new Filter(
            [1, 2, 3],
            []
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure1()
    {
        $filter = new Filter(
            1
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure2()
    {
        $filter = new Filter(
            [],
            2
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure3()
    {
        $filter = new Filter(
            [],
            [],
            5
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure4()
    {
        $filter = new Filter(
            [],
            [],
            [],
            2
        );
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParameters()
    {
        $parameters = (object) [
            'filter' => [
                'article'   => '1, 2',
                'tag'       => '4, 5, 7',
                'creator'   => '1'
            ]
         ];

        $filter = Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertEquals([1, 2], $filter->primary);
        $this->assertEquals([4, 5, 7], $filter->relationships->tag);
        $this->assertEquals([1], $filter->relationships->creator);
    }
}
