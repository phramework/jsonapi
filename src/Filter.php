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
namespace Phramework\JSONAPI;

use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\Models\Operator;
use Phramework\Validate\StringValidator;

/**
 * Filter helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-read string[]          $primary
 * @property-read object            $relationships
 * @property-read FilterAttribute[] $attributes Attribute filters of type `FilterAttribute` and `FilterJSONAttribute`
 */
class Filter
{
    const JSON_ATTRIBUTE_FILTER_PROPERTY_EXPRESSION = '/^[a-zA-Z_\-0-9]{1,32}$/';

    /**
     * @var string[]
     * @example
     * ```php
     * [1, 2]
     * ```
     */
    protected $primary = null;

    /**
     * @var object
     * @example
     * ```php
     * (object) [
     *     'author'  => [1],
     *     'comment' => [1, 2, 3],
     *     'tag'     => ['blog']
     * ]
     * ```
     */
    protected $relationships = [];

    /**
     * @var FilterAttribute[]
     */
    protected $attributes = [];

    /**
     * Filter constructor.
     * @param string[] $primary
     * @param array $relationships
     * @param FilterAttribute[] $attributes
     * @throws \Exception
     * @example
     * ```php
     * $filter = new Filter(
     *     [1, 2],
     *     (object) [
     *         'author'  => [1],
     *         'comment' => [1, 2, 3],
     *         'tag'     => ['blog']
     *     ],
     *     [
     *         new FilterAttribute('title', Operator::OPERATOR_LIKE, 'blog')
     *     ]
     * );
     * ```
     */
    public function __construct(
        $primary = [],
        $relationships = [],
        $attributes = []
    ) {

        if (!is_array($primary)) {
            throw new \Exception('Primary filter MUST be an array');
        }

        if (is_array($relationships)) {
            $relationships = (object)$relationships;
        }

        if (!is_object($relationships)) {
            throw new \Exception('Relationships filter MUST be an object');
        }

        if (!is_array($attributes)) {
            throw new \Exception('Attributes filter MUST be an array');
        }

        $this->primary = $primary;
        $this->relationships = $relationships;
        $this->attributes = $attributes;
    }

