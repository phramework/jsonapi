<?php

namespace Phramework\JSONAPI;

use Phramework\Models\Operator;

/**
 * Class ResourceModelTest
 * @coversDefaultClass Phramework\JSONAPI\ResourceModel
 */
class ResourceModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceModel
     */
    public static $user;

    /**
     * @var ResourceModel
     */
    public static $administrator;

    /**
     * @covers ::getModel
     * @before testInheritance
     */
    public function testGetModel()
    {
        $model = static::$administrator->getModel();

        $this->assertInstanceOf(
            InternalModel::class,
            $model
        );
    }

    /**
     * Test that a resource model that extends another does actually
     * 1. share untouched directives
     * 2. overwrites reapplied directives
     * @covers ::defineModel
     */
    public function testInheritance()
    {
        $administrator = static::$administrator;
        $user          = static::$user;

        $administratorDefaultDirectives = $administrator
            ::getModel()
            ->getDefaultDirectives();

        $defaultDirectives              = $user
            ::getModel()
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
     * a resource model and the extended model a consistent
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
            'Make sure that does not contains attributes filter, since the extended resource model has none'
        );

        $this->markTestIncomplete('Describe test');
    }

    /**
     * We "create" a special purpose resource containing directive values as attributes
     */
    public static function setUpBeforeClass()
    {
        self::$user = new Class extends ResourceModel{
            use ResourceModelTrait;

            public static function defineModel() : InternalModel
            {
                $model = (new InternalModel('user'))
                    ->setGet(
                        function (IDirective ...$directives) use (&$model) {

                            $definedPassed = new \stdClass();

                            foreach ($directives as $directive) {
                                $definedPassed->{get_class($directive)} = $directive;
                            }

                            /*$directiveAttributes = array_merge(
                                $directives,
                                array_filter(
                                    (array) $model->getDefaultDirectives(),
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
                                    Operator::OPERATOR_EQUAL,
                                    'ENABLED'
                                )
                            ]
                        )
                    );

                return $model;
            }
        };

        self::$administrator = new Class extends ResourceModel
        {
            use ResourceModelTrait;

            public static function defineModel() : InternalModel
            {
                $user = ResourceModelTest::$user;
                $model = $user::defineModel(); //based on User model

                $model = $model->addDefaultDirective(
                    new Page(20),
                    new Filter() //no filter - empty filter, will overwrite previous filter definition
                );

                return $model;
            }
        };
    }
}
