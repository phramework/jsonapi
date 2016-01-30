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
namespace Phramework\JSONAPI\Controller\GET;

use Phramework\Exceptions\RequestException;
use Phramework\Models\Operator;

/**
 * Filter helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Filter
{
    /**
     * @var integer[]
     */
    public $primary = null;
    /**
     * @var integer[]
     */
    public $relationships = [];
    /**
     * @var array $attributes (each array item [$attribute, $operator, $operant])
     */
    public $attributes = [];
    /**
     * @var array $attributesJSON (each array item [$attribute, $key, $operator, $operant])
     */
    public $attributesJSON = [];

    /**
     * @param object $parameters Request parameters
     * @throws RequestException
     * @return Filter|null
     * @todo define $filterableJSON
     */
    public static function parseFromParameters($parameters, $modelClass)
    {
        if (!isset($parameters->filter)) {
            return null;
        }

        $filter = new Filter();

        $filterValidationModel = $modelClass::getFilterValidationModel();
        $idAttribute = $modelClass::getIdAttribute();

        foreach ($parameters->filter as $filterKey => $filterValue) {
            //todo validate as int

            if ($filterKey === $modelClass::getType()) {
                //Check filter value type
                if (!is_string($filterValue) && !is_numeric($filterValue)) {
                    throw new RequestException(sprintf(
                        'String or integer value required for filter "%s"',
                        $filterKey
                    ));
                }

                //Use filterValidator for idAttribute if set else use intval to parse filtered values
                $function = (
                !empty($filterValidationModel) && isset($filterValidationModel->{$idAttribute})
                    ? [$filterValidationModel->{$idAttribute}, 'parse']
                    : 'intval'
                );

                $values = array_map(
                    $function,
                    array_map('trim', explode(',', trim($filterValue)))
                );

                $filter->primary = $values;
            } elseif ($modelClass::relationshipExists($filterKey)) {
                //Check filter value type
                if (!is_string($filterValue) && !is_numeric($filterValue)) {
                    throw new RequestException(sprintf(
                        'String or integer value required for filter "%s"',
                        $filterKey
                    ));
                }

                //Todo use filterValidation model

                $values = array_map(
                    'intval',
                    array_map('trim', explode(',', trim($filterValue)))
                );

                $filter->relationships[$filterKey] = $values;

                //when TYPE_TO_ONE it's easy to filter
            } else {
                $validationModel = $modelClass::getValidationModel();
                //if (!$validationModel || !isset($validationModel->attributes)) {
                //    throw new \Exception(sprintf(
                //        'Model "%s" doesn\'t have a validation model for attributes',
                //        $modelClass::getType()
                //    ));
                //}
                $attributeValidationModel = null;

                if ($filterValidationModel
                    && isset($filterValidationModel)
                    && isset($filterValidationModel->properties->{$filterKey})
                ) {
                    $attributeValidationModel =
                        $filterValidationModel->properties->{$filterKey};
                } elseif ($validationModel
                    && isset($validationModel->attributes)
                    && isset($validationModel->attributes->properties->{$filterKey})
                ) {
                    $attributeValidationModel =
                        $validationModel->attributes->properties->{$filterKey};
                } else {
                    throw new \Exception(sprintf(
                        'Attribute "%s" doesn\'t have a validation model',
                        $filterKey
                    ));
                }

                //$validationModelAttributes = $validationModel->attributes;

                $filterable = $modelClass::getFilterable();

                $isJSONFilter = false;

                //Check if $filterKeyParts and key contains . dot character
                if ($filterableJSON && strpos($filterKey, '.') !== false) {
                    $filterKeyParts = explode('.', $filterKey);

                    if (count($filterKeyParts) > 2) {
                        throw new RequestException(
                            'Second level filtering for JSON objects is not available'
                        );
                    }

                    $filterSubkey = $filterKeyParts[1];

                    //Hack check $filterSubkey if valid using regexp
                    Validate::regexp(
                        $filterSubkey,
                        '/^[a-zA-Z_\-0-9]{1,30}$/',
                        'filter[' . $filterKey . ']'
                    );

                    $filterKey = $filterKeyParts[0];

                    $isJSONFilter = true;
                }

                if (!key_exists($filterKey, $filterable)) {
                    throw new RequestException(sprintf(
                        'Filter key "%s" not allowed',
                        $filterKey
                    ));
                }

                $operatorClass = $filterable[$filterKey];

                if ($isJSONFilter && ($operatorClass & Operator::CLASS_JSONOBJECT) === 0) {
                    throw new RequestException(sprintf(
                        'Filter key "%s" is not accepting JSON object filtering',
                        $filterKey
                    ));
                }

                //All must be arrays
                if (!is_array($filterValue)) {
                    $filterValue = [$filterValue];
                }

                foreach ($filterValue as $singleFilterValue) {
                    if (is_array($singleFilterValue)) {
                        throw new RequestException(sprintf(
                            'Array given for filter "%s"',
                            $filterKey
                        ));
                    }

                    $singleFilterValue = urldecode($singleFilterValue);

                    list($operator, $operant) = Operator::parse($singleFilterValue);

                    //Validate operator (check if it's in allowed operators class)
                    if (!in_array(
                        $operator,
                        Operator::getByClassFlags($operatorClass)
                    )) {
                        throw new RequestException(sprintf(
                            'Not allowed operator for field "%s"',
                            $filterKey
                        ));
                    }

                    if ((in_array($operator, Operator::getNullableOperators()))) {
                        //Do nothing for nullable operators
                    } else {
                        //if (!$validationModelAttributes
                        //    || !isset($validationModelAttributes->properties->{$filterKey})
                        //) {
                        //    throw new \Exception(sprintf(
                        //        'Attribute "%s" doesn\'t have a validation model',
                        //        $filterKey
                        //    ));
                        //}

                        if ($isJSONFilter) {
                            //unparsable
                        } else {
                            //use filterValidationModel for this property
                            //if defined and operator is a CLASS_LIKE operator
                            //if (in_array($operator, Operator::getLikeOperators())
                            //    && $filterValidationModel
                            //    && isset($filterValidationModel->properties->{$filterKey})
                            //) {
                            //    //Validate operant value
                            //    $operant = $filterValidationModel->properties
                            //        ->{$filterKey}->parse($operant);
                            //} else {
                            //    //Validate operant value
                            //    $operant = $validationModelAttributes->properties
                            //        ->{$filterKey}->parse($operant);
                            //}
                            //
                            $operant = $attributeValidationModel->parse($operant);
                        }
                    }
                    if ($isJSONFilter) {
                        //Push tuple to attribute filters
                        $filter->attributesJSON[] = [$filterKey, $filterSubkey, $operator, $operant];
                    } else {
                        //Push tuple to attribute filters
                        $filter->attributes[] = [$filterKey, $operator, $operant];
                    }
                }
            }
        }

        return $filter;
    }

    public function __construct(
        $primary = null,
        $relationships= [],
        $attributes = [],
        $attributesJSON = []
    ) {

        $this->primary = $primary;
        $this->relationships = $relationships;
        $this->attributes = $attributes;
        $this->attributesJSON = $attributesJSON;
    }

}