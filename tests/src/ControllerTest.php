<?php

namespace Phramework\JSONAPI;

use \Phramework\Phramework;

class Controller extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * @todo update base
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testExtends()
    {
        $controller = \Phramework\JSONAPI\Controller::class;

        $classes = [
            \Phramework\JSONAPI\Controller\Base::class,
            \Phramework\JSONAPI\Controller\GET::class
        ];

        foreach ($classes as $class) {
            $this->assertTrue(is_a(
                $controller,
                $class,
                true
            ));
        }
    }
}
