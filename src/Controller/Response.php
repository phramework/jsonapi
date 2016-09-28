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

use Psr\Http\Message\ResponseInterface;

/**
 * Helper class containing methods for standard HTTP responses
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class Response
{
    /**
     * @param ResponseInterface $response
     * @param string $location
     * @return ResponseInterface
     */
    public static function created(
        ResponseInterface $response,
        string $location = null
    ) : ResponseInterface {
        $response = $response->withStatus(201);

        if (!$location !== null) {
            return $response->withHeader('Location', $location);
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function accepted(
        ResponseInterface $response
    ) : ResponseInterface {
        //todo
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function noContent(
        ResponseInterface $response
    ) : ResponseInterface {
        $response = $response->withStatus(204);

        return $response;
    }
}
