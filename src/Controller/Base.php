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

use Phramework\JSONAPI\Relationship;
use Phramework\Models\Request;
use Phramework\Exceptions\RequestException;

/**
 * Common controller internal methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class Base
{
    /**
     * Shortcut to \Phramework\Phramework::view.
     * @uses \Phramework\Phramework::view
     * @param array|object $parameters Response parameters
     * @uses \Phramework\Phramework::view
     * @deprecated since 1.0.0
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
     * @throws \Exception
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
     * @param object $data
     * @uses \Phramework\Viewers\JSONAPI
     * @return boolean
     */
    public static function viewData(
        $data,
        $links = null,
        $meta = null,
        $included = null
    ) {
        $viewParameters = new \stdClass();

        if ($links) {
            $viewParameters->links = $links;
        }

        $viewParameters->data = $data;

        if ($included !== null) {
            $viewParameters->included = $included;
        }

        if ($meta) {
            $viewParameters->meta = $meta;
        }

        \Phramework\Phramework::view($viewParameters);

        unset($viewParameters);

        return true;
    }

    /**
     * Extract included related resources from parameters
     * @param object $parameters Request parameters
     * @param string|null $modelClass If not null, will add additional include by default from resource model's relationships
     * @return string[]
     */
    protected static function getRequestInclude($parameters, $modelClass = null)
    {
        //work with arrays
        if (!is_object($parameters) && is_array($parameters)) {
            $parameters = (object) $parameters;
        }

        $include = [];

        if ($modelClass !== null) {
            //Add additional include by default from resource model's relationships
            foreach ($modelClass::getRelationships() as $relationshipKey => $relationship) {
                if (($relationship->flags & Relationship::FLAG_INCLUDE_BY_DEFAULT) != 0) {
                    $include[] = $include;
                }
            }
        }

        if (!isset($parameters->include) || empty($parameters->include)) {
            return $include;
        }

        //split parameter using , (for multiple values)
        foreach (explode(',', $parameters->include) as $i) {
            $include[] = trim($i);
        }

        return array_unique($include);
    }

    /**
     * Get request data attributes.
     * The request is expected to have json api structure
     * Like the following example:
     * ```
     * (object) [
     *    data => (object) [
     *        'type' => 'user',
     *        'attributes' => [(object)
     *            'email'    => 'nohponex@gmail.com',
     *            'password' => 'XXXXXXXXXXXXXXXXXX'
     *        ]
     *    ]
     * ]
     * ```
     * @param  object $parameters Request parameters
     * @uses Request::requireParameters
     * @return object
     */
    protected static function getRequestAttributes($parameters)
    {
        //work with objects
        if (is_array($parameters)) {
            $parameters = (object) $parameters;
        }

        //Require data
        Request::requireParameters($parameters, ['data']);

        //Require data attributes
        Request::requireParameters($parameters->data, ['attributes']);

        //work with objects
        if (is_array($parameters->data)) {
            $parameters->data = (object) $parameters->data;
        }

        return (object) $parameters->data->attributes;
    }

    /**
     * Get request primary data
     * @param  object $parameters Request parameters
     * @uses Request::requireParameters
     * @return object|object[]
     */
    protected static function getRequestData($parameters)
    {
        //work with objects
        if (!is_object($parameters) && is_array($parameters)) {
            $parameters = (object) $parameters;
        }

        //Require data
        Request::requireParameters($parameters, ['data']);

        return $parameters->data;
    }

    /**
     * Get request relationships if any attributes.
     * @param  object $parameters Request parameters
     * @return object
     */
    protected static function getRequestRelationships($parameters)
    {
        //work with objects
        if (!is_object($parameters) && is_array($parameters)) {
            $parameters = (object) $parameters;
        }

        //Require data
        Request::requireParameters($parameters, ['data']);

        //Require data['relationships']
        if (isset($parameters->data->relationships)) {
            return (object) $parameters->data->relationships;
        } else {
            return new \stdClass();
        }
    }
}
