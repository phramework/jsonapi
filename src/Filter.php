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
namespace Phramework\JSONAPI;

use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\Parameter;
use Phramework\Models\Operator;
use Phramework\Util\Util;
use Phramework\Validate\StringValidator;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * Filter helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-read string[]          $primary
 * @property-read object            $relationships
 * @property-read FilterAttribute[]|FilterJSONAttribute[] $attributes
 * Attribute filters of type `FilterAttribute` and `FilterJSONAttribute`
 */
class Filter implements IDirective
{
    /**
     * @var string[]
     * @example
     * ```php
     * [1, 2]
     * ```
     */
    protected $primary;

    /**
     * @var \stdClass
     * @example
     * ```php
     * (object) [
     *     'author'  => [1],
     *     'comment' => [1, 2, 3],
     *     'tag'     => ['blog']
     * ]
     * ```
     */
    protected $relationships;

    /**
     * @var (FilterAttributeFilterJSONAttribute)[]
     */
    protected $attributes;

    /**
     * Filter constructor.
     * @param string[] $primary
     * @param \stdClass $relationships null wil be interpreted as empty object
     * @param (FilterAttribute|FilterJSONAttribute)[] $filterAttributes
     * @throws \Exception
     * @throws \InvalidArgumentException
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
     * @example
     * ```php
     * $filter = new Filter(
     *     [1, 2],
     *     null,
     *     [
     *         new FilterAttribute('title', Operator::OPERATOR_LIKE, 'blog'),
     *         new FilterJSONAttribute('meta', 'keyword', Operator::OPERATOR_EQUAL, 'blog')
     *     ]
     * );
     * ```
     */
    public function __construct(
        array $primary = [],
        \stdClass $relationships = null,
        array $filterAttributes = []
    ) {
        if ($relationships === null) {
            $relationships = new \stdClass();
        } elseif (is_array($relationships) && Util::isArrayAssoc($relationships)) { //Allow associative arrays
            $relationships = (object) $relationships;
        }

        if (!is_object($relationships)) {
            throw new \InvalidArgumentException(
                '$relationships filter must be an object'
            );
        }

        foreach ($relationships as $relationshipKey => $relationshipValue) {
            if (!is_array($relationshipValue) &&  $relationshipValue !== Operator::OPERATOR_EMPTY) {
                throw new \InvalidArgumentException(sprintf(
                    'Values for relationship filter "%s" MUST be an array or Operator::"%s"',
                    $relationshipKey,
                    Operator::OPERATOR_EMPTY
                ));
            }
        }

        if (!Util::isArrayOf($filterAttributes, FilterAttribute::class)) {
            throw new \InvalidArgumentException(
                'filterAttributes must be an array of FilterAttribute instances'
            );
        }

        $this->primary = $primary;
        $this->relationships = $relationships;
        $this->attributes = $filterAttributes;
    }

