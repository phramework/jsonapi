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
namespace Phramework\JSONAPI\APP;

use Phramework\JSONAPI\InternalModel;
use Phramework\JSONAPI\Resource;

/**
 * @coversDefaultClass Phramework\JSONAPI\InternalModel
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class InternalModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InternalModel
     */
    protected $model;

    public function setUp()
    {
        $this->model = (new InternalModel('user'));
    }

    /**
     * @covers ::collection
     */
    public function testCollection()
    {
        $records = [
            [
                $this->model->getIdAttribute() => '15',
                'title' => 'Lorem ipsum'
            ]
        ];

        $collection = $this->model->collection(
            $records
        );

        $this->assertInternalType('array', $collection);
        $this->assertCount(1, $collection);

        $resource = $collection[0];

        $this->assertInstanceOf(
            Resource::class,
            $resource
        );

        $this->markTestIncomplete();
    }

    /**
     * @covers ::resource
     */
    public function testResource()
    {
        $record = [
            $this->model->getIdAttribute() => '15',
            'title' => 'Lorem ipsum'
        ];

        $resource = $this->model->resource(
            $record
        );

        $this->assertInstanceOf(
            Resource::class,
            $resource
        );

        $this->markTestIncomplete();
    }
}
