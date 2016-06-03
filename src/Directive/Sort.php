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

use Phramework\Exceptions\RequestException;
use Phramework\JSONAPI\InternalModel;

/**
 * Sort helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo allow multiple values for sort
 */
class Sort extends Directive
{
    /**
     * @var bool
     */
    protected $ascending;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * Sort constructor.
     * @param string $attribute
     * @param bool   $ascending
     */
    public function __construct(
        $attribute, $ascending = true
    ) {
        $this->attribute = $attribute;
        $this->ascending = $ascending;
    }

    /**
     * @param InternalModel $model
     * @todo implement
     */
    public function validate(InternalModel $model) : bool
    {
        //todo
        return true;
    }

    /**
     * @param object $parameters Request parameters
     * @param string $model
     * @return Sort
     * ```php
     * $sort = Sort::parseFromParameters(
     *     (object) [
     *         'sort' => '-updated'
     *     ], //Request parameters object
     *     Article::class
     * );
     * ```
     * @throws RequestException
     */
    public static function parseFromRequest(\stdClass $parameters, InternalModel $model)
    {
        $sortableAttributes = $model->getSortableAttributes();

        //Don't accept arrays

        if (isset($parameters->sort)) {
            if (!is_string($parameters->sort)) {
                throw new RequestException(
                    'String expected for sort'
                );
            }

            if (empty($sortableAttributes)) {
                throw new RequestException('Not sortable attributes for this resource model');
            }

            //Check attribute is in resource model's sortable and parse if is descending
            $validateExpression = sprintf(
                '/^(?P<descending>\-)?(?P<attribute>%s)$/',
                implode('|', array_map(
                    'preg_quote',
                    $sortableAttributes,
                    ['/']
                ))
            );

            if (!!preg_match($validateExpression, $parameters->sort, $matches)) {
                return new Sort(
                    $matches['attribute'],
                    (
                        isset($matches['descending']) && $matches['descending']
                        ? false
                        : true
                    )
                );
            } else {
                throw new RequestException(
                    'Invalid value for sort'
                );
            }
        }

        return null;
    }

    /**
     * @return boolean
     */
    public function getAscending()
    {
        return $this->ascending;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }
}
