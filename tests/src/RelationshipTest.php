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

use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Relationship;
use Phramework\Phramework;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Relationship
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class RelationshipTest extends \PHPUnit_Framework_TestCase
{
    public function getAvailableProperties()
    {
        return [
            ['modelClass', Tag::class],
            ['type', Relationship::TYPE_TO_ONE],
            ['recordDataAttribute', null],
            ['callbacks', new \stdClass()],
            ['flags', Relationship::FLAG_DEFAULT]
        ];
    }

    /**
     * @var Relationship
     */
    protected $relationship;

    public function setUp()
    {
        $this->relationship = new Relationship(
            Tag::class
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        new Relationship(
            Tag::class,
            Relationship::TYPE_TO_ONE,
            'tag-id'
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct2()
    {
        new Relationship(
            Tag::class,
            Relationship::TYPE_TO_ONE,
            'tag-id',
            [Tag::class, 'getRelationshipArticle']
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct3()
    {
        new Relationship(
            Tag::class,
            Relationship::TYPE_TO_ONE,
            'tag-id',
            (object) [
                Phramework::METHOD_GET => [Tag::class, 'getRelationshipArticle']
            ]
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailure1()
    {
        new Relationship(
            self::class, //Doesn't extend  Phramework\JSONAPI\Model
            Relationship::TYPE_TO_ONE,
            'tag-id'
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailure2()
    {
        new Relationship(
            Tag::class,
            Relationship::TYPE_TO_ONE,
            null,
            ['inv'] //Not callable
        );
    }

    /**
     * @covers ::__construct
     * @expectedException \Exception
     */
    public function testConstructFailureInvalidCallbackMethod()
    {
        new Relationship(
            Tag::class,
            Relationship::TYPE_TO_ONE,
            null,
            (object) [
                'HTTPMETODNOTALLOWED' => [Tag::class, 'getRelationshipByArticle']
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
            Tag::class,
            Relationship::TYPE_TO_ONE,
            null,
            (object) [
                Phramework::METHOD_GET => [Tag::class, 'getRelationshipByArticleNotCallable']
            ]
        );
    }

    /**
     * @covers ::__get
     * @param string $property
     * @param mixed $expected
     * @dataProvider getAvailableProperties
     */
    public function testGet($property, $expected)
    {
        $this->assertEquals($expected, $this->relationship->{$property});
    }

    /**
     * @covers ::__get
     * @expectedException \Exception
     */
    public function testGetFailure()
    {
        $this->relationship->{'not-found'};
    }

    /**
     * @covers ::__set
     */
    public function testSet()
    {
        $this->relationship->{'flags'} = Relationship::FLAG_INCLUDE_BY_DEFAULT;

        $this->assertSame(Relationship::FLAG_INCLUDE_BY_DEFAULT, $this->relationship->{'flags'});
    }

    /**
     * @covers ::__set
     * @expectedException \Exception
     */
    public function testSetFailure()
    {
        $this->relationship->{'modelClass'} = Tag::class;
    }
}
