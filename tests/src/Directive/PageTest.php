<?php
declare(strict_types=1);
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

use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Validate\EnumValidator;
use Phramework\Validate\ObjectValidator;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\Page
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class PageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            [],
            [],
            []
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $page = new Page();
        
        $page = new Page(1, 10);

        $page = new Page(null, 10);
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestEmpty()
    {
        $page = Page::parseFromRequest(
            $this->request->withQueryParams([]),
            new ResourceModel('user')
        );

        $this->assertNull($page);
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequest()
    {
        $page = Page::parseFromRequest(
            $this->request->withQueryParams([
                'page' => [
                    'limit' => '1',
                    'offset' => '10'
                ]
            ]),
            new ResourceModel('user')
        );

        $this->assertInstanceOf(
            Page::class,
            $page
        );

        $this->assertSame(1, $page->getLimit());
        $this->assertSame(10, $page->getOffset());
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureToParseLimit()
    {
        Page::parseFromRequest(
            $this->request->withQueryParams([
                'page' => [
                    'limit' => 'x10'
                ]
            ]),
            new ResourceModel('user')
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureToParseOffset()
    {
        Page::parseFromRequest(
            $this->request->withQueryParams([
                'page' => [
                    'offset' => 'x10'
                ]
            ]),
            new ResourceModel('user')
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestExceedMaximum()
    {
        Page::parseFromRequest(
            $this->request->withQueryParams([
                'page' => [
                    'limit' => '100'
                ]
            ]),
            (new ResourceModel('user'))
                ->setMaxPageLimit(10)
        );
    }

    /**
     * @covers ::getLimit()
     */
    public function testGetLimit()
    {
        $page = new Page(1, 10);

        $this->assertSame(
            1,
            $page->getLimit()
        );

        $page = new Page(null, 10);

        $this->assertNull(
            $page->getLimit()
        );
    }

    /**
     * @covers ::getOffset()
     */
    public function testGetOffset()
    {
        $page = new Page(1);

        $this->assertSame(
            0,
            $page->getOffset()
        );

        $page = new Page(1, 10);

        $this->assertSame(
            10,
            $page->getOffset()
        );
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testValidate()
    {
        $page = new Page(100, 10);

        $page->validate(
            (new ResourceModel('user'))
                ->setMaxPageLimit(10)
        );
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $page = new Page(1, 10);

        $object = json_decode(json_encode($page));

        (new ObjectValidator(
            (object) [
                'limit'  => new EnumValidator([1], true),
                'offset' => new EnumValidator([10], true)
            ],
            ['limit', 'offset'],
            false
        ))->parse($object);
    }
}
