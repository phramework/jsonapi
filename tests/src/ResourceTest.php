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
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\APP\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Phramework\JSONAPI\Resource
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class ResourceTest extends TestCase
{
    public function getAvailableProperties()
    {
        return [
            ['type', Tag::getType()],
            ['links', null],
            ['attributes', null],
            ['relationships', null],
            ['meta', null],
            ['private-attributes', null]
        ];
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        new Resource(
            Article::getType(),
            'id'
        );
    }

    /**
     * @covers ::parseFromRecords
     */
    public function testParseFromRecords(): void
    {
        $articles = Article::get();
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecord(): void
    {
        $tags = Tag::get();

        $articles = Article::get();
    }

    /**
     * @covers ::parseFromRecords
     */
    public function testParseFromRecordsEmpty(): void
    {
        $this->assertEmpty(Tag::collection(null));
        $this->assertEmpty(Tag::collection([]));
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecordEmpty(): void
    {
        $this->assertNull(Tag::resource(null));
        $this->assertNull(Tag::resource([]));
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureModelClass(): void
    {
        Resource::parseFromRecord(
            [
                'id' => '1'
            ],
            self::class
        );
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecordSuccessToOnePreloaded(): void
    {
        Article::resource([
            'id' => '1',
            'creator-user_id' => '1'
        ]);
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecordSuccessToOnePreloadedRelationshipResource(): void
    {
        $creator = RelationshipResource::parseFromRecord(User::$records[0], User::class);

        Article::resource([
            'id' => '1',
            'creator-user_id' => $creator
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordSuccessToOnePreloadedResource(): void
    {
        $creator = User::resource(User::$records[0]);

        Article::resource([
            'id' => '1',
            'creator-user_id' => $creator
        ]);
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecordSuccessToOneUseCallback(): void
    {
        Article::resource([
            'id' => '1'
        ]);
    }

    /**
     * @covers ::parseFromRecord
     */
    public function testParseFromRecordSuccessToManyPreloaded(): void
    {
        Article::resource([
            'id' => '1',
            'tag-id' => ['1', '2']
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureNoIdAttributeSetInRecord(): void
    {
        Tag::resource(['title' => 'true']);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureMetaNotAnObject(): void
    {
        Tag::resource([
            'id' => '1',
            Resource::META_MEMBER => 5
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureMetaNotAnObject2()
    {
        Tag::resource([
            'id' => '1',
            Resource::META_MEMBER => [1, 2, 3]
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureToManyNotAnStringOrResource(): void
    {
        Article::resource([
            'id' => '1',
            'creator-user_id' => new \stdClass()
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureToManyNotAnArray(): void
    {
        $this->markTestIncomplete();
        Article::resource([
            'id' => '1',
            'tag-id' => 5
        ]);
    }

    /**
     * @covers ::parseFromRecord
     * @expectedException \Exception
     */
    public function testParseFromRecordFailureToManyNotAnArrayOfStringsOrAttributes(): void
    {
        $this->markTestIncomplete();
        Article::resource([
            'id' => '1',
            'tag-id' => [new \stdClass(), 5]
        ]);
    }

    /**
     * @covers ::__set
     * @param string $property
     * @dataProvider getAvailableProperties
     */
    public function testSet($property)
    {
        if (in_array($property, ['id', 'type'])) {
            return;
        }

        $resource = new Resource(Tag::getType(), '1');

        $obj = (object) [
            'title' => 'hello'
        ];

        $resource->{$property} = $obj;

        $this->assertEquals($obj, $resource->{$property});
    }

    /**
     * @covers ::__set
     */
    public function testSetId(): void
    {
        $resource = new Resource(Tag::getType(), '1');

        $resource->{'id'} = '2';

        $this->assertEquals('2', $resource->{'id'});
    }

    /**
     * @covers ::__set
     * @param string $property
     * @dataProvider getAvailableProperties
     * @expectedException \Exception
     */
    public function testSetFailure($property)
    {
        $resource = new Resource(Tag::getType(), '1');

        $resource->{'not-found'} = '5';
    }

    /**
     * @covers ::__get
     * @param string $property
     * @param mixed $expected
     * @dataProvider getAvailableProperties
     */
    public function testGet($property, $expected)
    {
        $resource = new Resource(Tag::getType(), '1');

        $this->assertEquals($expected, $resource->{$property});
    }

    /**
     * @covers ::__get
     * @expectedException \Exception
     */
    public function testGetFailure(): void
    {
        $resource = new Resource(Tag::getType(), '1');

        $resource->{'not-found'};
    }
}
