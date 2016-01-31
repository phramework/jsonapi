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

use Exception;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;

/**
 * @coversDefaultClass Phramework\JSONAPI\Fields
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FieldsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Fields
     */
    protected $fields;

    public function setUp()
    {
        $this->fields = new Fields();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct1()
    {
        $fieldsInstance = new Fields();

        $this->assertInternalType('object', $fieldsInstance->getFields());
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct2()
    {
        $fieldsInstance = new Fields((object) [
            Article::getType() => ['title']
        ]);

        $fields = $fieldsInstance->getFields();

        $this->assertInternalType('object', $fields);

        $this->assertObjectHasAttribute(
            Article::getType(),
            $fields
        );

        $this->assertInternalType(
            'array',
            $fields->{Article::getType()}
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure()
    {
        new Fields((object)[
            Article::getType() => 'title' //Must be an array
        ]);
    }

    /**
     * @covers ::add
     * @before testGet
     * @before getFields
     */
    public function testAdd()
    {
        $return = $this->fields->add(
            Article::getType(),
            'id'
        );

        $this->assertInstanceOf(
            Fields::class,
            $return,
            'Expect add method to return self'
        );

        $this->fields->add(
            Article::getType(),
            ['title', 'updated']
        );
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $return = $this->fields->get(
            Article::getType()
        );

        $this->assertSame(
            $return,
            ['id', 'title', 'updated']
        );
    }

    /**
     * @covers ::get
     */
    public function testGetForNotSetResourceType()
    {
        $return = $this->fields->get(
            Tag::getType()
        );

        $this->assertSame(
            $return,
            []
        );
    }

    /**
     * @covers ::getFields
     */
    public function testGetFields()
    {
        $fields = $this->fields->getFields();

        $this->assertInternalType('object', $fields);

        $this->assertObjectHasAttribute(Article::getType(), $fields);
    }


    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersEmpty()
    {
        $fields = Fields::parseFromParameters(
            [],
            Article::class
        );

        $this->assertNull($fields);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParameters()
    {
        $parameters = (object) [
            'fields' => [
                Article::getType() => 'title, updated'
            ]
        ];

        $fields = Fields::parseFromParameters(
            $parameters,
            Article::class
        );

        $this->assertInstanceOf(
            Fields::class,
            $fields
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailureNotStringValue()
    {
        $parameters = (object) [
            'fields' => [
                Article::getType() => ['title, updated']
            ]
        ];

        Fields::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromParametersFailureNotAllowedResourceType()
    {
        $parameters = (object) [
            'fields' => [
                'offset' => 'title'
            ]
        ];

        Fields::parseFromParameters(
            $parameters,
            Article::class
        );
    }
}
