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
namespace Phramework\JSONAPI\Directive;

use Phramework\JSONAPI\APP\Models\User;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\AdditionalParameters
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class AdditionalParametersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdditionalParameters
     */
    protected $additionalParameters;

    public function setUp()
    {
        $this->additionalParameters = new AdditionalParameters('abc', 2);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new AdditionalParameters(1, 2, 'abc');
    }

    /**
     * @covers ::getParameters
     */
    public function testGetParameters()
    {
        $this->assertSame(
            ['abc', 2],
            $this->additionalParameters->getParameters()
        );
    }

    /**
     * @covers ::setParameters
     */
    public function testSetParameters()
    {
        $additionalParameters = new AdditionalParameters('abc' );
        $additionalParameters->setParameters('abc', 'wyz');

        $this->assertSame(
            ['abc', 'wyz'],
            $additionalParameters->getParameters()
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
            AdditionalParameters::parseFromRequest(
                $request,
                User::getResourceModel()
            )
        );
    }
}
