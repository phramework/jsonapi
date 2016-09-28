<?php
declare(strict_types=1);
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
use Phramework\Exceptions\Source\Parameter;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Validate\UnsignedIntegerValidator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Page directive, allows pagination
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Page extends Directive implements \JsonSerializable
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
     * @param int $limit null value is interpreted as "no limit"
     * @param int $offset
     */
    public function __construct(int $limit = null, int $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }

    /**
     * @param ResourceModel $model
     * @throws IncorrectParameterException When limit exceeds resourceModel's maximum page limit
     */
    public function validate(ResourceModel $model) : bool
    {
        if ($this->limit !== null) {
            (new UnsignedIntegerValidator(
                1,
                $model->getMaxPageLimit()
            ))
                ->setSource(new Parameter('page[limit]'))
                ->parse($this->limit);
        }

        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResourceModel          $model
     * @return null|Page
     * @throws \Exception|null
     * @todo use request instead of parameters
     * @uses InternalModel::getMaxPageLimit
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        $param = $request->getQueryParams()['page'] ?? null;

        if (empty($param)) {
            return null;
        }

        $limit  = null;
        $offset = 0;

        //Work with objects
        /*if (is_array($request->page)) {
            $request->page = (object) $request->page;
        }*/

        if (isset($param['limit'])) {
            $limit = (new UnsignedIntegerValidator(
                1,
                $model->getMaxPageLimit()
            ))
                ->setSource(new Parameter('page[limit]'))
                ->parse($param['limit']);
        }

        if (isset($param['offset'])) {
            $offset = (new UnsignedIntegerValidator())
                ->setSource(new Parameter('page[offset]'))
                ->parse($param['offset']);
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
