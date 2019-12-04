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

use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Phramework\JSONAPI\Sort
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class SortTest extends TestCase
{
    public function getAvailableProperties()
    {
        return [
            ['table', Article::getTable()],
            ['ascending', true],
            ['attribute', Article::getIdAttribute()]
        ];
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $sort = new Sort(
            Article::getTable(),
            Article::getIdAttribute()
        );
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersEmpty(): void
    {
        $sort = Sort::parseFromParameters(
            [],
            Article::class
        );

        $this->assertEquals(Article::getSort(), $sort);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParameters(): void
    {
        $parameters = (object) [
            'sort' => '-id'
        ];

        $sort = Sort::parseFromParameters(
            $parameters,
            Article::class
        );

        $this->assertInstanceOf(
            Sort::class,
            $sort
        );

        $this->assertSame(Article::getTable(), $sort->table);
        $this->assertSame('id', $sort->attribute);
        $this->assertFalse($sort->ascending);

        //Test ascending
        $parameters = (object) [
            'sort' => 'id'
        ];

        $sort = Sort::parseFromParameters(
            $parameters,
            Article::class
        );

        $this->assertSame('id', $sort->attribute);
        $this->assertTrue($sort->ascending);
    }

    public function testParseFromParametersFailureNotString(): void
    {
        $parameters = (object) [
            'sort' => ['id']
        ];

        $this->expectException(RequestException::class);

        $sort = Sort::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    public function testParseFromParametersFailureParseExpression(): void
    {
        $parameters = (object) [
            'sort' => '--id'
        ];

        $this->expectException(RequestException::class);

        $sort = Sort::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    public function testParseFromParametersFailureNotSortable(): void
    {
        $parameters = (object) [
            'sort' => 'meta'
        ];

        $this->expectException(RequestException::class);

        $sort = Sort::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    public function testParseFromParametersFailureNoSortableAttributes(): void
    {
        $parameters = (object) [
            'sort' => 'id'
        ];


        $this->expectException(RequestException::class);

        $sort = Sort::parseFromParameters(
            $parameters,
            Tag::class
        );
    }

    /**
     * @covers ::__get
     * @param string $property
     * @param mixed $expected
     * @dataProvider getAvailableProperties
     */
    public function testGet($property, $expected)
    {
        $sort = new Sort(
            Article::getTable(),
            Article::getIdAttribute()
        );

        $this->assertSame($expected, $sort->{$property});
    }

    /**
     * @covers ::__get
     * @expectedException \Exception
     */
    public function testGetFailure(): void
    {
        $sort = new Sort(
            Article::getTable(),
            Article::getIdAttribute()
        );

        $sort->{'not-found'};
    }
}
