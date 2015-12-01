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
use \Phramework\JSONAPI\Relationship;

/**
 * DELETE
 * @package JSONAPI
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class POST extends \Phramework\JSONAPI\Controller\GET
{
    /**
     * @param  array  $params                          Request parameters
     * @param  string $method                          Request method
     * @param  array  $headers                         Request headers
     * @param  string $modelClass                      Resource's primary model
     * @todo use $additionalGetArguments
     */
    protected static function handlePOST(
        $params,
        $method,
        $headers,
        $modelClass
    ) {

        $requestAttributes = static::getRequestAttributes($params);

        $validationModel = $modelClass::getValidationModel();

        //force additionalProperties to false
        $validationModel->attributes->additionalProperties = false;

        //parse request attributes using $validationModel to validate the data
        $attributes = $validationModel->attributes->parse($requestAttributes);

        $relationships = $modelClass::getRelationships();

        $requestRelationships = static::getRequestRelationships($params);

        foreach ($requestRelationships as $relationshipKey => $relationshipValue) {
            //MUST exists
            $relationshipData = $relationshipValue['data'];
            //Check if relationship exists
            static::exists(
                $modelClass::relationshipExists($relationshipKey),
                sprintf(
                    'Relationship "%s" not found',
                    $relationshipKey
                )
            );

            //check types

            //if to ONE
            $relationship = $modelClass::getRelationship($relationshipKey);
            $relationshipClass = $relationship->getRelationshipClass();
            $relationshipResourceType = $relationship->getType();

            if ($relationship->getRelationshipType() == Relationship::TYPE_TO_ONE) {
                $value = $relationshipData;

                if ($value['type'] !== $relationshipResourceType) {
                    throw new RequestException(sprintf(
                        'Invalid resource type "%s" for relationship "%s"',
                        $value['type'],
                        $relationshipKey
                    ));
                }

                //expect {type: "type", id: "id"}
                $value['id'] = $validationModel->relationships->{$relationshipKey}->parse(
                    $value['id']
                );


                //check if exists
                self::exists(
                    call_user_func_array(
                        [
                            $relationshipClass,
                            $relationshipClass::GET_BY_PREFIX . ucfirst($relationshipClass::getIdAttribute())
                        ],
                        [$value['id']]
                    ),
                    sprintf(
                        'Resource of type "%s" and id "%d" is not found',
                        $value['type'],
                        $value['id']
                    )
                );

                //push attribute to primary data attributes
                $attributes->{$relationship->getAttribute()} = $value['id'];
            } else {
                $parsedValues = [];

                foreach ($relationshipData as $value) {
                    if ($value['type'] !== $relationshipResourceType) {
                        throw new RequestException(sprintf(
                            'Invalid resource type "%s" for relationship "%s"',
                            $relationshipResourceType,
                            $relationshipKey
                        ));
                    }
                    $parsedValues[] = $value['id'];
                }

                $method = [
                    $relationshipClass,
                    $relationshipClass::GET_BY_PREFIX . ucfirst($relationshipClass::getIdAttribute())
                ];

                $parsedValues = $validationModel->relationships->{$relationshipKey}->parse(
                    $parsedValues
                );


                //check if each item in $parsedValues exists @TODO use additional
                foreach ($parsedValues as $value) {
                    self::exists(
                        call_user_func_array(
                            $method,
                            [$value]
                        ),
                        sprintf(
                            'Resource of type "%s" and id "%d" is not found',
                            $relationshipResourceType,
                            $value
                        )
                    );
                }

                //do something with this array
            }
        }

        $id = $modelClass::post((array)$attributes);

        //Prepare response with 201 Created status code
        \Phramework\Models\Response::created(
            $modelClass::getSelfLink($id)
        );

        return static::viewData(
            $modelClass::resource(['id' => $id]),
            ['self' => $modelClass::getSelfLink($id)]
        );

    }
}
