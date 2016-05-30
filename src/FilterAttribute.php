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

use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\Source\Parameter;
use Phramework\Models\Operator;
use Phramework\Validate\StringValidator;
use Phramework\Exceptions\RequestException;

/**
 * Fields helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-read string      $attribute
 * @property-read string      $operator
 * @property-read mixed|null  $operand
 */
class FilterAttribute
{
    const JSON_ATTRIBUTE_PROPERTY_KEY_EXPRESSION = '/^[a-zA-Z_\-0-9]{1,32}$/';

    /**
     * @var string
     */
    protected $attribute;
    /**
     * @var string
     */
    protected $operator;
    /**
     * @var mixed|null
     */
    protected $operand;

    /**
     * FilterAttribute constructor.
     * @param string      $attribute
     * @param string      $operator
     * @param mixed|null $operand
     */
    public function __construct(
        string $attribute,
        string $operator,
        $operand = null
    ) {
        $this->attribute = $attribute;
        $this->operator = $operator;
        $this->operand = $operand;
    }

    /**
     * @param string $filterKey
     * @param string|string[] $filterValue
     * @return FilterAttribute[]|FilterJSONAttribute[]
     * @throws RequestException
     */
    public static function parse($filterKey, $filterValue)
    {
        /**
         * @var FilterAttribute[]
         */
        $filterAttributes = [];

        $isJSONFilter = false;

        //Check if $filterKeyParts and key contains `.` dot character (object dereference operator)
        if (strpos($filterKey, '.') !== false) {
            $filterKeyParts = explode('.', $filterKey);

            if (count($filterKeyParts) > 2) {
                throw new RequestException(
                    'Second level filtering for JSON objects is not available'
                );
            }

            $filterPropertyKey = $filterKeyParts[1];

            //Hack check $filterPropertyKey if valid using regular expression
            //@todo
            (new StringValidator(0, null, self::JSON_ATTRIBUTE_PROPERTY_KEY_EXPRESSION))
                ->setSource(new Parameter('filter[' . $filterKey . ']'))
                ->parse($filterPropertyKey);

            $filterKey = $filterKeyParts[0];

            $isJSONFilter = true;
        }

        //All must be arrays
        if (!is_array($filterValue)) {
            $filterValue = [$filterValue];
        }

        foreach ($filterValue as $singleFilterValue) {
            if (is_array($singleFilterValue)) {
                throw new IncorrectParameterException(
                    'type',
                    sprintf(
                        'Array value given for filter attribute "%s"',
                        $filterKey
                    ),
                    new Parameter('filter[' . $filterKey . ']')
                );
            }

            //@todo is this required?
            $singleFilterValue = urldecode($singleFilterValue);

            list($operator, $operand) = Operator::parse($singleFilterValue);

            //Push to attribute filters

            if ($isJSONFilter) {
                $filterAttributes[] = new FilterJSONAttribute(
                    $filterKey,
                    $filterPropertyKey,
                    $operator,
                    $operand
                );
            } else {
                $filterAttributes[] = new FilterAttribute(
                    $filterKey,
                    $operator,
                    $operand
                );
            }
        }

        return $filterAttributes;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        switch ($name) {
            case 'operand':
                return $this->operand;
            case 'operator':
                return $this->operator;
            case 'attribute':
                return $this->attribute;
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed|null
     */
    public function getOperand()
    {
        return $this->operand;
    }

}
