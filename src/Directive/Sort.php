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
use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param ResourceModel $model
     * @todo implement
     */
    public function validate(ResourceModel $model) : bool
    {
        //todo
        return true;
    }

    /**
     * @param ServerRequestInterface $request Request parameters
     * @param ResourceModel|string   $model
     * @return Sort|null
     * @throws RequestException
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        $param = $request->getQueryParams()['sort'] ?? null;

        if (empty($param)) {
            return null;
        }

        //Don't accept arrays

        if (isset($param)) {
            if (!is_string($param)) {
                throw new RequestException(
                    'String expected for sort'
                );
            }

            $sortableAttributes = $model->getSortableAttributes();

            if (empty($sortableAttributes)) {
                throw new RequestException('Not sortable attributes for this resource resourceModel');
            }

            //Check attribute is in resource resourceModel's sortable and parse if is descending
            $validateExpression = sprintf(
                '/^(?P<descending>\-)?(?P<attribute>%s)$/',
                implode('|', array_map(
                    'preg_quote',
                    $sortableAttributes,
                    ['/']
                ))
            );

            if (!!preg_match($validateExpression, $param, $matches)) {
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
