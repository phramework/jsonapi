<?php
/**
 * Copyright 2015 Spafaridis Xenofon
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

/**
 * DELETE
 * @package JSONAPI
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Spafaridis Xenophon <nohponex@gmail.com>
 */
abstract class Base
{

    /**
     * Shortcut to \Phramework\Phramework::view.
     * @uses \Phramework\Phramework::view
     * @param array|object $parameters Response parameters
     * @uses \Phramework\Phramework::view
     */
    protected static function view($parameters = [])
    {
        \Phramework\Phramework::view($parameters);
    }

    /**
     * If !assert then a NotFoundException exceptions is thrown.
     *
     * @param mixed  $assert
     * @param string $exceptionMessage [Optional] Default is
     * 'Resource not found'
     * @throws \Phramework\Exceptions\NotFoundException
     */
    protected static function exists(
        $assert,
        $exceptionMessage = 'Resource not found'
    ) {
        if (!$assert) {
            throw new \Phramework\Exceptions\NotFoundException(
                $exceptionMessage
            );
        }
    }

    /**
     * If !assert then a Exception exception is thrown.
     *
     * @param mixed  $assert
     * @param string $exceptionMessage [Optional] Default is 'unknown_error'
     *
     * @throws Exception
     */
    protected static function testUnknownError(
        $assert,
        $exceptionMessage = 'Unknown error'
    ) {
        if (!$assert) {
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * View JSONAPI data
     * @param stdClass $data
     * @uses \Phramework\Viewers\JSONAPI
     * @todo use \Phramework\Phramework::view
     */
    public static function viewData(
        $data,
        $links = null,
        $meta = null,
        $included = null
    ) {
        $temp = [];

        if ($links) {
            $temp['links'] = $links;
        }

        $temp['data'] = $data;


        if ($included !== null) {
            $temp['included'] = $included;
        }

        if ($meta) {
            $temp['meta'] = $meta;
        }

        \Phramework\Phramework::view($temp);

        unset($temp);
    }

    /**
     * Extract included related resources from parameters
     * @param  array|object $params Request parameters
     * @return null|string[]
     */
    protected static function getRequestInclude($params = [])
    {
        //work with arrays
        if (!is_array($params) && is_object($params)) {
            $params = array($params);
        }

        if (!isset($params['include']) || empty($params['include'])) {
            return [];
        }

        $include = [];

        //split parameter using , (for multiple values)
        foreach (explode(',', $params['include']) as $i) {
            $include[] = trim($i);
        }

        return array_unique($include);
    }

    /**
     * Get request data attributes.
     * The request is expected to have json api structure
     * Like the following example:
     * ```
     * [
     *    data => [
     *        'type' => 'user',
     *        'attributes' => [
     *            'email'    => 'nohponex@gmail.com',
     *            'password' => 'XXXXXXXXXXXXXXXXXX'
     *        ]
     *    ]
     * ]
     * ```
     * @param  array|object $params Request parameters
     * @uses Request::requireParameters
     * @return \stdClass
     */
    protected static function getRequestAttributes($params = [])
    {
        //work with arrays
        if (!is_array($params) && is_object($params)) {
            $params = array($params);
        }

        //Require data
        Request::requireParameters($params, ['data']);

        //Require data['attributes']
        Request::requireParameters($params['data'], ['attributes']);

        return (object)$params['data']['attributes'];
    }

    /**
     * Throw a Forbidden exception if resource's id is set.
     *
     * Unsupported request to create a resource with a client-generated ID
     * @package JSONAPI
     * @throws \Phamework\Phramework\Exceptions\ForbiddenException
     * @param  object $resource [description]
     */
    public static function checkIfUnsupportedRequestWithId($resource)
    {
        if (isset($resource->id)) {
            throw new \Phamework\Phramework\Exceptions\ForbiddenException(
                'Unsupported request to create a resource with a client-generated ID'
            );
        }
    }
}
