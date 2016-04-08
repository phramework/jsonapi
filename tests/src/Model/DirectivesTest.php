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
namespace Phramework\JSONAPI\Model;

use Exception;
use Phramework\JSONAPI\APP\Bootstrap;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\FilterAttribute;
use Phramework\JSONAPI\FilterJSONAttribute;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\Sort;
use Phramework\Models\Operator;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\Directives
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class DirectivesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::handleSort
     */
    public function testHandleSort()
    {
        $sort = new Sort(
            Tag::getIdAttribute()
        );

        $query = sprintf(
            'SELECT * FROM "%s"
              {{sort}}',
            Tag::getTable()
        );

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleSort',
            $query,
            $sort
        );

        $this->assertInternalType('string', $query);

        $pattern = sprintf(
            '/^SELECT \* FROM "%s"\s+ORDER BY "%s"\."%s" ASC$/',
            Tag::getTable(),
            Tag::getTable(),
            Tag::getIdAttribute()
        );

        $this->assertRegExp($pattern, trim($query));
    }

    /**
     * @covers ::handleSort
     */
    public function testHandleSortNull()
    {
        $sort = new Sort(
            Tag::getIdAttribute()
        );

        $query = sprintf(
            'SELECT * FROM "%s"
              {{sort}}',
            Tag::getTable()
        );

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleSort',
            $query,
            $sort
        );

        $this->assertInternalType('string', $query);

        $pattern = sprintf(
            '/^SELECT \* FROM "%s"\s+ORDER BY "%s" ASC$/',
            Tag::getTable(),
            Tag::getIdAttribute()
        );

        $this->assertRegExp($pattern, trim($query));
    }

    /**
     * @covers ::handlePage
     */
    public function testHandlePage()
    {
        $limit  = 1;
        $offset = 2;

        $page = new Page(
            $limit,
            $offset
        );

        $query = sprintf(
            'SELECT * FROM "%s"
              {{page}}',
            Tag::getTable()
        );

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handlePage',
            $query,
            $page
        );

        $this->assertInternalType('string', $query);

        $pattern = sprintf(
            '/^SELECT \* FROM "%s"\s+LIMIT %s\s+OFFSET %s$/',
            Tag::getTable(),
            $limit,
            $offset
        );

        $this->assertRegExp($pattern, trim($query));
    }

    /**
     * @covers ::handleFields
     */
    public function testHandleFields()
    {
        $additional = 'title';

        $queryTemplate = sprintf(
            'SELECT {{fields}} FROM "%s"',
            Tag::getTable()
        );

        $fields = new Fields((object) [
            Tag::getType() => [Tag::getIdAttribute(), $additional] //idAttribute wont appear twice
        ]);

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleFields',
            $queryTemplate,
            $fields
        );

        $this->assertInternalType('string', $query);

        $pattern = sprintf(
            '/^SELECT "%s",\s*"%s" FROM "%s"$/',
            Tag::getIdAttribute(),
            $additional,
            Tag::getTable()
        );

        $this->assertRegExp($pattern, trim($query));

        //Should be added by default

        $fields = new Fields((object) [
            Tag::getType() => []
        ]);

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleFields',
            $queryTemplate,
            $fields
        );

        $pattern = sprintf(
            '/^SELECT \*,"%s" FROM "%s"$/', //'/^SELECT "%s"\.\*,"%s"\."%s" FROM "%s"$/',
            //Tag::getTable(),
            //Tag::getTable(),
            Tag::getIdAttribute(),
            Tag::getTable()
        );

        $this->assertRegExp($pattern, trim($query));

        //Asterisk

        $fields = new Fields((object) [
            Tag::getType() => ['*']
        ]);

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleFields',
            $queryTemplate,
            $fields
        );

        $pattern = sprintf(
            '/^SELECT \*\s*FROM "%s"$/',
            Tag::getTable()
        );

        $this->assertRegExp($pattern, trim($query));
    }

    /**
     * @covers ::handleGet
     * @todo rewrite
     */
    public function testHandleGet()
    {
        $page = new Page(10, 1);
        $filter = new Filter(
            [1, 2, 3],
            [
                'creator' => [1, 2, 3]
            ],
            [
                //new FilterAttribute('status', Operator::OPERATOR_IN, [1, 2, 3]),
                //new FilterAttribute('status', Operator::OPERATOR_NOT_IN, [4, 5]),
                new FilterAttribute('title', Operator::OPERATOR_LIKE, 'blog'),
                new FilterAttribute('order', Operator::OPERATOR_EQUAL, 5),
                new FilterAttribute('created', Operator::OPERATOR_LESS, time()),
                new FilterAttribute('tag', Operator::OPERATOR_IN_ARRAY, 'blog'),
                new FilterAttribute('tag', Operator::OPERATOR_NOT_IN_ARRAY, 'viral'),
                new FilterAttribute('updated', Operator::OPERATOR_ISNULL),
                new FilterAttribute('created', Operator::OPERATOR_NOT_ISNULL),
                new FilterJSONAttribute('meta', 'keyword', Operator::OPERATOR_EQUAL, 'blog')
                //new FilterJSONAttribute('meta', 'keyword', Operator::OPERATOR_LIKE, 'blog')
            ]
        );

        $sort = new Sort(
            Article::getIdAttribute()
        );

        $fields = new Fields();

        $query = 'SELECT {{fields}}
            FROM "{{table}}"
            WHERE
             "{{table}}"."status" <> \'DISABLED\'
             {{filter}}
             {{sort}}
             {{page}}';

        $query = Bootstrap::invokeStaticMethod(
            Article::class,
            'handleGet',
            $query,
            $page,
            $filter,
            $sort,
            $fields,
            true //query contains WHERE directive
        );

        $this->assertInternalType('string', $query);
    }

    /**
     * @covers ::handleFilter
     */
    public function testHandleFilterViaGet()
    {
        $this->testHandleGet();
    }

    /**
     * @covers ::handleFilter
     */
    public function testHandleFilter()
    {
        $filter = new Filter();

        $query = sprintf(
            'SELECT * FROM "%s"
              {{filter}}',
            Tag::getTable()
        );

        $query = Bootstrap::invokeStaticMethod(
            Tag::class,
            'handleFilter',
            $query,
            $filter
        );

        $this->assertInternalType('string', $query);

        return;
        $pattern = sprintf(
            '/^SELECT \* FROM "%s"\s+LIMIT %s\s+OFFSET %s$/',
            Tag::getTable(),
            $limit,
            $offset
        );

        $this->assertRegExp($pattern, trim($query));
    }
}
