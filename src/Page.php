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
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\Source\Parameter;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * Page directive, allows pagination
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add validate method
 */
class Page implements \JsonSerializable, IDirective
{

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @param int|null $limit null value is interpreted as "no limit"
     * @param int $offset
     */
    public function __construct(int $limit = null, int $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }

    /**
     * @param InternalModel $model
     * @throws IncorrectParameterException When limit exceeds model's maximum page limit
     * @uses InternalModel::getMaxPageLimit
     */
    public function validate(InternalModel $model)
    {
        if ($this->limit !== null) {
            (new UnsignedIntegerValidator(
                1,
                $model->getMaxPageLimit()
            ))
                ->setSource(new Parameter('page[limit]'))
                ->parse($this->limit);
        }
    }

    /**
     * @param \stdClass $parameters Request parameters
     * @return Page|null
     * @throws IncorrectParameterException When limit or offset are incorrect
     * @throws IncorrectParameterException When limit exceeds model's maximum page limit
     * @todo use request instead of parameters
     * @uses InternalModel::getMaxPageLimit
     */
    public static function parseFromRequest(
        \stdClass $parameters,
        InternalModel $model
    ) {
        if (!isset($parameters->page)) {
            return null;
        }

        $limit  = null;

        $offset = 0;

        //Work with objects
        if (is_array($parameters->page)) {
            $parameters->page = (object) $parameters->page;
        }

        if (isset($parameters->page->limit)) {
            $limit = (new UnsignedIntegerValidator(
                1,
                $model->getMaxPageLimit()
            ))
                ->setSource(new Parameter('page[limit]'))
                ->parse($parameters->page->limit);
        }

        if (isset($parameters->page->offset)) {
            $offset = (new UnsignedIntegerValidator())
                ->setSource(new Parameter('page[offset]'))
                ->parse($parameters->page->offset);
        }

        return new Page($limit, $offset);
    }

    /**
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset() : int
    {
        return $this->offset;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
