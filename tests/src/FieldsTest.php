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
use Phramework\Exceptions\IncorrectParameterException;
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

    /**
     * @var InternalModel
     */
    protected $model;

    public function setUp()
    {
        $this->fields = new Fields();

        $this->model  = (new InternalModel('user'))
            ->setFieldableAtributes(
                'title',
                'updated'
            );
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
            $this->model->getResourceType() => ['title']
        ]);

        $fields = $fieldsInstance->getFields();

        $this->assertInternalType('object', $fields);

        $this->assertObjectHasAttribute(
            $this->model->getResourceType(),
            $fields
        );

        $this->assertInternalType(
            'array',
            $fields->{$this->model->getResourceType()}
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure()
    {
        new Fields((object) [
            $this->model->getResourceType() => 'title' //Must be an array
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
            $this->model->getResourceType(),
            'id'
        );

        $this->assertInstanceOf(
            Fields::class,
            $return,
            'Expect add method to return self'
        );

        $this->fields->add(
            $this->model->getResourceType(),
            'title',
            'updated'
        );
    }

    /**
     * @covers ::get
     */
    public function testGet()
    {
        $return = $this->fields->get(
            $this->model->getResourceType()
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
        $fields = new Fields();

        $return = $fields->get(
            $this->model->getResourceType()
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
        $fields = new Fields();

        $this->assertInternalType('object', $this->fields->getFields());

        $this->assertObjectHasAttribute(
            $this->model->getResourceType(),
            $this->fields->getFields()
        );
    }


    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestEmpty()
    {
        $fields = Fields::parseFromRequest(
            (object) [],
            $this->model
        );

        $this->assertNull($fields);
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequest()
    {
        $parameters = (object) [
            'fields' => [
                $this->model->getResourceType() => 'title, updated',
            ]
        ];

        $fields = Fields::parseFromRequest(
            $parameters,
            $this->model
        );

        $this->assertInstanceOf(
            Fields::class,
            $fields
        );

        $this->assertEquals(
            ['title', 'updated'],
            $fields->getFields()->{$this->model->getResourceType()}
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureTypeNotArrayOrObject()
    {

        $parameters = (object) [
            'fields' =>
                'creator-user_id'

        ];

        $fields = Fields::parseFromRequest(
            $parameters,
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureTypeNotAssociativeArray()
    {
        $parameters = (object) [
            'fields' => [
                'creator-user_id'
            ]
        ];

        $fields = Fields::parseFromRequest(
            $parameters,
            $this->model
        );
    }
    
    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureNotAllowed()
    {
        $parameters = (object) [
            'fields' => [
                $this->model->getResourceType() => 'creator-user_id'
            ]
        ];

        $fields = Fields::parseFromRequest(
            $parameters,
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureResourceValueNotStringValue()
    {
        $parameters = (object) [
            'fields' => [
                $this->model->getResourceType() => ['title, updated']
            ]
        ];

        Fields::parseFromRequest(
            $parameters,
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureNotAllowedResourceType()
    {
        $parameters = (object) [
            'fields' => [
                'offset' => 'title'
            ]
        ];

        Fields::parseFromRequest(
            $parameters,
            $this->model
        );
    }

    /**
     * @covers ::validate
     */
    public function testValidate()
    {
        $this->fields->validate($this->model);

        $this->markTestIncomplete('Must be implemented when method is done');
    }
}