    /**
     * @param object $parameters Request parameters
     * @param string $modelClass
     * @param bool   $filterableJSON *[Optional]*
     * @return Filter|null
     * @todo allow strings and integers as id
     * @todo Todo use filterValidation model for relationships
     * @todo allowed operator for JSON properties
     * @example
     * ```php
     * $filter = Filter::parseFromParameters(
     *     (object) [
     *         'filter' => [
     *             'article'   => '1, 2',
     *             'tag'       => '4, 5, 7',
     *             'creator'   => '1',
     *             'status'    => [true, false],
     *             'title'     => [
     *                 Operator::OPERATOR_LIKE . 'blog',
     *                 Operator::OPERATOR_NOT_LIKE . 'welcome'
     *             ],
     *             'updated'   => Operator::OPERATOR_NOT_ISNULL,
     *             'meta.keywords' => 'blog'
     *         ]
     *     ], //Request parameters object
     *     Article::class
     * );
     * ```
     * @throws RequestException
     * @throws \Exception
     * @throws IncorrectParametersException
     */
    public static function parseFromParameters($parameters, $modelClass, $filterableJSON = true)
    {
        if (!isset($parameters->filter)) {
            return null;
        }

        $filterValidationModel = $modelClass::getFilterValidationModel();
        $idAttribute = $modelClass::getIdAttribute();

        $filterPrimary       = [];
        $filterRelationships = new \stdClass();
        $filterAttributes    = [];

        foreach ($parameters->filter as $filterKey => $filterValue) {

            if ($filterKey === $modelClass::getType()) { //Filter primary data
                //Check filter value type
                if (!is_string($filterValue) && !is_integer($filterValue)) {
                    throw new RequestException(sprintf(
                        'String or integer value required for filter "%s"',
                        $filterKey
                    ));
                }

                //Use filterValidator for idAttribute if set else use intval to parse filtered values
                $function = (
                    !empty($filterValidationModel) && isset($filterValidationModel->properties->{$idAttribute})
                    ? [$filterValidationModel->properties->{$idAttribute}, 'parse']
                    : 'intval'
                );

                //Split multiples and trim additional spaces and force string
                $values = array_map(
                    'strval',
                    array_map(
                        $function,
                        array_map('trim', explode(',', trim($filterValue)))
                    )
                );

                $filterPrimary = $values;
            } elseif ($modelClass::relationshipExists($filterKey)) { //Filter relationship data
                //Check filter value type
                if (!is_string($filterValue) && !is_integer($filterValue)) {
                    throw new RequestException(sprintf(
                        'String or integer value required for filter "%s"',
                        $filterKey
                    ));
                }

                //Todo use filterValidation model
                $function = 'intval';

                //Split multiples and trim additional spaces and force string
                $values = array_map(
                    'strval',
                    array_map(
                        $function,
                        array_map('trim', explode(',', trim($filterValue)))
                    )
                );

                $filterRelationships->{$filterKey} = $values;

                //when TYPE_TO_ONE it's easy to filter ??
            } else {
                $validationModel = $modelClass::getValidationModel();

                $filterable = $modelClass::getFilterable();

                $isJSONFilter = false;

                //Check if $filterKeyParts and key contains . dot character (object dereference operator)
                if ($filterableJSON && strpos($filterKey, '.') !== false) {
                    $filterKeyParts = explode('.', $filterKey);

                    if (count($filterKeyParts) > 2) {
                        throw new RequestException(
                            'Second level filtering for JSON objects is not available'
                        );
                    }

                    $filterPropertyKey = $filterKeyParts[1];

                    //Hack check $filterPropertyKey if valid using regular expression
                    (new StringValidator(0, null, self::JSON_ATTRIBUTE_FILTER_PROPERTY_EXPRESSION))
                        ->parse($filterPropertyKey);

                    $filterKey = $filterKeyParts[0];

                    $isJSONFilter = true;
                }

                if (!key_exists($filterKey, $filterable)) {
                    throw new RequestException(sprintf(
                        'Filter key "%s" not allowed',
                        $filterKey
                    ));
                }

                $attributeValidator = null;

                //Attempt to use filter validation model first
                if ($filterValidationModel
                    //&& isset($filterValidationModel)
                    && isset($filterValidationModel->properties->{$filterKey})
                ) {
                    $attributeValidator =
                        $filterValidationModel->properties->{$filterKey};
                } elseif ($validationModel
                    && isset($validationModel->attributes)
                    && isset($validationModel->attributes->properties->{$filterKey})
                ) { //Then attempt to use attribute validation model first
                    $attributeValidator =
                        $validationModel->attributes->properties->{$filterKey};
                } else {
                    throw new \Exception(sprintf(
                        'Attribute "%s" has not a filter validator',
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

                    list($operator, $operand) = Operator::parse($singleFilterValue);

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
                            //If filter validator is set for dereference JSON object property
                            if ($filterValidationModel
                                && isset($filterValidationModel->properties->{$filterKey})
                                && isset($filterValidationModel->properties->{$filterKey}
                                        ->properties->{$filterPropertyKey})
                            ) {

                                $attributePropertyValidator = $filterValidationModel->properties
                                    ->{$filterKey}->properties->{$filterPropertyKey};

                                $operand = $attributePropertyValidator->parse($operand);
                            } else {
                                //**NOTE** Remain unparsed!
                            }
                        } else {
                            //use filterValidationModel for this property
                            //if defined and operator is a CLASS_LIKE operator
                            //if (in_array($operator, Operator::getLikeOperators())
                            //    && $filterValidationModel
                            //    && isset($filterValidationModel->properties->{$filterKey})
                            //) {
                            //    //Validate operand value
                            //    $operand = $filterValidationModel->properties
                            //        ->{$filterKey}->parse($operand);
                            //} else {
                            //    //Validate operand value
                            //    $operand = $validationModelAttributes->properties
                            //        ->{$filterKey}->parse($operand);
                            //}
                            //
                            $operand = $attributeValidator->parse($operand);
                        }
                    }

                    //Push to attribute filters
                    if ($isJSONFilter) {
                        $filterAttributes[] =  new FilterJSONAttribute(
                            $filterKey,
                            $filterPropertyKey,
                            $operator,
                            $operand
                        );
                    } else {
                        $filterAttributes[] = new FilterAttribute($filterKey, $operator, $operand);
                    }
                }
            }
        }

        return new Filter(
            $filterPrimary,
            $filterRelationships,
            $filterAttributes
        );
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        switch ($name) {
            case 'primary':
                return $this->primary;
            case 'relationships':
                return $this->relationships;
            case 'attributes':
                return $this->attributes;
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }
}
