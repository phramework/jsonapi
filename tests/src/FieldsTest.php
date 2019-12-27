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
use PHPUnit\Framework\TestCase;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;

/**
 * @coversDefaultClass Phramework\JSONAPI\Fields
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FieldsTest extends TestCase
{
    /**
     * @var Fields
     */
    protected $fields;

    public function setUp(): void
    {
        $this->fields = new Fields();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct1()
    {
        $fieldsInstance = new Fields();

        $this->assertIsObject($fieldsInstance->getFields());
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

        $this->assertIsObject($fields);

        $this->assertObjectHasAttribute(
            Article::getType(),
            $fields
        );

        $this->assertIsArray(
            $fields->{Article::getType()}
        );
    }

    public function testConstructFailure(): void
    {
        $this->expectException(\Exception::class);

        new Fields((object)[
            Article::getType() => 'title' //Must be an array
        ]);
    }

    /**
     * @covers ::add
     */
    public function testAdd(): void
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

    public function testGet(): void
    {
        $this->fields->add(
            Article::getType(),
            ['id', 'title', 'updated']
        );

        $return = $this->fields->get(
            Article::getType()
        );

        $this->assertEquals(
            $return,
            ['id', 'title', 'updated']
        );
    }

    /**
     * @covers ::get
     */
    public function testGetForNotSetResourceType(): void
    {
        $return = $this->fields->get(
            Tag::getType()
        );

        $this->assertSame(
            $return,
            []
        );
    }

    public function testGetFields(): void
    {
        $this->fields->add(Article::getType(), ['id']);
        $fields = $this->fields->get(Article::getType());

        $this->assertNotEmpty($fields);
    }

    public function testParseFromParametersEmpty(): void
    {
        $fields = Fields::parseFromParameters(
            [],
            Article::class
        );

        $this->assertNull($fields);
    }

    public function testParseFromParameters(): void
    {
        $parameters = (object) [
            'fields' => [
                Article::getType() => 'title, updated',
                Tag::getType() => 'title'
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

        $this->assertEquals(['title', 'updated'], $fields->fields->{Article::getType()});
        $this->assertEquals(['title'], $fields->fields->{Tag::getType()});
    }

    public function testParseFromParametersFailureNotAllowed(): void
    {
        $parameters = (object)[
            'fields' => [
                Article::getType() => 'creator-user_id'
            ]
        ];

        $this->expectException(IncorrectParametersException::class);

        $fields = Fields::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    public function testParseFromParametersFailureNotStringValue(): void
    {
        $parameters = (object) [
            'fields' => [
                Article::getType() => ['title, updated']
            ]
        ];

        $this->expectException(\Phramework\Exceptions\IncorrectParametersException::class);

        Fields::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    public function testParseFromParametersFailureNotAllowedResourceType(): void
    {
        $parameters = (object) [
            'fields' => [
                'offset' => 'title'
            ]
        ];

        $this->expectException(\Phramework\Exceptions\RequestException::class);

        Fields::parseFromParameters(
            $parameters,
            Article::class
        );
    }

    /**
     * @covers ::__get
     */
    public function testMagicGet(): void
    {
        $fields = new Fields();

        $this->assertIsObject($fields->fields);
    }

    /**
     * @covers ::__get
     */
    public function testMagicGetFailure(): void
{
        $fields = new Fields();

        $this->expectException(\Exception::class);

        $fields->{'not-found'};
    }
}
