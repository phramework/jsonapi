<?php

namespace Phramework\JSONAPI\Controller;

use Psr\Http\Message\ResponseInterface;

class Response
{
    /**
     * @param string $location
     * @return ResponseInterface
     */
    public static function created(
        string $location = null
    ) : ResponseInterface {
        $response = new \Zend\Diactoros\Response(
            'php://memory',
            201
        );

        if (!$location !== null) {
            return $response->withHeader('Location', $location);
        }

        return $response;
    }

    public static function accepted() : ResponseInterface
    {
        //todo
    }

    /**
     * @return ResponseInterface
     */
    public static function noContent() : ResponseInterface
    {
        $response = new \Zend\Diactoros\Response(
            'php://memory',
            204
        );

        return $response;
    }

}
