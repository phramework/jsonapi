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

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class InternalModel
{
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
    protected $relationships;

    /**
     * @var callable
     */
    protected $get;

    /**
     * @var \stdClass
     */
    protected $defaultDirectives;

    /**
     * @var ValidationModel
     */
    protected $validationModel;

    /**
     * @var string[]
     */
    protected $supportedDirectives = [];

    /**
     * InternalModel constructor.
     * @param string $resourceType
     */
    public function __construct($resourceType)
    {
        $this->resourceType      = $resourceType;

        $this->defaultDirectives = new \stdClass();
        $this->relationships     = new \stdClass();
    }

    /**
     * @param IDirective[] ...$directives
     * @return Resource[]
     */
    public function get(IDirective ...$directives) : array
    {
        $get = $this->get;

        if ($get === null) {
            throw new \LogicException('Method "get" is not implemented');
        }

        return $get(...$directives);
    }

    /**
     * @param string|string[] $id
     * @param IDirective[] ...$directives
     * @return Resource|\stdClass|null
     * @todo rewrite cache
     */
    public function getById($id, IDirective ...$directives)
    {
        $collectionObject = new \stdClass();

        if (FALSE && !is_array($id) && ($cached = static::getCache($id)) !== null) {
            //Return a single resource immediately if cached
            return $cached;
        } elseif (is_array($id)) {
            $id = array_unique($id);

            $originalId = $id;

            foreach ($originalId as $resourceId) {
                $collectionObject->{$resourceId} = null;
                if (FALSE && ($cached = static::getCache($resourceId)) !== null) {
                    $collectionObject->{$resourceId} = $cached;
                    //remove $resourceId from id array, so we wont request the same item again,
                    //but it will be returned in $collectionObject
                    $id = array_diff($id, [$resourceId]);
                }
            }

            //If all ids are already available from cache, return immediately
            if (count($id) === 0) {
                return $collectionObject;
            }
        }

        $passedfilter = array_filter(
            $directives,
            function ($directive) {
                return get_class($directive) == Filter::class;
            }
        );

        $defaultFilter = $this->getDefaultDirectives()->{Filter::class} ?? null;

        //Prepare filter
        $filter = new Filter(
            is_array($id)
            ? $id
            : [$id]
        ); //Force array for primary data

        if (!empty($passedfilter)) {
            //merge with passed
            $filter = Filter::merge(
                $passedfilter[0],
                $filter
            );
        } elseif ($defaultFilter !== null) {
            //merge with default
            $filter = Filter::merge(
                $defaultFilter,
                $filter
            );
        }

        //remove already set page and filter
        $toPassDirectives = array_filter(
            $directives,
            function ($directive) {
                return !in_array(
                    get_class($directive),
                    [
                        Page::class,
                        Filter::class
                    ]
                );
            }
        );

        //pass new directives
        $toPassDirectives[] = $filter;
        $toPassDirectives[] = new Page(count($id));

        $collection = $this->get(
            ...$toPassDirectives
        );

        if (!is_array($id)) {
            if (empty($collection)) {
                return null;
            }

            //Store resource
            //FALSE && static::setCache($id, $collection[0]);

            //Return a resource
            return $collection[0];
        }

        //If ids are an array
        foreach ($collection as $resource) {
            $collectionObject->{$resource->id} = $resource;
            //FALSE && static::setCache($resource->id, $resource);
        }

        unset($collection);

        return $collectionObject;
    }

    /**
     * Add default directive values
     * Only one default value per directive class is allowed
     * It will include any directive class that are missing to supported directive class
     * @param IDirective[] $directives
     * @return $this
     */
    public function addDefaultDirective(IDirective ...$directives)
    {
        foreach ($directives as $directive) {
            $class = get_class($directive);

            $this->defaultDirectives->{$class} = $directive;

            if (!in_array($class, $this->supportedDirectives, true)) {
                $this->supportedDirectives[] = $class;
            }
        }

        return $this;
    }

    /**
     * @return ValidationModel
     */
    public function getValidationModel()
    {
        return $this->validationModel;
    }

    /**
     * @param ValidationModel $validationModel
     * @return $this
     */
    public function setValidationModel($validationModel)
    {
        $this->validationModel = $validationModel;

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
     * @param callable $get
     * @return $this
     * @todo validate callable using https://secure.php.net/manual/en/reflectionfunction.construct.php
     */
    public function setGet(callable $get)
    {
        $this->get = $get;

        return $this;
    }

    /**
     * Returns an array with class names of supported directives
     * @return string[]
     */
    public function getSupportedDirectives()
    {
        return $this->supportedDirectives;
    }

    /**
     * @param string[] $directiveClassName
     * @return $this
     * @throws \InvalidArgumentException If a class name is not implementing IDirective interface
     */
    public function setSupportedDirectives(string ...$directiveClassName)
    {
        foreach ($directiveClassName as $className) {
            if (!in_array(
                IDirective::class,
                class_implements($directiveClassName)
            )) {
                throw new \InvalidArgumentException(sprintf(
                    'Class "%s" is not implementing interface "%s"',
                    $className,
                    IDirective::class
                ));
            }
        }
        $this->supportedDirectives = $directiveClassName;

        return $this;
    }

    /**
     * @return \stdClass
     */
    public function getDefaultDirectives()
    {
        return $this->defaultDirectives;
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


    public function collection(array $records = [], Fields $fields = null, $flags = Resource::PARSE_DEFAULT)
    {
        return Resource::parseFromRecords(
            $records,
            $this,
            $fields,
            $flags
        );
    }

    public function resource($record, Fields $fields = null, $flags = Resource::PARSE_DEFAULT)
    {
        return Resource::parseFromRecord(
            $record,
            $this,
            $fields,
            $flags
        );
    }
}
