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
namespace Phramework\JSONAPI\Directive;

use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\Parameter;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Operator\Operator;
use Phramework\Util\Util;
use Phramework\Validate\StringValidator;
use Phramework\Validate\UnsignedIntegerValidator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Filter helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Filter extends Directive
{
    /**
     * @var string[]
     * @example
     * ```php
     * [1, 2]
     * ```
     */
    protected $primary = [];

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
    protected $attributes = [];

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
     *         new FilterAttribute('title', Operator::LIKE, 'blog')
     *     ]
     * );
     * ```
     * @example
     * ```php
     * $filter = new Filter(
     *     [1, 2],
     *     null,
     *     [
     *         new FilterAttribute('title', Operator::LIKE, 'blog'),
     *         new FilterJSONAttribute('meta', 'keyword', Operator::EQUAL, 'blog')
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
                'Relationships filter must be an object'
            );
        }

        foreach ($relationships as $relationshipKey => $relationshipValue) {
            if (!is_array($relationshipValue) &&  $relationshipValue !== Operator::EMPTY) {
                throw new \InvalidArgumentException(sprintf(
                    'Values for relationship filter "%s" MUST be an array or Operator::"%s"',
                    $relationshipKey,
                    Operator::EMPTY
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
     * Validate against a resource resourceModel class
     * @param ResourceModel $model
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
    public function validate(ResourceModel $model) : bool
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

                if ($relationshipValue === Operator::EMPTY) {
                    continue;
                }

                $relationshipObject = $model->getRelationship($relationshipKey);
                $relationshipObjectModel = $relationshipObject->getResourceModel();
                
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
            $attribute = $filterAttribute->getAttribute();
            $operator  = $filterAttribute->getOperator();
            $operand   = $filterAttribute->getOperand();
            
            $isJSONFilter = ($filterAttribute instanceof FilterJSONAttribute);

            if (!property_exists($filterable, $attribute)) {
                throw new RequestException(sprintf(
                    'Filter attribute "%s" not allowed',
                    $attribute
                ));
            }

            $attributeValidator = null;

            //Attempt to use filter validation resourceModel first
            if ($filterValidator
                //&& isset($filterValidator)
                && isset($filterValidator->properties->{$attribute})
            ) {
                $attributeValidator =
                    $filterValidator->properties->{$attribute};
            } elseif ($validationModel
                && isset(
                    $validationModel->attributes,
                    $validationModel->attributes->properties->{$attribute})
            ) { //Then attempt to use attribute validation resourceModel first
                $attributeValidator =
                    $validationModel->attributes->properties->{$attribute};
            } else {
                throw new \Exception(sprintf(
                    'Filter attribute "%s" has not a filter validator',
                    $attribute
                ));
            }

            $operatorClass = $filterable->{$attribute};

            if ($isJSONFilter && ($operatorClass & Operator::CLASS_JSONOBJECT) === 0) {
                throw new RequestException(sprintf(
                    'Filter attribute "%s" is not accepting JSON object filtering',
                    $attribute
                ));
            }

            //Check if operator is allowed
            if (!$isJSONFilter && !in_array(
                $operator,
                Operator::getByClassFlags($operatorClass)
            )) {
                throw new RequestException(sprintf(
                    'Filter operator "%s" is not allowed for attribute "%s"',
                    $operator,
                    $attribute
                ));
            }

            //Validate filterAttribute operand against filter validator or validator if set
            if (!in_array($operator, Operator::getNullableOperators())) {
                if ($isJSONFilter) {
                    //If filter validator is set for dereference JSON object property
                    if ($filterValidator
                        && isset($filterValidator->properties->{$attribute})
                        && isset($filterValidator->properties->{$attribute}
                                ->properties->{$filterAttribute->getKey()})
                    ) {
                        $attributePropertyValidator = $filterValidator->properties
                            ->{$attribute}->properties->{$filterAttribute->getKey()};

                        $attributePropertyValidator->parse($operand);
                    }
                    //} else {
                    //    //**NOTE** Remain unparsed!
                    //}
                } else {
                    $attributeValidator->parse($operand);
                }
            }
        }

        return true;
    }

    /**
     * @param ServerRequestInterface $request Request parameters
     * @param ResourceModel          $model
     * @return null|Filter
     * @throws IncorrectParameterException
     * @throws RequestException
     * @todo allow strings and integers as id
     * @todo Todo use filterValidation resourceModel for relationships
     * @todo allowed operator for JSON properties
     * @todo add support for operators of class in, parsing input using explode `,` (as array)
     * @todo fix definition for version 3.x
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        $param = $request->getQueryParams()['filter'] ?? null;

        if (empty($param)) {
            return null;
        }

        $filterValidator = $model->getFilterValidator();
        $idAttribute     = $model->getIdAttribute();

        $filterPrimary       = [];
        $filterRelationships = new \stdClass();
        $filterAttributes    = [];

        foreach ($param as $filterKey => $filterValue) {
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

                if ($filterValue === Operator::EMPTY) {
                    $values = Operator::EMPTY;
                } else {
                    //Todo use filterValidation resourceModel

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
     *                 Operator::EQUAL,
     *                 true
     *             )
     *         ]
     *     )
     * );
     * ```
     */
    public static function merge(
        Filter $first = null,
        Filter $second = null
    ) {
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
