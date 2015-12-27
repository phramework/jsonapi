<?php
/**
 * Copyright 2015 Xenofon Spafaridis
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
namespace Phramework\JSONAPI\APP;

define('NS', '\\Phramework\\JSONAPI\\APP\\Controllers\\');

use \Phramework\Phramework;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Bootstrap
{
    /**
     * @return Phramework
     */
    public static function prepare()
    {
        $settings = include __DIR__ . '/../../settings.php';

        $URIStrategy = new \Phramework\URIStrategy\URITemplate([
            ['article/',  NS . 'ArticleController', 'GET', Phramework::METHOD_GET],
            ['article/', NS . 'ArticleController', 'POST', Phramework::METHOD_POST],
            ['article/{id}', NS . 'ArticleController', 'GETById', Phramework::METHOD_GET],
            ['article/{id}', NS . 'ArticleController', 'PATCH', Phramework::METHOD_PATCH],
            ['article/{id}', NS . 'ArticleController', 'DELETE', Phramework::METHOD_DELETE],
            [
                'article/{id}/relationships/{relationship}',
                NS . 'ArticleController',
                'byIdRelationships',
                Phramework::METHOD_ANY
            ],
        ]);

        //Initialize API
        $phramework = new Phramework($settings, $URIStrategy);

        \Phramework\Database\Database::setAdapter(
            new \Phramework\Database\MySQL($settings['database'])
        );

        var_dump($settings);

        Phramework::setViewer(
            \Phramework\JSONAPI\APP\Viewers\Viewer::class
        );

        unset($settings);

        return $phramework;

        //Execute API
        //$phramework->invoke();
    }
}
