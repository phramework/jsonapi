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
namespace Phramework\JSONAPI\Controller;

use \Phramework\Models\Request;
use \Phramework\Exceptions\RequestException;
use Phramework\Validate\ObjectValidator;

/**
 * Patch related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class PATCH extends \Phramework\JSONAPI\Controller\POST
{
    /**
     * @param  object $parameters                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  integer|string $id                      Requested resource's id
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $primaryDataParameters           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @throws \Phramework\Exceptions\NotFound         If resource not found
     * @throws \Phramework\Exceptions\RequestException If no fields are changed
     * @uses model's `getById` method to fetch resource
     * @uses $modelClass::patch method to update resources
     * @todo clear call to getById
     * @todo allow null values
     * @todo patch relationship data
     * @throws \Exception When Validation model for an attribute  is not set
     * @return boolean
     * @todo rethink output
     */
    protected static function handlePATCH(
        $parameters,
        $method,
        $headers,
        $id,
        $modelClass,
        $primaryDataParameters = []
    ) {
        //Construct a validator
        $validator = new ObjectValidator(
            (object) [],
            [],
            false
        );

        $attributeValidator = $modelClass::getValidationModel()->attributes;

        if ($attributeValidator === null) {
            //TODO ???
        }

        foreach ($modelClass::getMutable() as $mutable) {
            if (!isset($attributeValidator->properties->{$mutable})) {
                throw new \Exception(sprintf(
                    'Validation model for attribute "%s" is not set!',
                    $mutable
                ));
            }

            //Push property to validator
            $validator->addProperty(
                $mutable,
                $attributeValidator->properties->{$mutable}
            );
        }

        $requestAttributes = static::getRequestAttributes($parameters);

        if (count((array) $requestAttributes)  === 0) {
            //@todo throw exception only both attributes and relationships are 0
            throw new RequestException('No fields updated');
        }

        $attributes = $validator->parse($requestAttributes);

        foreach ($attributes as $key => $attribute) {
            if ($attribute === null) {
                unset($attributes->{$key});
            }
        }

        //Fetch data, to check if resource exists
        $data = $modelClass::getById(
            $id,
            null, //fields
            ...$primaryDataParameters
        );

        //Check if resource exists (MUST exist!)
        static::exists($data);

        $patch = $modelClass::patch($id, (array) $attributes);

        self::testUnknownError($patch, 'PATCH operation was not successful');

        static::viewData(
            $modelClass::resource(['id' => $id]),
            ['self' => $modelClass::getSelfLink($id)]
        );

        return true;
    }
}
