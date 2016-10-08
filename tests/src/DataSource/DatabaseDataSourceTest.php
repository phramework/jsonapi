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
namespace Phramework\JSONAPI\DataSource;

use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\ResourceModel;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @coversDefaultClass Phramework\JSONAPI\DataSource\DatabaseDataSource
 */
class DatabaseDataSourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatabaseDataSource
     */
    protected $dataSource;

    public function setUp()
    {
        $this->dataSource = new DatabaseDataSource(Tag::getResourceModel());
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $dataSource = new DatabaseDataSource(Tag::getResourceModel());

        $this->assertSame(
            Tag::getResourceModel(),
            $dataSource->getResourceModel()
        );
    }

    /**
     * @covers ::requireTableSetting
     */
    public function testRequireTableSettingSuccess()
    {
        $dataSource = new DatabaseDataSource(Tag::getResourceModel());

        $this->assertSame(
            Tag::getResourceModel()->getVariable('table'),
            $dataSource->requireTableSetting()
        );
    }

    /**
     * @covers ::requireTableSetting
     * @expectedException \LogicException
     */
    public function testRequireTableSettingFailure()
    {
        $dataSource = new DatabaseDataSource();

        $model = (new ResourceModel('s', $dataSource));

        $dataSource->requireTableSetting();
    }

    /**
     * @covers ::handleFilter
     */
    public function testHandleFilter()
    {
        $filter = new Filter();

        $q = $this->dataSource->handleGet(
            'SELECT * FROM "table" {{filter}}',
            false,
            [$filter]
        );

        $this->assertInternalType('string', $q);

        $pattern = sprintf(
            '/^SELECT \* FROM "table"\s*$/'
        );

        $this->assertRegExp($pattern, trim($q));
    }

    /**
     * @covers ::handleFilter
     * @covers ::handleGet
     */
    public function testHandleFilter2()
    {
        $filter = new Filter(
            ['1', '2']
        );

        $q = $this->dataSource->handleGet(
            'SELECT * FROM "table" {{filter}}',
            false,
            [$filter]
        );

        $this->assertInternalType('string', $q);

        $pattern = sprintf(
            '/^SELECT \* FROM "table"\s* WHERE "id"\s*IN\s*\(\'1\',\'2\'\)\s*$/'
        );

        $this->assertRegExp($pattern, trim($q));
    }
}
