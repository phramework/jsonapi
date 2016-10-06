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
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class IncludeResources extends Directive
{
    /**
     * @var string[]
     */
    protected $include = [];

    /**
     * IncludeResources constructor.
     * @param string[] $include
     */
    public function __construct(string ...$include)
    {
        $this->include = $include;
    }

    /**
     * @return \string[]
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @param string[] $include
     * @return $this
     */
    public function setInclude(string ...$include)
    {
        $this->include = $include;

        return $this;
    }

    /**
     * @param ResourceModel $model
     * @return bool
     * @throws \DomainException If include relationship is not defined
     *     at resource resourceModel relationships
     */
    public function validate(ResourceModel $model) : bool
    {
        foreach ($this->include as $include) {
            if (!$model->issetRelationship($include)) {
                throw new \DomainException(sprintf(
                    'Relationship "%s" is not defined for resource resourceModel "%s"',
                    $include,
                    $model->getResourceType()
                ));
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     * @todo use include by default class defined in mode's relationships
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {

        $param = $request->getQueryParams()['include'] ?? null;

        if (empty($param)) {
            return null;
        }

        $include = [];

        //split parameter using , (for multiple values)
        foreach (explode(',', $param) as $i) {
            $include[] = trim($i);
        }

        $include =  new IncludeResources(
            ...array_unique($include)
        );
        
        $include->validate($model);
        
        return $include;
    }
}
