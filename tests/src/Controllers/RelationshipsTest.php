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
 * @coversDefaultClass \Phramework\JSONAPI\Controller\Relationships
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class RelationshipsTest extends TestCase
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

    protected function prepare(): void
    {
        $_SERVER['REQUEST_URI'] = '/article/1/relationships/tag/';
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
     * @covers ::handleByIdRelationships
     */
    public function testGETByIdSuccess(): void
    {
        $this->prepare();

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
        $params = $this->parameters;


        $this->assertIsObject($params);

        $this->assertObjectHasAttribute('links', $params);
        $this->assertObjectHasAttribute('data', $params);

        return;

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
     * @covers ::handleByIdRelationships
     */
    public function testDELETEFailureNotFound(): void
    {
        $this->prepare();

        //Set a non existing id
        $_SERVER['REQUEST_URI'] = '/article/' . 4235454365434 . '/relationships/tag';

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
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
     * @covers ::handleByIdRelationships
     */
    public function testHandleFailure(): void
    {
        $this->prepare();

        $_SERVER['REQUEST_URI'] = '/article/expectingInteger/relationships/tag/';

        $this->phramework->invoke();

        //Access parameters returned by invoked phramework's viewer
        $params = $this->parameters;

        $this->assertIsObject($params);
        $this->assertObjectHasAttribute('errors', $params);

        $this->assertSame(
            422,
            $params->errors[0]->status,
            'Expect error`s status to be 422 because of invalid id'
        );
    }
}
