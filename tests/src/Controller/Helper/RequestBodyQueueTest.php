<?php
declare(strict_types=1);
/*
 * Copyright 2015-2016 Xenofon Spafaridis
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
namespace Phramework\JSONAPI\Controller\Helper;

use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\MissingParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\ISource;
use Phramework\Exceptions\Source\Pointer;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\APP\Models\User;
use Phramework\JSONAPI\Controller\Post;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\Helper\RequestBodyQueue
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * Using \Phramework\JSONAPI\APP\Models\Tag model for tests
 */
class RequestBodyQueueTest extends \PHPUnit_Framework_TestCase
{
    use Post;

    /**
     * Expect missing relationships author
     * @covers ::handleResource
     * @group relationships
     * @group missing
     */
    public function testMissingRelationships()
    {
        //omit relationships
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Article::getResourceType(),
                    'attributes' => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ]
                ]
            ]);

        $this->expectMissing(
            $request,
            ['author'],
            '/data/relationships',
            Article::getResourceModel()
        );
    }

    /**
     * Expect missing relationships author
     * @covers ::handleResource
     * @group relationships
     * @group missing
     */
    public function testMissingRelationshipsData()
    {
        //omit relationships
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Article::getResourceType(),
                    'attributes' => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ],
                    'relationships' => (object) [
                        'author' => (object) []
                    ]
                ]
            ]);

        $this->expectMissing(
            $request,
            ['data'],
            '/data/relationships/author',
            Article::getResourceModel()
        );
    }

    /**
     * Expect missing relationships author
     * @covers ::handleResource
     * @group relationships
     * @group missing
     */
    public function testMissingRelationshipsDataIdType()
    {
        //omit relationships
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Article::getResourceType(),
                    'attributes' => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ],
                    'relationships' => (object) [
                        'author' => (object) [
                            'data' => (object) [ //empty
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectMissing(
            $request,
            ['id', 'type'],
            '/data/relationships/author/data',
            Article::getResourceModel()
        );
    }

    /**
     * @covers ::handleResource
     * @group relationships
     * @group incorrect
     */
    public function testRelationshipsIncorrectId()
    {
        $request = $this->getArticleRequest('', User::getResourceType());

        try {
            $this->handlePost(
                $request,
                new Response(),
                Article::getResourceModel()
            );
        } catch (IncorrectParametersException $e) {
            $this->assertCount(
                1,
                $e->getExceptions()
            );

            /**
             * @var IncorrectParameterException
             */
            $e = $e->getExceptions()[0];

            $this->assertInstanceOf(
                IncorrectParameterException::class,
                $e
            );

            $this->assertSame(
                'minLength',
                $e->getFailure(),
                'Expect minLength since empty string is given'
            );

            $this->assertEquals(
                new Pointer('/data/relationships/author/data/id'),
                $e->getSource()
            );
        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }

    /**
     * @covers ::handleResource
     * @group relationships
     * @expectedException \Phramework\Exceptions\NotFoundException
     * @expectedExceptionCode 404
     * @expectedExceptionMessageRegExp /user/
     */
    public function testRelationshipsNotFound()
    {
        $request = $this->getArticleRequest(md5('abcd'), User::getResourceType());

        $this->handlePost(
            $request,
            new Response(),
            Article::getResourceModel()
        );
    }

    /**
     * @covers ::handleResource
     * @group relationship
     * @group incorrect
     */
    public function testRelationshipsIncorrectType()
    {
        $request = $this->getArticleRequest('1', Tag::getResourceType());

        try {
            $this->handlePost(
                $request,
                new Response(),
                Article::getResourceModel()
            );
        } catch (IncorrectParametersException $e) {
            $this->assertCount(
                1,
                $e->getExceptions()
            );

            /**
             * @var IncorrectParameterException
             */
            $e = $e->getExceptions()[0];

            $this->assertInstanceOf(
                IncorrectParameterException::class,
                $e
            );

            $this->assertSame(
                'enum',
                $e->getFailure(),
                'Expect not expected type is given'
            );

            $this->assertEquals(
                new Pointer('/data/relationships/author/data/type'),
                $e->getSource()
            );
        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }

    /**
     * Expect author id to be in inserted resource
     * @covers ::handleResource
     * @covers \Phramework\JSONAPI\Controller\Post::handlePost
     * @group relationship
     */
    public function testRelationshipsToOneSuccess()
    {
        $user = User::get(new Page(1))[0];

        $request = $this->getArticleRequest($user->id, User::getResourceType());

        $unit = $this;

        $this->handlePost(
            $request,
            new Response(),
            Article::getResourceModel(),
            [
                function (
                    \stdClass $resource,
                    \stdClass $parsedAttributes,
                    \stdClass $parsedRelationships,
                    ISource $source
                ) use ($unit, $user) {
                    $unit->assertSame(
                        $user->id,
                        $parsedAttributes->{'creator-user_id'}
                    );

                    $unit->assertSame(
                        $user->id,
                        $parsedRelationships->{'author'}
                    );
                }
            ],
            function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $ids
            ) use ($unit, $user) : ResponseInterface {
                $data = Article::getById($ids[0]);

                $this->assertSame(
                    $user->id,
                    $data->relationships->author->data->id
                );

                return Post::defaultPostViewCallback(
                    $request,
                    $response,
                    $ids
                );
            }
        );
    }

    /**
     * Expect author id to be in inserted resource
     * @covers ::handleResource
     * @covers \Phramework\JSONAPI\Controller\Post::handlePost
     * @group relationship
     */
    public function testRelationshipsToManySuccess()
    {
        $user = User::get(new Page(1))[0];

        $tags = Tag::get(new Page(2));

        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'          => Article::getResourceType(),
                    'attributes'    => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ],
                    'relationships' => (object) [
                        'author' => (object) [
                            'data' => (object) [
                                'id'   => $user->id,
                                'type' => User::getResourceType()
                            ]
                        ],
                        'tag' => (object) [
                            'data' => [
                                (object) [
                                    'id'   => $tags[0]->id,
                                    'type' => Tag::getResourceType()
                                ],
                                (object) [
                                    'id'   => $tags[1]->id,
                                    'type' => Tag::getResourceType()
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $unit = $this;

        $this->handlePost(
            $request,
            new Response(),
            Article::getResourceModel()
            /*[
                function (
                    \stdClass $resource,
                    \stdClass $parsedAttributes,
                    \stdClass $parsedRelationships,
                    ISource $source
                ) use ($unit, $user) {
                    $unit->assertSame(
                        $user->id,
                        $parsedAttributes->{'creator-user_id'}
                    );

                    $unit->assertSame(
                        $user->id,
                        $parsedRelationships->{'author'}
                    );
                }
            ],*/
            /*function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $ids
            ) use ($unit, $user) : ResponseInterface {
                $data = Article::getById($ids[0]);

                $this->assertSame(
                    $user->id,
                    $data->relationships->author->data->id
                );

                return Post::defaultPostViewCallback(
                    $request,
                    $response,
                    $ids
                );
            }*/
        );

        $this->markTestIncomplete('must check for execution-insertion of tag data');
    }

    /**
     * @covers ::handleResource
     * @group relationship
     * @expectedException \Phramework\Exceptions\RequestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessageRegExp /Relationship/i
     * @expectedExceptionMessageRegExp /abcd/i
     */
    public function testRelationshipsNotDefinedException()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'          => Article::getResourceType(),
                    'attributes'    => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ],
                    'relationships' => (object) [
                        'abcd' => (object) [
                        ]
                    ]
                ]
            ]);

        $this->handlePost(
            $request,
            new Response(),
            Article::getResourceModel()
        );
    }

    /*
     * Helper methods area
     */

    /**
     * Helper method to assert missing parameters
     * @param ServerRequestInterface $request
     * @param array                  $missingParameters
     * @param string                 $pointerPath
     */
    private function expectMissing(
        ServerRequestInterface $request,
        array $missingParameters,
        string $pointerPath,
        ResourceModel $resourceModel
    ) {
        try {
            $response = $this->handlePost(
                $request,
                new Response(),
                $resourceModel
            );
        } catch (MissingParametersException $e) {
            $this->assertEquals(
                $missingParameters,
                $e->getParameters()
            );

            $this->assertEquals(
                new Pointer($pointerPath),
                $e->getSource()
            );
        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }

    private function getArticleRequest(
        string $authorId,
        string $authorType
    ) : ServerRequestInterface {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'          => Article::getResourceType(),
                    'attributes'    => (object) [
                        'title'  => 'abcd',
                        'body'   => 'abcdef',
                        'status' => 1
                    ],
                    'relationships' => (object) [
                        'author' => (object) [
                            'data' => (object) [
                                'id'   => $authorId,
                                'type' => $authorType
                            ]
                        ]
                    ]
                ]
            ]);

        return $request;
    }
}
