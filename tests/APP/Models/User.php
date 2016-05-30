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
namespace Phramework\JSONAPI\APP\Models;

use \Phramework\Database\Database;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\FilterAttribute;
use Phramework\JSONAPI\IDirective;
use Phramework\JSONAPI\InternalModel;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\ResourceModelTrait;
use Phramework\JSONAPI\Sort;
use Phramework\Models\Operator;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\UnsignedIntegerValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class User extends ResourceModel
{
    //protected static $articleModel; //important

    use ResourceModelTrait;

    /**
     * Define articleModel
     */
    public static function defineModel() : InternalModel
    {
        $model = (new InternalModel('user'))
            ->setGet(
                function (IDirective ...$directives) use (&$model) {
                    //var_dump($articleModel->getDefaultDirectives());
                    return [];
                }
            )->addDefaultDirective(
                new Page(10),
                new Sort('email'),
                new Filter(
                    [],
                    null,
                    [
                        new FilterAttribute(
                            'status',
                            Operator::OPERATOR_EQUAL,
                            'ENABLED'
                        )
                    ]
                )
            );

        return $model;
    }
}
