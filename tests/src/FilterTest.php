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

use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\APP\Bootstrap;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\Models\Operator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Phramework\JSONAPI\Filter
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FilterTest extends TestCase
{
    public function getAvailableProperties()
    {
        return [
            ['primary', []],
            ['relationships', (object)[]],
            ['attributes', []]
        ];
    }

    public function testConstructFailure1()
    {
        $this->expectException(\Exception::class);

        $filter = new Filter(
            [],
            2
        );
    }

    public function testConstructFailure2()
    {
        $this->expectException(\Exception::class);

        $filter = new Filter(
            [],
            (object) [
                'tag' => new \stdClass() //not an array
            ]
        );
    }

    public function testConstructFailure3()
    {
        $this->expectException(\Exception::class);

        $filter = new Filter(
            [],
            null,
            [
                new \stdClass()
            ]
        );
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersPrimaryUsingIntval(): void
    {
        $parameters = (object) [
            'filter' => (object) [ //Will accept both arrays and object
                'tag' => 4
            ]
        ];

        $filter = Filter::parseFromParameters(
            $parameters,
            Tag::class //Use article resource model's filters
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertEquals([4], $filter->primary);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersRelationshipEmpty(): void
    {
        $parameters = (object) [
            'filter' => (object) [ //Will accept both arrays and object
                'tag' => Operator::OPERATOR_EMPTY
            ]
        ];

        $filter = Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertEquals(Operator::OPERATOR_EMPTY, $filter->relationships->tag);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersEmpty(): void
    {
        $filter = Filter::parseFromParameters(
            [],
            Article::class
        );

        $this->assertNull($filter);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParameters(): void
    {
        $parameters = (object) [
            'filter' => [
                'article'   => '1, 2',
                'tag'       => '4, 5, 7',
                'creator'   => '1',
                'status'    => [true, false],
                'title'     => [
                    Operator::OPERATOR_LIKE . 'blog',
                    Operator::OPERATOR_NOT_LIKE . 'welcome'
                ],
                'updated'   => Operator::OPERATOR_NOT_ISNULL,
                'meta.keywords' => 'blog'
            ]
         ];

        $filter = Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertContainsOnly(
            'string',
            $filter->primary,
            true
        );

        $this->assertIsObject(
            $filter->relationships
        );

        $this->assertIsArray(
            $filter->relationships->tag
        );

        $this->assertContainsOnly(
            'string',
            $filter->relationships->tag,
            true
        );

        $this->assertContainsOnlyInstancesOf(
            FilterAttribute::class,
            $filter->attributes
        );

        $this->assertSame(['1', '2'], $filter->primary);
        $this->assertSame(['4', '5', '7'], $filter->relationships->tag);
        $this->assertSame(['1'], $filter->relationships->creator);

        $this->assertCount(6, $filter->attributes);

        $shouldContain1 = new FilterAttribute('title', Operator::OPERATOR_LIKE, 'blog');
        $shouldContain2 = new FilterJSONAttribute('meta', 'keywords', Operator::OPERATOR_EQUAL, 'blog');

        $found1 = false;
        $found2 = false;

        foreach ($filter->attributes as $filterAttribute) {
            if ($shouldContain1 == $filterAttribute) {
                $found1 = true;
            } elseif ($shouldContain2 == $filterAttribute) {
                $found2 = true;
            }
        }

        $this->assertTrue($found1);
        $this->assertTrue($found2);
    }

    public function testParseFromParametersFailurePrimaryNotString(): void
    {
        $parameters = (object) [
            'filter' => [
                'article'   => [1, 2]
            ]
        ];

        $this->expectException(IncorrectParametersException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );
    }

    public function testParseFromParametersFailurePrimaryToParse(): void
    {
        $parameters = (object) [
            'filter' => [
                'article'   => 10000
            ]
        ];

        $this->expectException(IncorrectParametersException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureRelationshipNotString(): void
    {
        $parameters = (object) [
            'filter' => [
                'tag'   => [1, 2]
            ]
        ];

        $this->expectException(IncorrectParametersException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureNotAllowedAttribute(): void
    {
        $parameters = (object) [
            'filter' => [
                'not-found'   => 1
            ]
        ];

        $this->expectException(RequestException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeWithoutValidator(): void
    {
        $parameters = (object) [
            'filter' => [
                'no-validator'   => 1
            ]
        ];

        $this->expectException(\Exception::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeIsArray(): void
    {
        $parameters = (object) [
            'filter' => [
                'updated'   => [[Operator::OPERATOR_ISNULL]]
            ]
        ];

        $this->expectException(RequestException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );
    }

    public function testParseFromParametersFailureAttributeToParse(): void
    {
        $parameters = (object) [
            'filter' => [
                'updated'   => 'xxxxxasdadas'
            ]
        ];

        $this->expectException(IncorrectParametersException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeNotAllowedOperator(): void
    {
        $parameters = (object) [
            'filter' => [
                'status'   => Operator::OPERATOR_GREATER_EQUAL . '1'
            ]
        ];

        $this->expectException(RequestException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeNotAcceptingJSONOperator(): void
    {
        $parameters = (object) [
            'filter' => [
                'status.ok'   => true
            ]
        ];

        $this->expectException(RequestException::class);

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        )->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeUsingJSONPropertyValidator(): void
    {
        $parameters = (object) [
            'filter' => [
                'meta.timestamp'   => 'xsadas'
            ]
        ];

        $filter = Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );

        $this->expectException(IncorrectParametersException::class);

        $filter->validate(Article::class);
    }

    public function testParseFromParametersFailureAttributeJSONSecondLevel(): void
    {
        $parameters = (object) [
            'filter' => [
                'meta.timestamp.time'   => 123456789
            ]
        ];

        $this->expectException(RequestException::class);

        $filter = Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );



        $filter->validate(Article::class);
    }


    public function testValidate(): void
    {
        $filter = new Filter(
            [],
            (object) [
                'tag' => [1, 2, 3]
            ]
        );

        $filter->validate(Article::class);

        $this->assertTrue(true);
    }

    public function testValidateFailureRelationshipNotFound(): void
    {
        $filter = new Filter(
            [],
            (object) [
                'not-found-relationship' => [1, 2, 3]
            ]
        );

        $this->expectException(RequestException::class);

        $filter->validate(Article::class);
    }

    /**
     * @covers ::__get
     * @param string $property
     * @param mixed $expected
     * @dataProvider getAvailableProperties
     */
    public function testGet($property, $expected)
    {
        $filter = new Filter();

        $this->assertEquals($expected, $filter->{$property});
    }

    public function testGetFailure(): void
    {
        $filter = new Filter();


        $this->expectException(\Exception::class);

        $filter->{'not-found'};
    }
}
