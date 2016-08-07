<?php
declare(strict_types=1);
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
 * @coversDefaultClass Phramework\JSONAPI\Directive\IncludeResources
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class IncludeResourcesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IncludeResources
     */
    protected $includeResources;

    public function setUp()
    {
        $this->includeResources = new IncludeResources('group', 'company');
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new IncludeResources();
        new IncludeResources('group', 'company');
    }

    /**
     * @covers ::getInclude
     */
    public function testGetInclude()
    {
        $this->assertSame(
            ['group', 'company'],
            $this->includeResources->getInclude()
        );
    }

    /**
     * @covers ::setInclude
     */
    public function testSetInclude()
    {
        $includeResources = new IncludeResources();
        $includeResources->setInclude('tag', 'group');

        $this->assertSame(
            ['tag', 'group'],
            $includeResources->getInclude()
        );
    }

    /**
     * @covers ::validate
     */
    public function testValidate()
    {
        $this->assertTrue(
            $this->includeResources->validate(
                User::getResourceModel()
            )
        );
    }

    /**
     * @covers ::validate
     * @expectedException \DomainException
     */
    public function testValidateFailure()
    {
        $includeResources = new IncludeResources('tag', 'whatever');

        $this->assertTrue(
            $includeResources->validate(
                User::getResourceModel()
            )
        );
    }

    public function parseProvider()
    {
        return [
            ['tag, group', ['tag', 'group']],
            ['tag',        ['tag']],
            ['',           null]
        ];
    }

    /**
     * @covers ::parseFromRequest
     * @dataProvider parseProvider
     */
    public function testParseFromRequest(
        string $queryParameter,
        $expected = null
    ) {
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            [],
            [],
            [
                'include' => $queryParameter
            ]
        );

        $includeResources = IncludeResources::parseFromRequest(
            $request,
            User::getResourceModel()
        );

        if ($includeResources === null) {
            return;
        }

        $this->assertInstanceOf(
            IncludeResources::class,
            $includeResources
        );

        $this->assertEquals(
            $expected,
            $includeResources->getInclude()
        );
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestUnsetParameter()
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

        $includeResources = IncludeResources::parseFromRequest(
            $request,
            User::getResourceModel()
        );

        $this->assertNull($includeResources);
    }
}
