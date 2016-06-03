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

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\Directive
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class DirectiveTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Directive[]
     */
    protected $directives = [];

    public function setUp()
    {
        $this->directives = [
            new Page(),
            new AdditionalParameter([1])
        ];
    }

    /**
     * @covers ::getByClass
     */
    public function testGetByClassNotFound()
    {
        $this->assertNull(
            Directive::getByClass(
                Filter::class,
                $this->directives
            ),
            'Expect null since directive of type Filter is not provided'
        );
    }

    /**
     * @covers ::getByClass
     */
    public function testGetByClass()
    {
        $additionalParameter = Directive::getByClass(
            AdditionalParameter::class,
            $this->directives
        );

        $this->assertInstanceOf(
            AdditionalParameter::class,
            $additionalParameter
        );

        $this->assertSame(
            [1],
            $additionalParameter->getParameters()
        );
    }

    /**
     * @covers ::getByClasses
     */
    public function testGetByClasses()
    {
        list($additionalParameter, $page, $filter) = Directive::getByClasses(
            [
                AdditionalParameter::class,
                Page::class,
                Filter::class
            ],
            $this->directives
        );

        $this->assertInstanceOf(
            AdditionalParameter::class,
            $additionalParameter
        );

        $this->assertSame(
            [1],
            $additionalParameter->getParameters()
        );

        $this->assertInstanceOf(
            Page::class,
            $page
        );

        $this->assertNull(
            $filter,
            'Expect null since directive of type Filter is not provided'
        );
    }
}
