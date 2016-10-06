<?php
declare(strict_types=1);
/*
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

use Phramework\JSONAPI\ResourceModel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Additional parameters required to get for primary resource
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class AdditionalParameters extends Directive
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * AdditionalModelParameter constructor.
     * @param mixed[] $parameters
     */
    public function __construct(...$parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @param mixed[] $parameters
     * @return $this
     */
    public function setParameters(...$parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param ResourceModel $model
     * @return bool
     */
    public function validate(ResourceModel $model) : bool
    {
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResourceModel          $model
     * @return null
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        return null;
    }
}
