<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
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

use Phramework\Phramework;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Relationship
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class RelationshipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var $model
     */
    protected  $model;
    
    /**
     * @var Relationship
     */
    protected $relationship;

    public function setUp()
    {
        $this->model = new InternalModel('user');
        
        $this->relationship = new Relationship(
            $this->model
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            'tag-id'
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct3()
    {
        $relationship = new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            'tag-id',
            (object) [
                'GET' => function () {}
            ]
        );

        $this->assertEquals(
            (object) [
                'GET' => function () {}
            ],
            $relationship->getCallbacks()
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailure2()
    {
        new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            null,
            (object) ['inv'] //Not callable
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailureCallbackMethodUnset()
    {
        new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            null,
            (object) [function() {}] //Not callable
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailureInvalidCallbackMethod()
    {
        new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            null,
            (object) [
                'HTTPMETODNOTALLOWED' => [
                    $this->model,
                    'getRelationshipByArticle'
                ]
            ]
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailureInvalidMethodNotCallable()
    {
        new Relationship(
            $this->model,
            Relationship::TYPE_TO_ONE,
            null,
            (object) [
                'GET' => [
                    $this->model,
                    'getRelationshipByArticleNotCallable'
                ]
            ]
        );
    }
}