    /**
     * Validate against a resource model class
     * @param InternalModel $model
     * @throws RequestException
     * @throws \Exception
     * @throws IncorrectParametersException When filter for primary id attribute is incorrect
     * @throws IncorrectParametersException When filter for relationship is incorrect
     * @example
     * ```php
     * $filter = new Filter([1, 2, 3]);
     *
     * $filter->validate(Article::class);
     * ```
     * @todo add relationship idAttribute validators
     */
    public function validate(InternalModel $model)
    {
        $idAttribute     = $model->getIdAttribute();
        $filterValidator = $model->getFilterValidator();
        $validationModel = $model->getValidationModel();

        /**
         * Validate primary
         */

        //Use filterValidator for idAttribute if set else use unsigned integer validator to parse filtered values
        $idAttributeValidator = (
            !empty($filterValidator) && isset($filterValidator->properties->{$idAttribute})
            ? [$filterValidator->properties->{$idAttribute}, 'parse']
            : [UnsignedIntegerValidator::class, 'parseStatic']
        );

        //Run validator, if any value is incorrect IncorrectParametersException will be thrown
        foreach ($this->primary as $id) {
            call_user_func($idAttributeValidator, $id);
        }

        /**
         * Validate relationships
         */

        if ($this->relationships !== null) {
            foreach ($this->getRelationships() as $relationshipKey => $relationshipValue) {
                if (!$model->issetRelationship($relationshipKey)) {
                    throw new RequestException(sprintf(
                        'Not a valid relationship for filter relationship "%s"',
                        $relationshipKey
                    ));
                }

                if ($relationshipValue === Operator::OPERATOR_EMPTY) {
                    continue;
                }

                $relationshipObject = $model->getRelationship($relationshipKey);
                $relationshipObjectModel = $relationshipObject->getModel();
                
                $relationshipValidationModel = $relationshipObjectModel->getValidationModel();
                $relationshipFilterValidationModel = $relationshipObjectModel->getValidationModel();

                if ($relationshipFilterValidationModel !== null
                    && isset($relationshipFilterValidationModel->properties->{$relationshipObjectModel->getIdAttribute()})) {
                    $relationshipValidator = [
                        $relationshipFilterValidationModel->properties->{$relationshipObjectModel->getIdAttribute()},
                        'parse'
                    ];
                } elseif ($relationshipValidationModel !== null
                    && isset($relationshipValidationModel->properties->{$relationshipObjectModel->getIdAttribute()})
                ) {
                    $relationshipValidator = [
                        $relationshipValidationModel->properties->{$relationshipObjectModel->getIdAttribute()},
                        'parse'
                    ];
                } else {
                    $relationshipValidator = [UnsignedIntegerValidator::class, 'parseStatic'];
                }

                //Run validator, if any value is incorrect IncorrectParametersException will be thrown
                foreach ($relationshipValue as $id) {
                    call_user_func($relationshipValidator, $id);
                }
            }
        }

        /**
         * Validate attributes
         */

        $filterable = $model->getFilterableAttributes();

        foreach ($this->attributes as $filterAttribute) {
            $isJSONFilter = ($filterAttribute instanceof FilterJSONAttribute);

            if (!property_exists($filterable, $filterAttribute->attribute)) {
                throw new RequestException(sprintf(
                    'Filter attribute "%s" not allowed',
                    $filterAttribute->attribute
                ));
            }

            $attributeValidator = null;

            //Attempt to use filter validation model first
            if ($filterValidator
                //&& isset($filterValidator)
                && isset($filterValidator->properties->{$filterAttribute->attribute})
            ) {
                $attributeValidator =
                    $filterValidator->properties->{$filterAttribute->attribute};
            } elseif ($validationModel
                && isset(
                    $validationModel->attributes,
                    $validationModel->attributes->properties->{$filterAttribute->attribute})
            ) { //Then attempt to use attribute validation model first
                $attributeValidator =
                    $validationModel->attributes->properties->{$filterAttribute->attribute};
            } else {
                throw new \Exception(sprintf(
                    'Filter attribute "%s" has not a filter validator',
                    $filterAttribute->attribute
                ));
            }

            $operatorClass = $filterable->{$filterAttribute->attribute};

            if ($isJSONFilter && ($operatorClass & Operator::CLASS_JSONOBJECT) === 0) {
                throw new RequestException(sprintf(
                    'Filter attribute "%s" is not accepting JSON object filtering',
                    $filterAttribute->attribute
                ));
            }

            //Check if operator is allowed
            if (!$isJSONFilter && !in_array(
                $filterAttribute->operator,
                Operator::getByClassFlags($operatorClass)
            )) {
                throw new RequestException(sprintf(
                    'Filter operator "%s" is not allowed for attribute "%s"',
                    $filterAttribute->operator,
                    $filterAttribute->attribute
                ));
            }

            //Validate filterAttribute operand against filter validator or validator if set
            if (!in_array($filterAttribute->operator, Operator::getNullableOperators())) {
                if ($isJSONFilter) {
                    //If filter validator is set for dereference JSON object property
                    if ($filterValidator
                        && isset($filterValidator->properties->{$filterAttribute->attribute})
                        && isset($filterValidator->properties->{$filterAttribute->attribute}
                                ->properties->{$filterAttribute->key})
                    ) {
                        $attributePropertyValidator = $filterValidator->properties
                            ->{$filterAttribute->attribute}->properties->{$filterAttribute->key};

                        $attributePropertyValidator->parse($filterAttribute->operand);
                    }
                    //} else {
                    //    //**NOTE** Remain unparsed!
                    //}
                } else {
                    $attributeValidator->parse($filterAttribute->operand);
                }
            }
        }
    }

