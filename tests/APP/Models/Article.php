<?php
declare(strict_types=1);
/*
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
namespace Phramework\JSONAPI\APP\Models;

use Phramework\JSONAPI\APP\DataSource\MemoryDataSource;
use Phramework\JSONAPI\Model;
use Phramework\JSONAPI\ModelTrait;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\ResourceModel;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Article extends Model
{
    use ModelTrait;

    protected static function defineModel() : ResourceModel
    {
        return (new ResourceModel('article', new MemoryDataSource()))
            ->addVariable('table', 'article')
            ->setRelationships(
                (object) [
                    'author' => new Relationship(
                        function () {
                            return User::getResourceModel();
                        },
                        Relationship::TYPE_TO_ONE,
                        'creator-user_id'
                    ),
                    'tag' => new Relationship(
                        function () {
                            return Tag::getResourceModel();
                        },
                        Relationship::TYPE_TO_MANY,
                        null,//'tag_id'
                        (object) [
                            /**
                             * @param string $articleId
                             * @return string[]
                             */
                            'GET' => function (string $articleId) {
                                $ids = [];
                                return $ids;
                            }
                        ]
                    )
                ]
            );
    }
}
{

}
