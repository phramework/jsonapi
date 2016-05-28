<?php

namespace Phramework\JSONAPI;

class InternalModel
{
    /**
     * @var string
     */
    protected $resourceType;

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
     * InternalModel constructor.
     * @param string $resourceType
     */
    public function __construct($resourceType)
    {
        $this->resourceType      = $resourceType;

        $this->defaultDirectives = new \stdClass();
    }

    public function get(IDirective ...$directives) : array
    {
        $get = $this->get;

        if ($get === null) {
            throw new \LogicException('Method "get" is not implemented');
        }

        return $get(...$directives);
    }

    public function getById(string $id, IDirective ...$directives) : Resource
    {
        //todo implement
    }

    /**
     * @param IDirective[] $directives
     * @return $this
     */
    public function addDefaultDirective(IDirective ...$directives)
    {
        foreach ($directives as $directive) {
            $class = get_class($directive);

            $this->defaultDirectives->{$class} = $directive;
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
     */
    public function setGet(callable $get)
    {
        $this->get = $get;

        return $this;
    }


}