    /**
     * @param \stdClass $parameters Request parameters
     * @param InternalModel    $model
     * @return Filter|null
     * @todo allow strings and integers as id
     * @todo Todo use filterValidation model for relationships
     * @todo allowed operator for JSON properties
     * @example
     * ```php
     * $filter = Filter::parseFromParameters(
     *     (object) [
     *         'filter' => [
     *             'article'   => '1, 2', //primary filter (parsed from URL's ?filter[article]=1, 2)
     *             'tag'       => '4, 5,7', //relationship filter (parsed from URL's ?filter[tag]=4, 5,7)
     *             'creator'   => '1', //relationship  filter(parsed from URL's ?filter[creator]=1)
     *             'status'    => ['ENABLED', 'INACTIVE'], //multiple filters
     *             'title'     => [
     *                 Operator::OPERATOR_LIKE . 'blog',
     *                 Operator::OPERATOR_NOT_LIKE . 'welcome'
     *             ], //multiple filters on title (parsed from URL's ?filter[title][]=~~blog&filter[title][]=!~~welcome)
     *             'updated'   => Operator::OPERATOR_NOT_ISNULL,
     *             'meta.keywords' => 'blog' //JSON attribute filter
     *         ]
     *     ], //Request parameters object
     *     Article::class
     * );
     * ```
     * @example
     * ```php
     * $filter = Filter::parseFromParameters(
     *     (object) [
     *         'filter' => [
     *             'tag'       => Operator::OPERATOR_EMPTY
     *         ]
     *     ],
     *     Article::class
     * );
     * @throws RequestException
     * @throws \Exception
     * @throws IncorrectParametersException
     * @todo add support for operators of class in, parsing input using explode `,` (as array)
     * @todo fix definition for version 3.x
     */
    public static function parseFromRequest(
        \stdClass $parameters,
        InternalModel $model
    ) {
        if (!isset($parameters->filter)) {
            return null;
        }

        $filterValidator = $model->getFilterValidator();
        $idAttribute     = $model->getIdAttribute();

        $filterPrimary       = [];
        $filterRelationships = new \stdClass();
        $filterAttributes    = [];

        foreach ($parameters->filter as $filterKey => $filterValue) {
            if ($filterKey === $model->getResourceType()) { //Filter primary data
                //Check filter value type
                if (!is_string($filterValue) && !is_int($filterValue)) {
                    throw new IncorrectParameterException(
                        'type',
                        sprintf(
                            'String or integer value required for filter "%s"',
                            $filterKey
                        ),
                        new Parameter('filter[' . $filterKey . ']')
                    );
                }

                //Use filterValidator for idAttribute if set else use intval to parse filtered values
                //$function = (
                //    !empty($filterValidator) && isset($filterValidator->properties->{$idAttribute})
                //    ? [$filterValidator->properties->{$idAttribute}, 'parse']
                //    : 'intval'
                //);

                //Split multiples and trim additional spaces and force string
                $values = array_map(
                    'strval',
                    array_map('trim', explode(',', trim($filterValue)))
                    //array_map(
                    //    $function,
                    //    array_map('trim', explode(',', trim($filterValue)))
                    //)
                );

                $filterPrimary = $values;
            } elseif ($model->issetRelationship($filterKey)) { //Filter relationship data

                //Check filter value type
                if (!is_string($filterValue) && !is_int($filterValue)) {
                    throw new IncorrectParameterException(
                        'type',
                        sprintf(
                            'String or integer value required for filter "%s"',
                            $filterKey
                        ),
                        new Parameter('filter[' . $filterKey . ']')
                    );
                }

                if ($filterValue === Operator::OPERATOR_EMPTY) {
                    $values = Operator::OPERATOR_EMPTY;
                } else {
                    //Todo use filterValidation model

                    //Split multiples and trim additional spaces and force string
                    $values = array_map(
                        'strval',
                        array_map('trim', explode(',', trim($filterValue)))
                    );
                }

                $filterRelationships->{$filterKey} = $values;
            } else {
                $filterAttributes = array_merge(
                    $filterAttributes,
                    FilterAttribute::parse($filterKey, $filterValue)
                );
            }
        }

        return new Filter(
            $filterPrimary,
            $filterRelationships,
            $filterAttributes
        );
    }

    /**
     * Merge two filters
     * @param Filter $first
     * @param Filter $second
     * @return Filter Merged filter
     * @example
     * ```php
     * //Force additional filters to $filter object
     * $filter = Filter::merge(
     *     $filter,
     *     new Filter(
     *         [],
     *         null,
     *         [
     *             new FilterAttribute(
     *                 'status',
     *                 Operator::OPERATOR_EQUAL,
     *                 true
     *             )
     *         ]
     *     )
     * );
     * ```
     */
    public static function merge(Filter $first = null, Filter $second = null) {
        //Initialize if null
        if ($first === null) {
            $first = new Filter();
        }

        //Initialize if null
        if ($second === null) {
            $second = new Filter();
        }

        return new Filter(
            array_merge(
                $first->getPrimary(),
                $second->getPrimary()
            ),
            (object) array_merge_recursive(
                (array) $first->getRelationships(),
                (array) $second->getRelationships()
            ),
            array_merge(
                $first->getAttributes(),
                $second->getAttributes()
            )
        );
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        if (in_array($name, ['primary', 'relationships', 'attributes'])) {
            return $this->{$name};
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }

    /**
     * @return string[]
     */
    public function getPrimary() : array
    {
        return $this->primary;
    }

    /**
     * @return \stdClass
     */
    public function getRelationships() : \stdClass
    {
        return $this->relationships;
    }

    /**
     * @return (FilterAttribute|FilterJSONAttribute)[]
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }
}
