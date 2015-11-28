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
namespace Phramework\JSONAPI\Controller;

use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;

/**
 * DELETE
 * @package JSONAPI
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class PATCH extends \Phramework\JSONAPI\Controller\POST
{
    /**
     * @todo allow null values
     * @param  array  $params                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  integer|string $id                      Requested resource's id
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $additionalGetArguments           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @throws \Phramework\Exceptions\NotFound         If resource not found
     * @throws \Phramework\Exceptions\RequestException If no fields are changed
     */
    protected static function handlePATCH(
        $params,
        $method,
        $headers,
        $id,
        $modelClass,
        $additionalGetArguments = []
    ) {
        $validationModel = new \Phramework\Validate\ObjectValidator(
            [],
            [],
            false
        );

        $classValidationModel = $modelClass::getValidationModel();

        foreach ($modelClass::getMutable() as $mutable) {
            if (!isset($classValidationModel->properties->{$mutable})) {
                throw new \Exception(sprintf(
                    'Validation model for attribute "%s" is not set!',
                    $mutable
                ));
            }

            $validationModel->addProperty(
                $mutable,
                $classValidationModel->properties->{$mutable}
            );
        }

        $requestAttributes = static::getRequestAttributes($params);

        $attributes = $validationModel->parse($requestAttributes);

        foreach ($attributes as $key => $attribute) {
            if ($attribute === null) {
                unset($attributes->{$key});
            }
        }

        if (count($attributes) === 0) {
            throw new RequestException('No fields updated');
        }

        //Fetch data, to check if resource exists
        $data = call_user_func_array(
            [
                $modelClass,
                $modelClass::GET_BY_PREFIX . ucfirst($modelClass::getIdAttribute())
            ],
            array_merge([$id], $additionalGetArguments)
        );

        //Check if resource exists
        static::exists($data);

        $patch = $modelClass::patch($id, (array)$attributes);

        return static::viewData(
            $modelClass::resource(['id' => $id]),
            ['self' => $modelClass::getSelfLink($id)]
        );
    }
}
