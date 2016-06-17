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
namespace Phramework\JSONAPI\DataSource;

use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\ResourceModel;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
abstract class DataSource
{
    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    public abstract  function get(
        Directive ...$directives
    ) : array;
    
    public abstract  function post(
        \stdClass $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    );
    
    public abstract  function patch(
        string $id,
        \stdClass $attributes,
        $return = null
    );

    public abstract  function delete(
        string $id,
        \stdClass $additionalAttributes = null
    );

    /**
     * @param ResourceModel $resourceModel
     * @return DataSource
     */
    public function setResourceModel(
        ResourceModel $resourceModel
    ) : DataSource {
        $this->resourceModel = $resourceModel;

        return $this;
    }

    /**
     * @return ResourceModel
     */
    public function getResourceModel()
    {
        return $this->resourceModel;
    }
}
