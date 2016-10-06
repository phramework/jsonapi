<?php
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
namespace Phramework\JSONAPI;

use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\Directive\FilterAttribute;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\Directive\Sort;
use Phramework\Operator\Operator;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @coversDefaultClass Phramework\JSONAPI\Model
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Model
     */
    public static $user;

    /**
     * @var Model
     */
    public static $administrator;

    /**
     * @covers ::getResourceModel
     * @before testInheritance
     */
    public function testGetModel()
    {
        $model = static::$administrator->getResourceModel();

        $this->assertInstanceOf(
            ResourceModel::class,
            $model
        );
    }

    /**
     * Test that a resource articleModel that extends another does actually
     * 1. share untouched directives
     * 2. overwrites reapplied directives
     * @covers ::defineModel
     */
    public function testInheritance()
    {
        $administrator = static::$administrator;
        $user          = static::$user;

        $administratorDefaultDirectives = $administrator
            ::getResourceModel()
            ->getDefaultDirectives();

        $defaultDirectives              = $user
            ::getResourceModel()
            ->getDefaultDirectives();

        /**
         * 1.
         */

        $this->assertEquals(
            'email',
            $administratorDefaultDirectives->{Sort::class}->getAttribute()
        );

        $this->assertTrue(
            $administratorDefaultDirectives->{Sort::class}->getAscending()
        );

        $this->assertEquals(
            'email',
            $defaultDirectives->{Sort::class}->getAttribute()
        );

        $this->assertTrue(
            $defaultDirectives->{Sort::class}->getAscending()
        );

        /**
         * 2.
         */

        $this->assertSame(
            20,
            $administratorDefaultDirectives->{Page::class}->getLimit()
        );

        $this->assertSame(
            10,
            $defaultDirectives->{Page::class}->getLimit()
        );
        
        $this->markTestIncomplete();
    }

    /**
     * @covers ::getResourceType
     */
    public function getResourceType()
    {
        $user          = static::$user;
        $administrator = static::$administrator;

        $this->assertSame(
            'user',
            $user::getResourceType()
        );

        $this->assertSame(
            'user',
            $administrator::getResourceType()
        );
    }

    /**
     * This test is to make sure passed directives and default directives of
     * a resource articleModel and the extended articleModel a consistent
     * @covers ::get
     */
    public function testGet()
    {
        $id = '1';
        $userModel          = static::$user;
        $administratorModel = static::$administrator;

        /**
         * @var Resource
         */

        $user          = $userModel::getById($id);
        $administrator = $administratorModel::getById($id);

        $this->assertSame(
            'user',
            $user->type
        );

        $this->assertSame(
            $id,
            $user->id
        );

        //Passed
        $this->assertSame(
            1,
            $user->attributes->{Page::class}->getLimit()
        );

        //Untouched default
        $this->assertSame(
            'email',
            $user->attributes->{Sort::class}->getAttribute()
        );

        $this->assertEquals(
            [$id],
            $user->attributes->{Filter::class}->getPrimary(),
            'Make sure that primary filter contains only the requested id, enforced by getById'
        );

        //Passed merged, untouched default (test merge functionality of filter)
        $this->assertCount(
            1,
            $user->attributes->{Filter::class}->getAttributes(),
            'Make sure that attributes filter contains only the defined default attribute filter'
        );

        //Passed merged, extended default
        $this->assertCount(
            0,
            $administrator->attributes->{Filter::class}->getAttributes(),
            'Make sure that does not contains attributes filter, since the extended resource articleModel has none'
        );

        $this->markTestIncomplete('Describe test');
    }

    /**
     * We "create" a special purpose resource containing directive values as attributes
     */
    public static function setUpBeforeClass()
    {
        self::$user = new Class extends Model {
            use ModelTrait;

            public static function defineModel() : ResourceModel
            {
                $model = (new ResourceModel('user'))
                    ->setGet(
                        function (Directive ...$directives) use (&$model) {

                            $definedPassed = new \stdClass();

                            foreach ($directives as $directive) {
                                $definedPassed->{get_class($directive)} =
                                    $directive;
                            }

                            /*$directiveAttributes = array_merge(
                                $directives,
                                array_filter(
                                    (array) $articleModel->getDefaultDirectives(),
                                    //remove already defined in passed
                                    function ($directive) use ($definedPassed) {
                                        return !in_array(
                                            get_class($directive),
                                            $definedPassed
                                        );
                                    }
                                )
                            );*/

                            $directiveAttributes = array_merge(
                                (array) $model->getDefaultDirectives(),
                                (array) $definedPassed
                            );

                            $records = [
                                array_merge(
                                    ['id' => '1'],
                                    $directiveAttributes
                                )
                            ];

                            return $model->collection($records);
                        }
                    )->addDefaultDirective(
                        new Page(10),
                        new Sort('email'),
                        new Filter(
                            [],
                            null,
                            [
                                new FilterAttribute(
                                    'status',
                                    Operator::EQUAL,
                                    'ENABLED'
                                )
                            ]
                        )
                    );

                return $model;
            }
        };

        self::$administrator = new Class extends Model
        {
            use ModelTrait;

            public static function defineModel() : ResourceModel
            {
                $user = ModelTest::$user;
                $model = $user::defineModel(); //based on User articleModel

                $model = $model->addDefaultDirective(
                    new Page(20),
                    new Filter() //no filter - empty filter, will overwrite previous filter definition
                );

                return $model;
            }
        };
    }
}
