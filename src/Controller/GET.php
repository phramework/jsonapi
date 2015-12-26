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
use \Phramework\Models\Operator;
use \Phramework\Exceptions\RequestException;

/**
 * GET related methods
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
abstract class GET extends \Phramework\JSONAPI\Controller\GETById
{
    /**
     * handles GET requests
     * @param  array  $params  Request parameters
     * @param  string $modelClass                      Resource's primary model
     * to be used
     * @param  array $additionalGetArguments           [Optional] Array with any
     * additional arguments that the primary data is requiring
     * @param  array $additionalRelationshipsArguments [Optional] Array with any
     * additional arguemnt primary data's relationships are requiring
     * @param  boolean $filterable                     [Optional] Deafult is
     * true, if true allowes `filter` URI parameters to be parsed for filtering
     * @param  boolean $filterableJSON                 [Optional] Deafult is
     * false, if true allowes `filter` URI parameters to be parsed for filtering
     * for JSON encoded fields
     * @param  boolean $sortable                       [Optional] Deafult is
     * true, if true allowes sorting
     */
    protected static function handleGET(
        $params,
        $modelClass,
        $additionalGetArguments = [],
        $additionalRelationshipsArguments = [],
        $filterable = true,
        $filterableJSON = false,
        $sortable = true
    ) {
        $page = null;

        $filter = (object)[
            'primary' => null,
            'relationships' => [],
            'attributes' => [],
            'attributesJSON' => []
        ];

        $sort = null;

        if ($filterable && isset($params['filter'])) {
            foreach ($params['filter'] as $filterKey => $filterValue) {
                //todo validate as int

                if ($filterKey === $modelClass::getType()) {
                    //Check filter value type
                    if (!is_string($filterValue) && !is_numeric($filterValue)) {
                        throw new RequestException(sprintf(
                            'String or integer value required for filter "%s"',
                            $filterKey
                        ));
                    }

                    $values = array_map(
                        'intval',
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

                    $values = array_map(
                        'intval',
                        array_map('trim', explode(',', trim($filterValue)))
                    );

                    $filter->relationships[$filterKey] = $values;

                    //when TYPE_TO_ONE it's easy to filter
                } else {
                    $validationModel = $modelClass::getValidationModel();

                    if (!$validationModel || !isset($validationModel->attributes)) {
                        throw new \Exception(sprintf(
                            'Model "%s" doesn\'t have a validation model for attributes',
                            $modelClass::getType()
                        ));
                    }

                    $validationModelAttributes = $validationModel->attributes;
                    $filterValidationModel = $modelClass::getFilterValidationModel();

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
                            if (!$validationModelAttributes
                                || !isset($validationModelAttributes->properties->{$filterKey})
                            ) {
                                throw new \Exception(sprintf(
                                    'Attribute "%s" doesn\'t have a validation model',
                                    $filterKey
                                ));
                            }

                            if ($isJSONFilter) {
                                //unparsable
                            } else {
                                //use filterValidationModel for this property
                                //if defined and operator is a CLASS_LIKE operator
                                if (in_array($operator, Operator::getLikeOperators())
                                    && $filterValidationModel
                                    && isset($filterValidationModel->properties->{$filterKey})
                                ) {
                                    //Validate operant value
                                    $operant = $filterValidationModel->properties
                                        ->{$filterKey}->parse($operant);
                                } else {
                                    //Validate operant value
                                    $operant = $validationModelAttributes->properties
                                        ->{$filterKey}->parse($operant);
                                }
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
        }

        //Parse pagination
        if (isset($params['page'])) {
            $tempPage = [];

            if (isset($params['page']['offset'])) {
                $tempPage['offset'] =
                    (new \Phramework\Validate\UnsignedIntegerValidator())
                        ->parse($params['page']['offset']);
            }

            if (isset($params['page']['limit'])) {
                $tempPage['limit'] =
                    (new \Phramework\Validate\UnsignedIntegerValidator())
                        ->parse($params['page']['limit']);
            }

            if (!empty($tempPage)) {
                $page = (object)$tempPage;
            }
        }

        //Push pagination $page object to end of arguments
        $additionalGetArguments[] = $page;

        if ($filterable) {
            //Push filters to end of arguments
            $additionalGetArguments[] = $filter;
        }

        if ($sortable) {
            $modelSort = $modelClass::getSort();

            $sort = null;

            if ($modelSort->default !== null) {
                $sort = new \stdClass();
                $sort->table = $modelClass::getTable();
                $sort->attribute = $modelSort->default;
                $sort->ascending = $modelSort->ascending;

                //Don't accept arrays
                if (isset($params['sort'])) {
                    if (!is_string($params['sort'])) {
                        throw new RequestException(
                            'String expected for sort'
                        );
                    }

                    $validateExpression =
                        '/^(?P<descending>\-)?(?P<attribute>'
                        . implode('|', $modelSort->attributes)
                        . ')$/';

                    if (!!preg_match($validateExpression, $params['sort'], $matches)) {
                        $sort->attribute = $matches['attribute'];
                        $sort->ascending = (
                            isset($matches['descending']) && $matches['descending']
                            ? false
                            : true
                        );

                    } else {
                        throw new RequestException(
                            'Invalid value for sort'
                        );
                    }
                }
            }

            //Push sort to end of arguments
            $additionalGetArguments[] = $sort;
        }

        $data = call_user_func_array(
            [$modelClass, 'get'],
            $additionalGetArguments
        );

        $requestInclude = static::getRequestInclude($params);

        //Get included data
        $includedData = $modelClass::getIncludedData(
            $data,
            $requestInclude,
            $additionalRelationshipsArguments
        );

        static::viewData(
            $data,
            ['self' => $modelClass::getSelfLink()],
            null,
            (empty($requestInclude) ? null : $includedData)
        );
    }
}
