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

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\GETById
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class GETByIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Phramework
     */
    protected $phramework;

    /**
     * @var object
     * @todo rename if this is response data
     */
    protected $parameters;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    protected function prepare()
    {
        $_SERVER['REQUEST_URI'] = 'article/1';
        $_SERVER['REQUEST_METHOD'] = Phramework::METHOD_GET;

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
     * @covers ::handleGETById
     */
    public function testGETByIdSuccess()
    {
        $this->prepare();

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
        $params = $this->parameters;


        $this->assertInternalType('object', $params);

        $this->assertObjectHasAttribute('links', $params);
        $this->assertObjectHasAttribute('data', $params);

        $this->assertInternalType('object', $params->data);
        $this->assertObjectHasAttribute('id', $params->data);

        return;
        
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
     * @covers \Phramework\JSONAPI\Controller\DELETE::handleDELETE
     */
    public function testDELETEFailureNotFound()
    {
        $this->prepare();

        //Set a non existing id
        $_SERVER['REQUEST_URI'] = '/article/' . 4235454365434;

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
        $params = $this->parameters;

        $this->assertInternalType('object', $params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            404,
            $params->errors[0]->status,
            'Expect error`s status to be 404, since we caused a not found exception'
        );
    }

    /**
     * @covers \Phramework\JSONAPI\Controller\DELETE::handleDELETE
     */
    public function testHandleFailure()
    {
        $this->prepare();

        $_SERVER['REQUEST_URI'] = '/article/expectingInteger';

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
        $params = $this->parameters;

        $this->assertInternalType('object', $params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            422,
            $params->errors[0]->status,
            'Expect error`s status to be 422 because of invalid id'
        );
    }
}
