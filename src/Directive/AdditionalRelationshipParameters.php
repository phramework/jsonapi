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
class AdditionalRelationshipParameters extends Directive
{
    /**
     * Structure
     * (object) [
     *     'author' => [1, 'abc']
     * ]
     * @var \stdClass
     */
    protected $relationshipObjects;

    /**
     * AdditionalRelationshipsModelParameter constructor.
     * @param \stdClass $relationshipObjects
     */
    public function __construct(\stdClass $relationshipObjects = null)
    {
        if ($relationshipObjects === null) {
            $relationshipObjects = new \stdClass();
        }

        $this->setRelationshipObjects($relationshipObjects);
    }

    /**
     * @return \stdClass
     */
    public function getRelationshipObjects() : \stdClass
    {
        return $this->relationshipObjects;
    }

    /**
     * @param \stdClass $relationshipObjects
     * @return $this
     */
    public function setRelationshipObjects($relationshipObjects)
    {
        foreach ($relationshipObjects as $relationship => $parameters) {
            assert(is_string($relationship));
            assert(is_array($parameters));
        }

        $this->relationshipObjects = $relationshipObjects;

        return $this;
    }

    /**
     * @param ResourceModel $model
     * @return bool
     * Note it's better not to check the existence of the relationship keys
     * since additional relationship parameters might be used
     * for 2nd level of inclusion
     */
    public function validate(ResourceModel $model) : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        return null;
    }
}
