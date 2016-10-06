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
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\ResourceModel;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\Sort
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class SortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRequest
     */
    protected $request;

    /**
     * @var ResourceModel
     */
    protected $model;

    public function setUp()
    {
        $this->model  = (new ResourceModel('user'))
            ->setSortableAttributes(
                'id',
                'title',
                'updated'
            );

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
        $sort = new Sort(
            $this->model->getIdAttribute()
        );
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestEmpty()
    {
        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([]),
            $this->model
        );

        $this->assertNull($sort);
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequest()
    {
        $request = $this->request->withQueryParams([
            'sort' => '-id'
        ]);

        $sort = Sort::parseFromRequest(
            $request,
            $this->model
        );

        $this->assertInstanceOf(
            Sort::class,
            $sort
        );

        $this->assertSame('id', $sort->getAttribute());
        $this->assertFalse($sort->getAscending());

        //Test ascending

        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([
                'sort' => 'id'
            ]),
            $this->model
        );

        $this->assertSame('id', $sort->getAttribute());
        $this->assertTrue($sort->getAscending());
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureNotString()
    {
        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([
                'sort' => ['id']
            ]),
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureParseExpression()
    {
        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([
                'sort' => '--id'
            ]),
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureNotSortable()
    {
        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([
                'sort' => 'meta'
            ]),
            $this->model
        );
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureNoSortableAttributes()
    {
        $sort = Sort::parseFromRequest(
            $this->request->withQueryParams([
                'sort' => 'value'
            ]),
            $this->model
        );
    }
}
