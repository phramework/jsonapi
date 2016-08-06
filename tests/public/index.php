<?php

require __DIR__ . '/../bootstrap.php';
/*
var_dump(
);*/

class Ctrl
{
    use \Phramework\JSONAPI\Controller\Get;
}

// Using the createServer factory, providing it with the various superglobals:
$server = Zend\Diactoros\Server::createServer(
    function ($request, $response, $done) {
        return Ctrl::handleGet(
            $request,
            $response,
            \Phramework\JSONAPI\APP\Models\User::getResourceModel()
        );

        //$response->getBody()->write("Hello world!");
    },
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen();