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
 * @coversDefaultClass Phramework\JSONAPI\Fields
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FieldsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $fields1 = new Fields();

        $fields2 = new Fields((object) [
            Article::getType() => 'title'
        ]);
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
