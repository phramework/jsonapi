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

use Phramework\JSONAPI\APP\Models\User;
use \Phramework\Phramework;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\PATCH
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class PATCHTest extends TestCase
{
    /**
     * @var Phramework
     */
    protected $phramework;

    /**
     * @var object
     */
    protected $parameters;

    protected function prepare()
    {
        $_SERVER['REQUEST_URI'] = '/article/1';
        $_SERVER['REQUEST_METHOD'] = Phramework::METHOD_PATCH;

        $_POST['data'] = (object) [
            'type' => 'article',
            'id'   => '1',
            'attributes' => [
                'title' => 'omg'
            ],
            'relationships' => (object) [
                'creator' => (object) [
                    'data' => (object) [
                        'type' => 'user',
                        'id' => '1'
                    ]
                ],
                'tag' => (object) [
                    'data' => [
                        (object) [
                            'type' => 'tag',
                            'id' => '3'
                        ],
                        (object) [
                            'type' => 'tag',
                            'id' => '2'
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
                $that->parameters = $parameters;
            }
        );
    }

    /**
     * @covers ::handlePATCH
     */
    public function testPATCHSuccess(): void
    {
        $this->prepare();
        $this->phramework->invoke();

        $parameters = $this->parameters;

        //var_dump($parameters);

        return;

        $this->assertIsObject($parameters);

        $this->assertObjectHasAttribute('links', $parameters);
        $this->assertObjectHasAttribute('data', $parameters);

        $this->assertIsObject($parameters->data);
        $this->assertObjectHasAttribute('id', $parameters->data);

        $this->assertInternalType('string', $parameters->data->id);

        $id = $parameters->data->id;

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
     * @covers \Phramework\JSONAPI\Controller\PATCH::handlePATCH
     */
    public function testPATCHFailureToOne(): void
    {
        $this->prepare();

        //Set a non existing id for creator relationship
        $_POST['data']->relationships->creator->data->id = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
        $params = $this->parameters;

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
     * @covers \Phramework\JSONAPI\Controller\PATCH::handlePATCH
     */
    public function testPATCHFailureToMany(): void
    {
        $this->prepare();

        //Set a non existing id for tag relationship
        $_POST['data']->relationships->tag->data[0]->id = 4235454365434;

        $this->phramework->invoke();

        //Access parameters written by invoked phramework's viewer
        $params = $this->parameters;

        $this->assertIsObject($params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            404,
            $params->errors[0]->status,
            'Expect error`s status to be 404, since we caused a not found exception'
        );
    }
}
