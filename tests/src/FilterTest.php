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

use Phramework\JSONAPI\APP\Bootstrap;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\Models\Operator;

/**
 * @coversDefaultClass Phramework\JSONAPI\Filter
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FilterTest extends \PHPUnit_Framework_TestCase
{
    public function getAvailableProperties()
    {
        return [
            ['primary', []],
            ['relationships', (object)[]],
            ['attributes', []]
        ];
    }

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
    public function testConstructFailure2()
    {
        $filter = new Filter(
            [],
            2
        );
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersPrimaryUsingIntval()
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
    public function testParseFromParametersEmpty()
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
    public function testParseFromParameters()
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

        $this->assertInternalType(
            'object',
            $filter->relationships
        );

        $this->assertInternalType(
            'array',
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

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailurePrimaryNotString()
    {
        $parameters = (object) [
            'filter' => [
                'article'   => [1, 2]
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            Article::class //Use article resource model's filters
        );
    }


    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailurePrimaryToParse()
    {
        $parameters = (object) [
            'filter' => [
                'article'   => 10000
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureRelationshipNotString()
    {
        $parameters = (object) [
            'filter' => [
                'tag'   => [1, 2]
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureNotAllowedAttribute()
    {
        $parameters = (object) [
            'filter' => [
                'not-found'   => 1
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException Exception
     */
    public function testParseFromParametersFailureAttributeWithoutValidator()
    {
        $parameters = (object) [
            'filter' => [
                'no-validator'   => 1
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureAttributeIsArray()
    {
        $parameters = (object) [
            'filter' => [
                'updated'   => [[Operator::OPERATOR_ISNULL]]
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailureAttributeToParse()
    {
        $parameters = (object) [
            'filter' => [
                'updated'   => 'xxxxxasdadas'
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureAttributeNotAllowedOperator()
    {
        $parameters = (object) [
            'filter' => [
                'status'   => Operator::OPERATOR_GREATER_EQUAL . '1'
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureAttributeNotAcceptingJSONOperator()
    {
        $parameters = (object) [
            'filter' => [
                'status.ok'   => true
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailureAttributeUsingJSONPropertyValidator()
    {
        $parameters = (object) [
            'filter' => [
                'meta.timestamp'   => 'xsadas'
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureAttributeJSONSecondLevel()
    {
        $parameters = (object) [
            'filter' => [
                'meta.timestamp.time'   => 123456789
            ]
        ];

        Filter::parseFromParameters(
            $parameters,
            APP\Models\Article::class //Use article resource model's filters
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
        $filter = new Filter();

        $this->assertEquals($expected, $filter->{$property});
    }

    /**
     * @covers ::__get
     * @expectedException \Exception
     */
    public function testGetFailure()
    {
        $filter = new Filter();

        $filter->{'not-found'};
    }
}
