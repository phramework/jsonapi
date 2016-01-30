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

use \Phramework\Phramework;

/**
 * @todo test wrong relationship
 * @todo test validation error
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class POSTTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Phramework
     */
    protected $phramework;

    /**
     * @var object
     */
    protected $params;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {

    }

    protected function prepare()
    {
        return;
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
        return;

        $this->prepare();
        //ob_clean();
        $this->phramework->invoke();

        //ob_end_clean();
        //Access parameters written by invoked phramework's viewer
        $params = $this->params;

        $this->assertInternalType('object', $params);

        $this->assertObjectHasAttribute('links', $params);
        $this->assertObjectHasAttribute('data', $params);

        $this->assertInternalType('object', $params->data);
        $this->assertObjectHasAttribute('id', $params->data);

        $this->assertInternalType('string', $params->data->id);

        $id = $params->data->id;

        //Required, to reinitialize db adapter connection
        $this->prepare();

        $article = \Phramework\JSONAPI\APP\Models\Article::getById($id);

        $this->assertInternalType('object', $article);

        $this->assertObjectHasAttribute('attributes', $article);
        $this->assertObjectHasAttribute('relationships', $article);

        $relationships = $article->relationships;

        $this->assertObjectHasAttribute('creator', $relationships);
        $this->assertObjectHasAttribute('tag', $relationships);

        $this->assertInternalType('object', $relationships->creator->data);
        $this->assertInternalType('array', $relationships->tag->data);

        $this->assertEquals('1', $relationships->creator->data->id);

        $this->assertEquals('2', $relationships->tag->data[0]->id);
        $this->assertEquals('3', $relationships->tag->data[1]->id);
    }

    /**
     * Cause a not found exception, at to TYPE_TO_ONE relationship
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTFailureToOne()
    {
        return;
        $this->prepare();

        //Set a non existing id for creator relationship
        $_POST['data']['relationships']['creator']['data']['id'] = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
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
        return;
        $this->prepare();

        //Set a non existing id for tag relationship
        $_POST['data']['relationships']['tag']['data'][0]['id'] = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
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
