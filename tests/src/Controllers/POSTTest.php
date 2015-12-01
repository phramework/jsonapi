<?php

namespace Phramework\JSONAPI;

use \Phramework\Phramework;

class POSTTest extends \PHPUnit_Framework_TestCase
{
    protected $phramework;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * @todo update base
     */
    protected function setUp()
    {
        $_SERVER['REQUEST_URI'] = '/article/1/';

        $this->phramework = \Phramework\JSONAPI\APP\Bootstrap::prepare();
        $this->phramework->invoke();
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

    }
}
