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
namespace Phramework\JSONAPI\APP\Controllers;

use \Phramework\Phramework;
use \Phramework\JSONAPI\APP\Models\Article;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class ArticleController extends \Phramework\JSONAPI\Controller
{
    public static function GET($params, $method, $headers)
    {
        return self::handleGET(
            $params,
            Article::class,
            [],
            [],
            true
        );
    }

    public static function GETById($params, $method, $headers, $id)
    {
        $id = \Phramework\Validate\UnsignedIntegerValidator::parseStatic($id);

        return self::handleGETById(
            $params,
            $id,
            Article::class,
            [],
            []
        );
    }

    public static function POST($params, $method, $headers)
    {
        return self::handlePOST(
            $params,
            $method,
            $headers,
            Article::class
        );
    }

    public static function PATCH($params, $method, $headers, $id)
    {
        $id = \Phramework\Validate\UnsignedIntegerValidator::parseStatic($id);

        return self::handlePATCH(
            $params,
            $method,
            $headers,
            $id,
            Article::class
        );
    }

    public static function DELETE($params, $method, $headers, $id)
    {
        $id = \Phramework\Validate\UnsignedIntegerValidator::parseStatic($id);

        return self::handleDELETE(
            $params,
            $method,
            $headers,
            $id,
            Article::class
        );
    }

    /**
     * Manage resource's relationships
     * `/article/{id}/relationships/{relationship}` handler
     * @param  array  $params  Request parameters
     * @param  string $method  Request method
     * @param  array $headers  Request headers
     */
    public static function byIdRelationships($params, $method, $headers, $id, $relationship)
    {
        $id = \Phramework\Validate\UnsignedIntegerValidator::parseStatic($id);

        parent::handleByIdRelationships(
            $params,
            $method,
            $headers,
            $id,
            $relationship,
            Article::class,
            [\Phramework\Phramework::METHOD_GET],
            [],
            []
        );
    }
}
