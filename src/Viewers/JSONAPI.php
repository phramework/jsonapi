<?php

namespace Phramework\JSONAPI\Viewers;

/**
 * Implementation of IViewer for JSON API
 *
 * Also sends `Content-Type: application/vnd.api+json;charset=utf-8` header as response
 *
 * JSONP Support is disabled
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @link http://jsonapi.org/
 * @sinse 1.0.0
 */
class JSONAPI implements \Phramework\Viewers\IViewer
{
    /**
     * Send JSON API headers
     * @see header https://secure.php.net/manual/en/function.header.php
     * @return boolean Returns false if headers are already sent, else true
     */
    public static function header()
    {
        if (headers_sent()) {
            return false;
        }

        header('Content-Type: application/vnd.api+json;charset=utf-8');

        return true;
    }

    /**
     * Send output
     *
     * @param object|array $parameters Output to display as json
     */
    public function view($parameters)
    {
        self::header();

        if (!is_object($parameters)) {
            $parameters = (object)$parameters;
        }

        //Include JSON API version object
        $parameters->jsonapi = (object)[
            'version' => '1.0'
        ];

        echo json_encode($parameters);
    }
}
