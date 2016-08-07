<?php
declare(strict_types=1);
/**
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

use Phramework\JSONAPI\APP\Models\Group;
use Phramework\JSONAPI\APP\Models\User;
use Phramework\JSONAPI\CollectionResponse;
use Phramework\JSONAPI\Controller\Controller;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\ResourceResponse;
use Phramework\Util\Util;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\GetById
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class GetByIdTest extends \PHPUnit_Framework_TestCase
{
    use GetById;

    /**
     * @covers ::handleGetById
     */
    public function testHandleGetById()
    {
        $id = '1';

        $body = $this->getBody(
            new ServerRequest(),
            $id
        );

        $this->assertSame(
            User::getResourceModel()->getResourceType(),
            $body->data->type
        );

        $this->assertSame(
            $id,
            $body->data->id
        );
    }

    /**
     * @covers ::handleGetById
     * @after testHandleGetById
     */
    public function testHandleGetWithInclude()
    {
        $id = '1';

        $request = new ServerRequest();
        $request = $request
            ->withQueryParams(
                [
                    'include' => 'group',
                ]
            );

        $body = $this->getBody(
            $request,
            $id
        );

        $this->assertInternalType(
            'array',
            $body->included
        );

        $this->assertCount(
            1,
            $body->included
        );

        $this->assertSame(
            Group::getResourceModel()->getResourceType(),
            $body->included[0]->type
        );
    }

    protected function checkRequest(RequestInterface $request, string  $id) : ResponseInterface
    {
        $response = static::handleGetById(
            $request,
            new Response(),
            User::getResourceModel(),
            [],
            $id
        );

        $this->assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());

        //todo use regex instead
        $this->assertStringStartsWith(
            'application/vnd.api+json',
            $response->getHeader('Content-Type')[0]
        );

        $body = $response->getBody()->__toString();

        $this->assertTrue(Util::isJSON($body));

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @return ResourceResponse
     */
    protected function getBody(RequestInterface $request, string $id)
    {
        $response = $this->checkRequest($request, $id);

        $body = $response->getBody()->__toString();

        $bodyParsed = (json_decode($body));

        return $bodyParsed;
    }
}