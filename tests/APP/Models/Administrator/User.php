<?php
/**
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
namespace Phramework\JSONAPI\APP\Models\Administrator;

use \Phramework\Database\Database;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\IDirective;
use Phramework\JSONAPI\InternalModel;
use Phramework\JSONAPI\Page;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\RelationshipResource;
use Phramework\JSONAPI\Resource;
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\ResourceModelTrait;
use Phramework\JSONAPI\Sort;
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

    use ResourceModelTrait;
    //protected static $model; //important
    
    /**
     * Define model
     */
    protected static function defineModel() : InternalModel
    {
        $model = \Phramework\JSONAPI\APP\Models\User::defineModel(); //based on User model

        $model = $model->addDefaultDirective(
                new Page(20),
                new Filter() //no filter - empty filter, will overwrite previous filter definition
        );

        return $model;
    }
}
