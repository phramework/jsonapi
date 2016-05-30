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
namespace Phramework\JSONAPI;

use Phramework\JSONAPI\DataSource\DatabaseDataSource;
use Phramework\JSONAPI\Model\DataSource;
use Phramework\JSONAPI\Model\Directives;
use Phramework\JSONAPI\Model\Relationships;
use Phramework\JSONAPI\Model\Settings;
use Phramework\Validate\ObjectValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo define prefix schema, table space for attributes
 * @todo post, patch, delete methods
 * @todo handleGet and related helper methods
 * @todo database related, schema table
 * @todo resource parsing
 * @todo links related
 * @todo endpoint related
 * @todo relationship related and included data
 */
class InternalModel
{
    use Directives;
    use Settings;
    use DataSource;
    use Relationships;
    
    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var string
     */
    protected $idAttribute = 'id';

    /**
     * @var \stdClass
     */
    protected $validationModels;

    /**
     * @var ObjectValidator
     */
    public $filterValidator;
    
    /**
     * InternalModel constructor.
     * Will create a new internal model initialized with:
     * - defaultDirectives Page directive limit with value of getMaxPageLimit()
     * @param string $resourceType
     */
    public function __construct($resourceType)
    {
        $this->resourceType      = $resourceType;

        $this->defaultDirectives    = (object) [
            Page::class => new Page($this->getMaxPageLimit())
        ];
        $this->relationships        = new \stdClass();
        $this->filterableAttributes = new \stdClass();

        $this->settings = new \stdClass();
        
        $model = $this; //alias

        $this->prepareRecords = function (array &$records) {

        };

        $dataSource = $this->dataSource = new DatabaseDataSource(
            $model
        );

        $this->get = function () use ($dataSource) {
            return $dataSource->get(
                ...func_get_args()
            );
        };

        $this->post = function () use ($dataSource) {
            return $dataSource->post(
                ...func_get_args()
            );
        };

        $this->patch = function () use ($dataSource) {
            return $dataSource->patch(
                ...func_get_args()
            );
        };

        $this->put = function () use ($dataSource) {
            return $dataSource->put(
                ...func_get_args()
            );
        };

        //experiment
        $this->delete = [$dataSource, 'delete'];

        $this->validationModels = new \stdClass();

        $this->filterValidator = new ObjectValidator(
            (object) [],
            [],
            false
        );
    }

    /**
     * If a validation model for request method is not found, "DEFAULT" will be used
     * @param string          $requestMethod
     * @return ValidationModel
     * @throws \DomainException If none validation model is set
     */
    public function getValidationModel(string $requestMethod = 'DEFAULT')
    {
        $key = 'DEFAULT';

        if ($requestMethod !== null
            && property_exists($this->validationModels, $requestMethod)
        ) {
            $key = $requestMethod;
        }

        if (!isset($this->validationModels->{$key})) {
            throw new \DomainException(
                'No validation model is set'
            );
        }

        return $this->validationModels->{$key};
    }

    /**
     * @param ValidationModel $validationModel
     * @param string          $requestMethod
     * @return $this
     */
    public function setValidationModel(
        $validationModel,
        string $requestMethod = 'DEFAULT'
    ) {
        $key = 'DEFAULT';

        if ($requestMethod !== null) {
            $key = $requestMethod;
        }

        $this->validationModels->{$key} = $validationModel;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @param string $resourceType
     * @return $this
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }
    
    /**
     * @return string
     */
    public function getIdAttribute()
    {
        return $this->idAttribute;
    }

    /**
     * @return \stdClass
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    public function collection(
        array $records = [],
        array $directives = null,
        $flags = Resource::PARSE_DEFAULT
    ) {
        return Resource::parseFromRecords(
            $records,
            $this,
            null,//$fields,
            $flags
        );
    }

    public function resource(
        $record,
        array $directives = null,
        $flags = Resource::PARSE_DEFAULT
    ) {
        return Resource::parseFromRecord(
            $record,
            $this,
            null,//$fields,
            $flags
        );
    }

    /**
     * @return ObjectValidator
     */
    public function getFilterValidator() : ObjectValidator
    {
        return $this->filterValidator;
    }

    /**
     * @param ObjectValidator $filterValidator
     * @return $this
     */
    public function setFilterValidator(ObjectValidator $filterValidator)
    {
        $this->filterValidator = $filterValidator;

        return $this;
    }
}