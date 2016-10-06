<?php
/*
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
namespace Phramework\JSONAPI\Directive;

use Phramework\JSONAPI\APP\Models\User;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\AdditionalRelationshipParameters
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class AdditionalRelationshipsParameterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdditionalRelationshipParameters
     */
    protected $additionalParameters;

    public function setUp()
    {
        $this->additionalParameters = new AdditionalRelationshipParameters(
            (object) [
                'tag'     => [1, 2, 3],
                'company' => [1]
            ]
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new AdditionalRelationshipParameters((object) [
            'tag'     => [1, 2, 3],
            'company' => [1]
        ]);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructNull()
    {
        $additionalParameters = new AdditionalRelationshipParameters();

        $this->assertEquals(
            (object)[],
            $additionalParameters->getRelationshipObjects()
        );
    }

    /**
     * @covers ::getRelationshipObjects
     */
    public function testGetRelationshipObjects()
    {
        $this->assertEquals(
            (object) [
                'tag'     => [1, 2, 3],
                'company' => [1]
            ],
            $this->additionalParameters->getRelationshipObjects()
        );
    }

    /**
     * @covers ::setRelationshipObjects
     */
    public function testSetRelationshipObjects()
    {
        $this->additionalParameters->setRelationshipObjects((object) [
            'company' => [1]
        ]);

        $this->assertEquals(
            (object) [
                'company' => [1]
            ],
            $this->additionalParameters->getRelationshipObjects()
        );
    }

    /**
     * Expect always true
     * @covers ::validate
     */
    public function testParseFromRequest()
    {
        $this->assertTrue(
            $this->additionalParameters->validate(
                User::getResourceModel()
            )
        );
    }

    /**
     * Expect always null
     * @covers ::validate
     */
    public function testValidate()
    {
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            [],
            []
        );

        $this->assertNull(
            AdditionalRelationshipParameters::parseFromRequest(
                $request,
                User::getResourceModel()
            )
        );
    }
}
