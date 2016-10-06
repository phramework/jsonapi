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
namespace Phramework\JSONAPI;

use Phramework\JSONAPI\DataSource\DatabaseDataSource;
use Phramework\JSONAPI\DataSource\DataSource;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\Page;
use Phramework\JSONAPI\Model\DataSourceTrait;
use Phramework\JSONAPI\Model\DirectivesTrait;
use Phramework\JSONAPI\Model\RelationshipsTrait;
use Phramework\JSONAPI\Model\VariableTrait;
use Phramework\Validate\ObjectValidator;
use Phramework\Validate\StringValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo define prefix schema, table space for attributes
 * @todo database related, schema table
 * @todo resource parsing
 * @todo links related
 * @todo endpoint related - allow external modifications (?)
 * @todo relationship related and included data
 */
class ResourceModel
{
    use DirectivesTrait;
    use VariableTrait;
    use DataSourceTrait;
    use RelationshipsTrait;
    
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
     * @var StringValidator
     */
    public $idAttributeValidator;
    
    /**
     * InternalModel constructor.
     * Will create a new internal resourceModel initialized with:
     * - defaultDirectives Page directive limit with value of getMaxPageLimit()
     * - empty prepareRecord
     * @param string     $resourceType
     * @param DataSource $dataSource null will interpreted as a new DatabaseDataSource
     */
    public function __construct(string $resourceType, DataSource $dataSource = null)
    {
        $this->resourceType      = $resourceType;

        $this->defaultDirectives    = (object) [
            Page::class => new Page($this->getMaxPageLimit())
        ];
        $this->relationships        = new \stdClass();
        $this->filterableAttributes = new \stdClass();

        $this->initializeVariables();

        //Set default prepareRecord method as an empty method
        $this->prepareRecord = function (array &$record) {
        };

        $this->dataSource = $dataSource;

        if ($dataSource === null) {
            //Set default data source
            $this->dataSource = $dataSource = new DatabaseDataSource(
                $this
            );
        }
        
        $dataSource->setResourceModel($this);

        //Setup default operations to use data source
        $this->get    = [$dataSource, 'get'];
        $this->post   = [$dataSource, 'post'];
        $this->patch  = [$dataSource, 'patch'];
        $this->put    = [$dataSource, 'put'];
        $this->delete = [$dataSource, 'delete'];

        $this->validationModels = new \stdClass();

        //Set default empty filter validator
        $this->filterValidator = new ObjectValidator(
            (object) [],
            [],
            false
        );

        //todo provide better default idAttributeValidator
        $this->idAttributeValidator = new StringValidator(1);
    }

    /**
     * If a validation resourceModel for request method is not found, "DEFAULT" will be used
     * @param string          $requestMethod
     * @return ValidationModel
     * @throws \DomainException If none validation resourceModel is set
     */
    public function getValidationModel(
        string $requestMethod = 'DEFAULT'
    ) : ValidationModel {
        $key = 'DEFAULT';

        if ($requestMethod !== null
            && property_exists($this->validationModels, $requestMethod)
        ) {
            $key = $requestMethod;
        }

        if (!isset($this->validationModels->{$key})) {
            throw new \DomainException(
                'No validation resourceModel is set'
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
        ValidationModel $validationModel,
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
    public function getIdAttribute() : string
    {
        return $this->idAttribute;
    }

    /**
     * @param string $idAttribute If non set, default is "id"
     * @return $this
     */
    public function setIdAttribute(string $idAttribute)
    {
        $this->idAttribute = $idAttribute;

        return $this;
    }

    /**
     * Get idAttribute validator
     * @return StringValidator
     */
    public function getIdAttributeValidator() : StringValidator
    {
        return $this->idAttributeValidator;
    }

    /**
     * @param StringValidator $idAttributeValidator
     * @return $this
     */
    public function setIdAttributeValidator(
        StringValidator $idAttributeValidator
    ) {
        $this->idAttributeValidator  = $idAttributeValidator;
        return $this;
    }

    /**
     * Parse an array of raw records as a collection of Resources
     * @param \stdClass[] $records
     * @param Directive[] $directives
     * @param int         $flags
     * @return Resource[]
     */
    public function collection(
        array $records = [],
        array $directives = [],
        int $flags = Resource::PARSE_DEFAULT
    ) : array {
        return Resource::parseFromRecords(
            $records,
            $this,
            $directives,
            $flags
        );
    }

    /**
     * Parse a record as a Resource
     * @param \stdClass   $record
     * @param Directive[] $directives
     * @param int         $flags
     * @return null|Resource
     */
    public function resource(
        \stdClass $record,
        array $directives = [],
        int $flags = Resource::PARSE_DEFAULT
    ) {
        return Resource::parseFromRecord(
            $record,
            $this,
            $directives,
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
