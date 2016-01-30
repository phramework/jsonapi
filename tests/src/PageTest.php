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

use Phramework\JSONAPI\APP\Models\Article;

/**
 * @coversDefaultClass Phramework\JSONAPI\Page
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class PageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $page = new Page();
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParametersEmpty()
    {
        $page = Page::parseFromParameters(
            [],
            Article::class
        );

        $this->assertNull($page);
    }

    /**
     * @covers ::parseFromParameters
     */
    public function testParseFromParameters()
    {
        $parameters = (object) [
            'page' => [
                'limit' => '1',
                'offset' => '10'
            ]
        ];

        $page = Page::parseFromParameters(
            $parameters,
            Article::class
        );

        $this->assertInstanceOf(
            Page::class,
            $page
        );

        $this->assertSame(1, $page->limit);
        $this->assertSame(10, $page->offset);
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailureToParseLimit()
    {
        $parameters = (object) [
            'page' => (object) [
                'limit' => 'x10'
            ]
        ];

        $page = Page::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    /**
     * @covers ::parseFromParameters
     * @expectedException \Phramework\Exceptions\IncorrectParametersException
     */
    public function testParseFromParametersFailureToParseOffset()
    {
        $parameters = (object) [
            'page' => [
                'offset' => 'xx10'
            ]
        ];

        $page = Page::parseFromParameters(
            $parameters,
            Article::class
        );
    }
}
