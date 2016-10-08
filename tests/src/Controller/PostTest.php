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
namespace Phramework\JSONAPI\Controller;

use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\MissingParametersException;
use Phramework\Exceptions\NotFoundException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\ISource;
use Phramework\Exceptions\Source\Pointer;
use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Group;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\APP\Models\User;
use Phramework\JSONAPI\Controller\Controller;
use Phramework\JSONAPI\CollectionResponse;
use Phramework\JSONAPI\Controller\Helper\RequestBodyQueueTest;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\Model\VariableTraitTest;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Util\Util;
use Phramework\Validate\ObjectValidator;
use Phramework\Validate\StringValidator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\Post
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * Using \Phramework\JSONAPI\APP\Models\Tag model for tests
 */
class PostTest extends \PHPUnit_Framework_TestCase
{
    use Post;

    /**
     * @covers ::handlePost
     */
    public function testHandlePost()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Tag::getResourceType(),
                    'attributes' => (object) [
                        'name' => 'aaaaa'
                    ]
                ]
            ]);

        $response = $this->handlePost(
            $request,
            new Response(),
            Tag::getResourceModel()
        );

        $this->assertSame(
            204,
            $response->getStatusCode()
        );

        $this->markTestIncomplete('test actual resource created');
        $this->markTestIncomplete('test headers');
        $this->markTestIncomplete('test body');
    }

    /**
     * @covers ::defaultPostViewCallback
     */
    public function testDefaultViewCallback()
    {
        $request = $this->getValidTagRequest('abcd');

        $response = $this->defaultPostViewCallback(
            $request,
            new Response(),
            ['1', '2', '3']
        );

        $this->assertSame(
            204,
            $response->getStatusCode()
        );
    }

    /**
     * @covers ::defaultPostViewCallback
     */
    public function testDefaultViewCallbackSingle()
    {
        $request = $this->getValidTagRequest('abcd');

        $response = $this->defaultPostViewCallback(
            $request,
            new Response(),
            ['1']
        );

        $this->assertSame(
            204,
            $response->getStatusCode()
        );

        $this->assertTrue(
            $response->hasHeader('Location')
        );
    }

    /*
     * Missing
     */

    /**
     * @covers ::handlePost
     * @group missing
     */
    public function testMissingPrimaryData()
    {
        $request = (new ServerRequest());

        $this->expectMissing(
            $request,
            ['data'],
            '/',
            Tag::getResourceModel()
        );
    }

    /**
     * @covers ::handlePost
     * @group missing
     */
    public function testMissingType()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                ]
            ]);

        $this->expectMissing(
            $request,
            ['type'],
            '/data',
            Tag::getResourceModel()
        );
    }

    /**
     * Expect exception with missing /data/attributes/name since its required
     * @covers ::handlePost
     * @group missing
     */
    public function testMissingAttributes()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type' => Tag::getResourceType()
                ]
            ]);

        $this->expectMissing(
            $request,
            ['name'],
            '/data/attributes',
            Tag::getResourceModel()
        );
    }

    /*
     * Test bulk
     */

    /**
     * @covers ::handlePost
     * @group bulk
     */
    public function testBulk()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => [
                    (object) [
                        'type' => Tag::getResourceType(),
                        'attributes' => (object) [
                            'name' => 'abcd'
                        ]
                    ],
                    (object) [
                        'type' => Tag::getResourceType(),
                        'attributes' => (object) [
                            'name' => 'abcdef'
                        ]
                    ],
                ]
            ]);

        $response = $this->handlePost(
            $request,
            new Response(),
            Tag::getResourceModel(),
            [],
            null,
            2
        );

        $this->assertSame(
            204,
            $response->getStatusCode()
        );
    }

    /**
     * Expect exception since 2 resources are given with bulk limit of 1
     * also expect exception message to contain word bulk
     * @covers ::handlePost
     * @expectedException \Phramework\Exceptions\RequestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessageRegExp /bulk/
     * @group bulk
     */
    public function testBulkMaximum()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => [
                    (object) [
                        'type' => Tag::getResourceType()
                    ],
                    (object) [
                        'type' => Tag::getResourceType()
                    ],
                ]
            ]);

        $response = $this->handlePost(
            $request,
            new Response(),
            Tag::getResourceModel(),
            [],
            null,
            1 //Set bulk limit of 1
        );
    }

    /*
     * Incorrect parameters
     */

    /**
     * @covers ::handlePost
     * @group incorrect
     */
    public function testUnsupportedRequestWithId()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Tag::getResourceType(),
                    'id'         => md5((string) mt_rand()), //inject unsupported id
                    'attributes' => (object) [
                        'name' => 'aaaaa'
                    ]
                ]
            ]);

        try {
            $this->handlePost(
                $request,
                new Response(),
                Tag::getResourceModel()
            );
        } catch (IncorrectParameterException $e) {
            $this->assertSame(
                'additionalProperties',
                $e->getFailure()
            );

            $this->assertSame(
                '/data',
                $e->getSource()->getPath()
            );

            $this->assertRegExp(
                '/id/',
                $e->getDetail(),
                'Expect detail message to contain "id" word'
            );

        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }

    /**
     * @covers ::handlePost
     * @group incorrect
     */
    public function testIncorrectAttributes()
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Tag::getResourceType(),
                    'attributes' => (object) [
                        'name' => '' //since expecting 2 to 10
                    ]
                ]
            ]);

        try {
            $response = $this->handlePost(
                $request,
                new Response(),
                Tag::getResourceModel()
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
                $e->getFailure()
            );

            $this->assertEquals(
                new Pointer('/data/attributes/name'),
                $e->getSource()
            );
        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }


    /*
     * Validation callback
     */

    /**
     * This test will use pass a validation callback in order to have additional checks
     * @covers ::handlePost
     * @group validationCallbacks
     */
    public function testValidationCallbacksAdditionalException()
    {
        $name = 'aaaaa';

        $request = $this->getValidTagRequest($name);

        try {

            $response = $this->handlePost(
                $request,
                new Response(),
                Tag::getResourceModel(),
                [
                    function (
                        \stdClass $resource,
                        \stdClass $parsedAttributes,
                        \stdClass $parsedRelationships,
                        ISource $source
                    ) use ($name) {
                        (new StringValidator())
                            ->setNot(
                                (new StringValidator())
                                    ->setEnum([$name])
                            )
                            ->setSource(new Pointer(
                                $source->getPath() . '/attributes/name'
                            ))
                            ->parse($parsedAttributes->name);
                    }
                ]
            );
        } catch (IncorrectParameterException $e) {
            $this->assertInstanceOf(
                IncorrectParameterException::class,
                $e
            );

            $this->assertSame(
                'not',
                $e->getFailure()
            );

            $this->assertEquals(
                new Pointer('/data/attributes/name'),
                $e->getSource()
            );
        } catch (\Exception $e) {
            $this->fail('Expected Exception has not been raised');
        }
    }

    /**
     * This test will use pass a validation callback in order to modify attributes
     * @covers ::handlePost
     * @group validationCallbacks
     */
    public function testValidationCallbacksModifyAttributes()
    {
        $name = 'aaaaa';
        $newName = str_repeat($name, 2);

        $request = $this->getValidTagRequest($name);

        $unit = $this;

        $response = $this->handlePost(
            $request,
            new Response(),
            Tag::getResourceModel(),
            [
                function (
                    \stdClass $resource,
                    \stdClass &$parsedAttributes,
                    \stdClass $parsedRelationships,
                    ISource $source
                ) use ($newName) {
                    $parsedAttributes->name = $newName;
                }
            ],
            function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $ids
            ) use ($unit, $newName) : ResponseInterface {
                $data = Tag::getById($ids[0]);

                $unit->assertSame(
                    $newName,
                    $data->attributes->name,
                    'Expect inserted name to have same value with modified instead of original'
                );

                return Post::defaultPostViewCallback(
                    $request,
                    $response,
                    $ids
                );
            }
        );
    }

    /*
     * View callback
     */

    /**
     * This test will use pass a viewCallback in order to have a modified response
     * It will also ensure that status, headers and body can be modified
     * Additionally it will check the structure of body if it's identical to inserted resource
     * @covers ::handlePost
     */
    public function testViewCallback()
    {
        $name = 'aaaaa';

        $request = $this->getValidTagRequest($name);

        $unit = $this;

        $response = $this->handlePost(
            $request,
            new Response(),
            Tag::getResourceModel(),
            [],
            function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $ids
            ) use ($unit) : ResponseInterface {
                $unit->assertCount(
                    1,
                    $ids
                );

                $data = Tag::getById($ids[0]);

                $response = Controller::viewData(
                    $response,
                    $data
                );

                $response = $response
                    ->withStatus(203)
                    ->withAddedHeader(
                        'x-phramework', $ids[0]
                    );

                return $response;
            } //set viewCallback
        );

        $this->assertSame(
            203,
            $response->getStatusCode()
        );

        $this->assertTrue(
            $response->hasHeader('x-phramework')
        );

        $object = json_decode(
            $response->getBody()->__toString()
        );

        /*
         * Test inserted resource structure
         */

        $validate = (new ObjectValidator(
            (object) [
                'data' => new ObjectValidator(
                    (object) [
                        'name' => (new StringValidator())
                            ->setEnum([$name])
                    ],
                    ['type', 'attributes']
                )
            ],
            ['data']
        ))->validate($object);

        $this->assertTrue(
            $validate->status
        );
    }

    /**
     * Expect author id to be in inserted resource
     * @covers ::handlePost
     * @group relationship
     */
    /*public function testRelationshipsToOneSuccess()
    {
        return (new RequestBodyQueueTest())->testRelationshipsToOneSuccess();
    }*/

    /**
     * Expect tag ids to be in inserted resource
     * @covers ::handlePost
     * @group relationship
     */
    /*public function testRelationshipsToManySuccess()
    {
        return (new RequestBodyQueueTest())->testRelationshipsToManySuccess();
    }*/


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

    private function getValidTagRequest(string $name) : ServerRequestInterface
    {
        $request = (new ServerRequest())
            ->withParsedBody((object) [
                'data' => (object) [
                    'type'       => Tag::getResourceType(),
                    'attributes' => (object) [
                        'name' => $name
                    ]
                ]
            ]);

        return $request;
    }
}
