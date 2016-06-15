<?php

namespace Phramework\JSONAPI\Controller\Helper;

class ResourceQueueItem
{
    /**
     * @var \stdClass
     */
    protected $attributes;

    /**
     * @var \stdClass
     */
    protected $relationships;

    /**
     * ResourceQueueItem constructor.
     * @param \stdClass $attributes
     * @param \stdClass $relationships
     */
    public function __construct(
        \stdClass $attributes,
        \stdClass $relationships
    ) {
        $this->attributes = $attributes;
        $this->relationships = $relationships;
    }

    /**
     * @return \stdClass
     */
    public function getAttributes() : \stdClass
    {
        return $this->attributes;
    }

    /**
     * @return \stdClass
     */
    public function getRelationships() : \stdClass
    {
        return $this->relationships;
    }


}
