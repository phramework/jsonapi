<?php
declare(strict_types=1);
/**
 * Copyright 2016 Xenofon Spafaridis
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
use Phramework\JSONAPI\ValidationModel;
use Phramework\Validate\ArrayValidator;
use Phramework\Validate\ObjectValidator;
use Phramework\Validate\StringValidator;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 1.0
 */
class Article extends Model
{
    use ModelTrait;

    protected static function defineModel() : ResourceModel
    {
        $r =  (new ResourceModel('article', new MemoryDataSource()));
        return $r
            ->addVariable('table', 'article')
            ->setSortableAttributes(
                'id'
            )->setValidationModel(
                new ValidationModel(
                    new ObjectValidator(
                        (object) [
                            'title'  => new StringValidator(),
                            'body'   => new StringValidator(),
                            'status' => (new UnsignedIntegerValidator(0, 1))
                                ->setDefault(1)
                        ],
                        ['title', 'body'],
                        false
                    ),
                    new ObjectValidator(
                        (object) [
                            'author' => User::getResourceModel()->getIdAttributeValidator(),
                            'tag'    => new ArrayValidator(
                                0,
                                null,
                                Tag::getResourceModel()->getIdAttributeValidator()
                            )
                        ],
                        ['author'],
                        false
                    )
                ),
                'POST'
            )
            /*->setValidationModel(
                new ValidationModel(
                    new ObjectValidator(
                        (object) [
                            'title' => new StringValidator(),
                            'body'  => new StringValidator(),
                            'status' => (new UnsignedIntegerValidator(0, 1))
                        ],
                        [],
                        false
                    ),
                    new ObjectValidator(
                        (object) [
                            'author' => User::getResourceModel()->getIdAttributeValidator()
                        ],
                        [],
                        false
                    )
                ),
                'PATCH'
            )*/
            ->setRelationships(
                (object) [
                    'author' => new Relationship(
                        User::getResourceModel(),
                        Relationship::TYPE_TO_ONE,
                        'creator-user_id'
                    ),
                    'tag'    => new Relationship(
                        Tag::getResourceModel(),
                        Relationship::TYPE_TO_MANY,
                        'tag_id',
                        (object) [
                            /**
                             * @param string $articleId
                             * @param string $tagId
                             */
                            'POST' => function (string $articleId, string $tagId) use (&$r) {
                                //todo use actual datasource to store connection
                                var_dump(sprintf('post (%s, %s',
                                    $articleId,
                                    $tagId
                                ));
                            }
                        ]
                    )
                ]
            );
    }
}
