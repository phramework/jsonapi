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

use Phramework\JSONAPI\APP\Models\NotCachedModel;
use Phramework\JSONAPI\ValidationModel;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\Validate\ObjectValidator;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getValidationModel
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function testGetValidationModel($modelClass)
    {
        $validationModel = $modelClass::getValidationModel();

        $this->assertInstanceOf(ValidationModel::class, $validationModel);
        $this->assertInstanceOf(ObjectValidator::class, $validationModel->attributes);
    }

    /**
     * @covers ::getFilterValidationModel
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function testFilterValidationModel($modelClass)
    {
        $filterValidator = $modelClass::getFilterValidationModel();

        $this->assertInstanceOf(ObjectValidator::class, $filterValidator);
    }

    /**
     * @covers ::collection
     */
    public function testCollection()
    {
        $resources = Article::collection([
            [
                'id' => 1
            ]
        ]);

        $this->assertInternalType('array', $resources);
        $this->assertTrue(Util::isArrayOf($resources, Resource::class));
    }

    /**
     * @covers ::resource
     */
    public function testResource()
    {
        $resources = Article::resource(
            [
                'id' => 1
            ]
        );

        $this->assertInstanceOf(Resource::class, $resources);
    }

    /**
     * @covers ::getFilterable
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function testGetFilterable($modelClass)
    {
        $fields = $modelClass::getFilterable();

        $this->assertInternalType('object', $fields);

        $this->assertTrue(Util::isArrayOf((array)$fields, 'integer'), 'Expect object of integers');
    }

    /**
     * @covers ::getMutable
     * @dataProvider modelProvider
     */
    public function testGetMutable($modelClass)
    {
        $fields = $modelClass::getMutable();

        $this->assertInternalType('array', $fields);
        $this->assertTrue(Util::isArrayOf($fields, 'string'));
    }

    /**
     * @covers ::getSortable
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function testGetSortable($modelClass)
    {
        $fields = $modelClass::getSortable();

        $this->assertInternalType('array', $fields);
        $this->assertTrue(Util::isArrayOf($fields, 'string'));

    }

    /**
     * @covers ::getFields
     * @param string $modelClass
     * @dataProvider modelProvider
     */
    public function testGetFields($modelClass)
    {
        $fields = $modelClass::getFields();

        $this->assertInternalType('array', $fields);
        $this->assertTrue(Util::isArrayOf($fields, 'string'));

    }

    /**
     * @covers ::getSort
     */
    public function testGetSort()
    {
        $sort = Article::getSort();

        $this->assertInstanceOf(Sort::class, $sort);
    }

    /**
     * @return array[]
     */
    public function modelProvider()
    {
        return [
            [NotCachedModel::class],
            [Article::class]
        ];
    }
}
