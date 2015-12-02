<?php

namespace Phramework\JSONAPI;

use \Phramework\Phramework;

class POSTTest extends \PHPUnit_Framework_TestCase
{
    protected $phramework;
    protected $params;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * @todo update base
     */
    protected function prepare()
    {
        //ob_start();
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

        Phramework::setViewer(
            \Phramework\JSONAPI\APP\Viewers\PHPUnit::class
        );

        $that = $this;
        \Phramework\JSONAPI\APP\Viewers\PHPUnit::setCallback(
            function (
                $parameters
            ) use (
                $that
            ) {
                $that->params = $parameters;
            }
        );
        // clean the output buffer
        //ob_clean();
        //$this->phramework->invoke();
        //ob_end_clean();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        //\Phramework\JSONAPI\APP\Viewers\Viewer::release(__CLASS__);
        //foreach ($this->buffer as $line) {
        //    print_r($line);
        //}
    }

    /**
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTSuccess()
    {
        $this->prepare();

        $this->phramework->invoke();

        //Access parameters writen by invoked phramework's viewer
        $params = $this->params;

        $this->assertInternalType('object', $params);

        $this->assertObjectHasAttribute('links', $params);
        $this->assertObjectHasAttribute('data', $params);

        $this->assertInternalType('object', $params->data);
        $this->assertObjectHasAttribute('id', $params->data);

        $this->assertInternalType('string', $params->data->id);

        $this->markTestIncomplete(
            'Use id, to check if relationships are actually saved'
        );

        //$id = $params->data->id;
    }

    /**
     * Cause a not found exception, at to TYPE_TO_ONE relationship
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTFailureToOne()
    {
        $this->prepare();

        //Set a non existing id
        $_POST['data']['relationships']['creator']['data']['id'] = 4235454365434;

        $this->phramework->invoke();

        //Access parameters writen by invoked phramework's viewer
        $params = $this->params;

        $this->assertInternalType('object', $params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            404,
            $params->errors[0]->status,
            'Expect error`s status to be 404, since we caused a not found exception'
        );
    }

    /**
     * Cause a not found exception, at to TYPE_TO_MANY relationship
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTFailureToMany()
    {
        $this->prepare();

        //Set a non existing id
        $_POST['data']['relationships']['tag']['data'][0]['id'] = 4235454365434;

        $this->phramework->invoke();

        //Access parameters writen by invoked phramework's viewer
        $params = $this->params;

        $this->assertInternalType('object', $params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            404,
            $params->errors[0]->status,
            'Expect error`s status to be 404, since we caused a not found exception'
        );
    }
}
