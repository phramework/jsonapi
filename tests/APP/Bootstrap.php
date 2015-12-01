<?php
namespace Phramework\JSONAPI\APP;

define('NS', '\\Phramework\\JSONAPI\\APP\\Controllers\\');

use \Phramework\Phramework;

class Bootstrap
{
    public static function prepare()
    {
        $settings = include __DIR__ . '/../../settings.php';

        $URIStrategy = new \Phramework\URIStrategy\URITemplate([
            ['article/',  NS . 'ArticleController', 'GET', Phramework::METHOD_GET],
            ['article/', NS . 'ArticleController', 'POST', Phramework::METHOD_POST],
            ['article/{id}', NS . 'ArticleController', 'GETById', Phramework::METHOD_GET],
            ['article/{id}', NS . 'ArticleController', 'PATCH', Phramework::METHOD_PATCH],
            ['article/{id}', NS . 'ArticleController', 'DELETE', Phramework::METHOD_DELETE],
            ['article/{id}/relationships/{relationship}', NS . 'ArticleController', 'byIdRelationships', Phramework::METHOD_ANY],
        ]);

        //Initialize API
        $phramework = new Phramework($settings, $URIStrategy);

        \Phramework\Database\Database::setAdapter(
            new \Phramework\Database\MySQL($settings['db'])
        );

        Phramework::setViewer(
            //\Phramework\JSONAPI\Viewers\JSONAPI::class
            \Phramework\JSONAPI\APP\Viewers\Viewer::class
        );

        unset($settings);

        return $phramework;

        //Execute API
        //$phramework->invoke();
    }
}
