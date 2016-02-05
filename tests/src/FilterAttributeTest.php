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
use Phramework\Models\Operator;

/**
 * @coversDefaultClass Phramework\JSONAPI\FilterAttribute
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FilterAttributeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilterAttribute
     */
    protected $filterAttribute;

    public function setUp()
    {
        $this->filterAttribute = new FilterAttribute(
            'id',
            Operator::OPERATOR_EQUAL,
            '5'
        );
    }

    public function getAvailableProperties()
    {
        return [
            ['attribute', 'id'],
            ['operator', Operator::OPERATOR_EQUAL],
            ['operand', '5']
        ];
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new FilterAttribute(
            'id',
            Operator::OPERATOR_EQUAL,
            '5'
        );
    }

    /**
     * @covers ::parse
     */
    public function testValidate()
    {
        (new FilterTest())->testParseFromParameters();
    }

    /**
     * @covers ::parse
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testValidateFailureAttributeArray()
    {
        (new FilterTest())->testParseFromParametersFailureAttributeIsArray();
    }

    /**
     * @covers ::parse
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testValidateFailureAttributeJSONSecondLevel()
    {
        (new FilterTest())->testParseFromParametersFailureAttributeJSONSecondLevel();
    }

    /**
     * @covers ::__get
     * @param string $property
     * @param mixed  $expected
     * @dataProvider getAvailableProperties
     */
    public function testGet($property, $expected)
    {
        $this->assertSame(
            $this->filterAttribute->{$property},
            $expected
        );
    }

    /**
     * @covers ::__get
     * @expectedException \Exception
     */
    public function testGetFailure()
    {
        $this->filterAttribute->{'not-found'};
    }
}
