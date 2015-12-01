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
        return;
        ob_start();
        $_SERVER['REQUEST_URI'] = '/article/';
        $_SERVER['REQUEST_METHOD'] = Phramework::METHOD_POST;

        $_POST['data'] = [
            'attributes' => [
                'title' => 'omg'
            ],
            'relationships' => [
                'creator' => [
                    'data' => [
                        'type' => 'user', 'id' => '1'
                    ]
                ],
                'tag' => [
                    'data' => [
                        [
                            'type' => 'tag', 'id' => '3'
                        ],
                        [
                            'type' => 'tag', 'id' => '2'
                        ]
                    ]
                ]
            ]
        ];

        $this->phramework = \Phramework\JSONAPI\APP\Bootstrap::prepare();

        // clean the output buffer
        ob_clean();
        $this->phramework->invoke();
        ob_end_clean();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        \Phramework\JSONAPI\APP\Viewers\Viewer::release(__CLASS__);
    }

    public function testExtends()
    {

    }
}
