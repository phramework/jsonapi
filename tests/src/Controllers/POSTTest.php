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
namespace Phramework\JSONAPI\Controller;

use \Phramework\Phramework;
use PHPUnit\Framework\TestCase;

/**
 * @todo test wrong relationship
 * @todo test validation error
 * @coversDefaultClass \Phramework\JSONAPI\Controller\POST
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class POSTTest extends TestCase
{
    /**
     * @var Phramework
     */
    protected $phramework;

    /**
     * @var object
     */
    protected $params;

    protected function prepare(): void
    {
        //ob_start();
        $_SERVER['REQUEST_URI'] = '/article/';
        $_SERVER['REQUEST_METHOD'] = Phramework::METHOD_POST;

        $_POST['data'] = (object) [
            'type' => 'article',
            'attributes' => (object) [
                'title' => 'omg'
            ],
            'relationships' => (object) [
                'creator' => (object) [
                    'data' => (object) [
                        'type' => 'user', 'id' => '1'
                    ]
                ],
                'tag' => (object) [
                    'data' => [
                        (object) [
                            'type' => 'tag', 'id' => '3'
                        ],
                        (object) [
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
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOSTResource
     */
    public function testHandlePOSTResource(): void
    {
        $this->testPOSTSuccess();
    }

    /**
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTSuccess(): void
    {
        $this->prepare();
        //ob_clean();
        $this->phramework->invoke();

        //ob_end_clean();
        //Access parameters written by invoked phramework's viewer
        $params = $this->params;
        return;

        $this->assertIsObject($params);

        $this->assertObjectHasAttribute('links', $params);
        $this->assertObjectHasAttribute('data', $params);

        $this->assertIsObject($params->data);
        $this->assertObjectHasAttribute('id', $params->data);

        $this->assertInternalType('string', $params->data->id);

        $id = $params->data->id;

        //Required, to reinitialize db adapter connection
        $this->prepare();

        $article = \Phramework\JSONAPI\APP\Models\Article::getById($id);

        $this->assertIsObject($article);

        $this->assertObjectHasAttribute('attributes', $article);
        $this->assertObjectHasAttribute('relationships', $article);

        $relationships = $article->relationships;

        $this->assertObjectHasAttribute('creator', $relationships);
        $this->assertObjectHasAttribute('tag', $relationships);

        $this->assertIsObject($relationships->creator->data);
        $this->assertInternalType('array', $relationships->tag->data);

        $this->assertEquals('1', $relationships->creator->data->id);

        $this->assertEquals('2', $relationships->tag->data[0]->id);
        $this->assertEquals('3', $relationships->tag->data[1]->id);
    }

    /**
     * Cause a not found exception, at to TYPE_TO_ONE relationship
     * @covers \Phramework\JSONAPI\Controller\POST::handlePOST
     */
    public function testPOSTFailureToOne(): void
    {
        $this->prepare();

        //Set a non existing id for creator relationship
        $_POST['data']->relationships->creator->data->id = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
        $params = $this->params;

        $this->assertIsObject($params);
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
    public function testPOSTFailureToMany(): void
    {
        $this->prepare();

        //Set a non existing id for tag relationship
        $_POST['data']->relationships->tag->data[0]->id = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
        $params = $this->params;

        $this->assertIsObject($params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            404,
            $params->errors[0]->status,
            'Expect error`s status to be 404, since we caused a not found exception'
        );
    }
}
