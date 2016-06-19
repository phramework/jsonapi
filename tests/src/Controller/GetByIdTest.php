<?php
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

use Phramework\JSONAPI\APP\Models\User;
use Phramework\JSONAPI\Controller\Controller;
use Phramework\JSONAPI\Resource;
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

    public function testHandleGetById()
    {
        $response = static::handleGetById(
            new ServerRequest(),
            new Response(),
            User::getResourceModel(),
            [],
            '1'
        );

        $this->assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        $this->markTestIncomplete();
    }
}