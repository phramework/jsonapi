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

namespace Phramework\JSONAPI;

use Phramework\JSONAPI\Directive\Directive;
use Phramework\Util\Util;

/**
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add magic set and get, set will instantiate a property
 * if it's null, to reduce output of json to minimal required
 * @property-read string $id
 * @property-read string $type
 * @property \stdClass $links
 * @property \stdClass $attributes
 * @property \stdClass $relationships
 * @property \stdClass $private-attributes
 * @property \stdClass $'attributes-meta'
 */
class Resource extends \stdClass implements \JsonSerializable
{
    const META_MEMBER = 'attributes-meta';

    const PARSE_DEFAULT            = Resource::PARSE_ATTRIBUTES
        | Resource::PARSE_LINKS
        | Resource::PARSE_RELATIONSHIP
        | Resource::PARSE_RELATIONSHIP_DATA //todo remove ?
        | Resource::PARSE_RELATIONSHIP_LINKS;

    const PARSE_ATTRIBUTES              = 1;
    const PARSE_LINKS                   = 2;
    const PARSE_RELATIONSHIP            = 4;
    const PARSE_RELATIONSHIP_LINKS      = 8;
    const PARSE_RELATIONSHIP_DATA       = 16;

    /**
     * @deprecated
     */
    const PARSE_META          = 128;

    /**
     * Resource's type
     * @var string
     */
    protected $type;

    /**
     * *NOTE* The id member is not required when the resource object originates
     * at the client and represents a new resource to be created on the server.
     * @var string
     */
    protected $id;

    /**
     * Resource constructor.
     * @param string $type
     * @param string $id
     */
    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id   = $id;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'id':
            case 'type':
                return true;
            case 'links':
            case 'attributes':
            case 'relationships':
            case 'meta':
            case 'private-attributes':
                return isset($this->{$name});
        }

        return false;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if (in_array($name, [
            'links',
            'attributes',
            'relationships',
            'meta',
            'private-attributes'
        ])) {
            if (!isset($this->{$name}) || $this->{$name} === null) {
                $this->{$name} = new \stdClass();
            }

            $this->{$name} = $value;
        } elseif (in_array($name, ['id', 'type'])) {
            $this->{$name} = (string) $value;
        } else {
            throw new \Exception(sprintf(
                'Undefined property via __set(): %s',
                $name
            ));
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        if (in_array($name, ['id', 'type'])) {
            return $this->{$name};
        } elseif (in_array($name, [
            'links',
            'attributes',
            'relationships',
            'meta',
            'private-attributes'
        ])) {
            return (
                isset($this->{$name})
                ? $this->{$name}
                : null
            );
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }

    /**
     * @param (array|\stdClass)[] $records
     * @param ResourceModel $model
     * @param Directive[]   $directives
     * @param int           $flags
     * @return Resource[]
     * @throws \Exception
     */
    public static function parseFromRecords(
        array $records,
        ResourceModel $model,
        array $directives = [],
        int $flags = Resource::PARSE_DEFAULT
    ) : array {
        if (empty($records)) {
            return [];
        }

        //Initialize collection
        $collection = [];

        foreach ($records as $record) {
            //Parse resource from record
            $resource = static::parseFromRecord(
                (
                    is_array($record)
                    ? (object) $record
                    : $record
                ),
                $model,
                $directives,
                $flags
            );

            if (!empty($resource)) {
                //Push to collection
                $collection[] = $resource;
            }
        }

        return $collection;
    }

    /**
     * @param array|\stdClass $record
     * @param ResourceModel   $model
     * @param Directive[]    $directives
     * @param int             $flags
     * @return Resource|null
     * @throws \Exception
     * @example
     * ```php
     * Resource::parseFromRecord(
     *     [
     *         'id'     => '1',
     *         'status' => 'enabled',
     *         'title'  => 'blog'
     *     ],
     *     $resourceModel
     * );
     * ```
     * @todo what about getRelationshipData method ?
     */
    public static function parseFromRecord(
        \stdClass $record,
        ResourceModel $model,
        array $directives = [],
        int $flags = Resource::PARSE_DEFAULT
    ) {
        if (empty($record)) {
            return null;
        }

        $flagAttributes    = ($flags & Resource::PARSE_ATTRIBUTES) != 0;
        $flagLinks         = ($flags & Resource::PARSE_LINKS) != 0;
        $flagRelationships = ($flags & Resource::PARSE_RELATIONSHIP) != 0;
        $flagRelationshipLinks       = ($flags & Resource::PARSE_RELATIONSHIP_LINKS) != 0;
        $flagRelationshipData        = ($flags & Resource::PARSE_RELATIONSHIP_DATA) != 0;


        $idAttribute = $model->getIdAttribute();

        if (!isset($record->{$idAttribute})) {
            throw new \Exception(sprintf(
                'Attribute "%s" is not set for record of type "%s"',
                $idAttribute,
                $model->getResourceType()
            ));
        }

        //Determine in which class parse method was called (create a Resource or a RelationshipResource)
        $resourceClass = static::class;

        //Initialize resource
        $resource = new $resourceClass(
            $model->getResourceType(),
            (string) $record->{$idAttribute}
        );

        //Delete $idAttribute from record's attributes
        unset($record->{$idAttribute});

        //Attach resource resource if META_ATTRIBUTE is available
        if (isset($record->{Resource::META_MEMBER})) {
            $meta = $record->{Resource::META_MEMBER};

            if (is_array($meta) && Util::isArrayAssoc($meta)) {
                $meta = (object) $meta;
            }

            if (!is_object($meta)) {
                throw new \Exception(sprintf(
                    'The value of meta member MUST be an object for resource with id "%s" of type "%s"',
                    $resource->id,
                    $resource->type
                ));
            }

            //Push meta
            $resource->meta = $meta;

            unset($record->{Resource::META_MEMBER});
        }

        //Parse private attributes first
        $privateAttributes = new \stdClass();
        foreach ($model->getPrivateAttributes() as $attribute) {
            if (property_exists($record, $attribute)) {
                $privateAttributes->{$attribute} = $record->{$attribute};
                unset($record->{$attribute});
            }
        }

        if (count((array) $privateAttributes)) {
            $resource->{'private-attributes'} = $privateAttributes;
        }

        if ($flagAttributes) {
            $resource->attributes = new \stdClass();
        }

        //Attach relationships if resource's relationships are set
//        if (/*$flagRelationships &&*/ ($relationships = $resourceModel->getRelationships())) {

            $resourceRelationships = new \stdClass();
            //Parse relationships
            foreach ($model->getRelationships() ?? [] as $relationshipKey => $relationshipObject) {
                //Initialize an new relationship entry object
                $relationshipEntry = new \stdClass();

                //Attach relationship links
                //TODO RESTORE
               /* if ($flagRelationshipLinks) {
                    $relationshipEntry->links = [
                        'self' => $resourceModel::getSelfLink(
                            $resource->id . '/relationships/' . $relationshipKey
                        ),
                        'related' => $resourceModel::getSelfLink(
                            $resource->id . '/' . $relationshipKey
                        )
                    ];
                }*/

                $relationshipFlagData = ($relationshipObject->getFlags() & Relationship::FLAG_DATA) != 0;

                /**
                 * @var ResourceModel
                 */
                $relationshipModel   = $relationshipObject->getResourceModel();
                $relationshipType    = $relationshipObject->getType();
                $recordDataAttribute = $relationshipObject->getRecordDataAttribute();

                $relationshipResourceType = $relationshipModel->getResourceType();

                //todo Define callback signature to fetch data
                /**
                 * @return string[]|RelationshipResource[]
                 */
                $dataCallback = function () use (
                    $relationshipObject,
                    $resource,
                    $relationshipKey,
                    $directives,
                    $flags
                ) {
                    return $relationshipObject->getCallbacks()->{'GET'}(
                        $resource->id,
                        $directives,
                        $flags // use $flagRelationshipsAttributes to enable/disable parsing of relationship attributes
                    );
                };
                
                if ($flagRelationships && ($flagRelationshipData || $relationshipFlagData)) {
                    //Include data only if $flagRelationshipData is true
                    if ($relationshipType === Relationship::TYPE_TO_ONE) {
                        $relationshipEntryResource = null;

                        if (isset($record->{$recordDataAttribute}) && $record->{$recordDataAttribute}) { //preloaded
                            $relationshipEntryResource = $record->{$recordDataAttribute};
                        } elseif (isset($relationshipObject->getCallbacks()->{'GET'})) { //available from callback
                            $relationshipEntryResource = $dataCallback();
                        }

                        if ($relationshipEntryResource !== null) {
                            //parse relationship resource
                            if (is_string($relationshipEntryResource)) {
                                //If returned $relationshipEntryResource is an id string
                                $relationshipEntry->data = new RelationshipResource(
                                    $relationshipResourceType,
                                    (string) $relationshipEntryResource
                                );
                            } elseif ($relationshipEntryResource instanceof RelationshipResource) {
                                //If returned $relationshipEntryResource is RelationshipResource
                                $relationshipEntry->data = $relationshipEntryResource;
                            } else {
                                throw new \Exception(sprintf(
                                    'Unexpected relationship entry resource of relationship "%s",'
                                    . ' expecting string or RelationshipResource "%s" given',
                                    $relationshipKey,
                                    gettype($relationshipEntryResource)
                                ));
                            }
                        }
                    } elseif ($relationshipType === Relationship::TYPE_TO_MANY) {
                        //Initialize
                        $relationshipEntry->data = [];

                        $relationshipEntryResources = [];

                        if (isset($record->{$recordDataAttribute}) && $record->{$recordDataAttribute}) { //preloaded
                            $relationshipEntryResources = $record->{$recordDataAttribute};
                        } elseif (isset($relationshipObject->getCallbacks()->{'GET'})) { //available from callback
                            $relationshipEntryResources = $dataCallback();
                        }

                        if (!is_array($relationshipEntryResources)) {
                            throw new \Exception(sprintf(
                                'Expecting array for relationship entry resources of relationship "%s"',
                                $relationshipKey
                            ));
                        }

                        //Parse relationship resources

                        if (Util::isArrayOf($relationshipEntryResources, 'string')) {
                            //If returned $relationshipEntryResources are id strings
                            foreach ($relationshipEntryResources as $relationshipEntryResourceId) {
                                $relationshipEntry->data[] = new RelationshipResource(
                                    $relationshipResourceType,
                                    (string)$relationshipEntryResourceId
                                );
                            }
                        } elseif (Util::isArrayOf($relationshipEntryResources, RelationshipResource::class)) {
                            //If returned $relationshipEntryResources are RelationshipResource
                            $relationshipEntry->data = $relationshipEntryResources;
                        } else {
                            throw new \Exception(sprintf(
                                'Unexpected relationship entry resources of relationship "%s",'
                                . ' expecting string or RelationshipResource "%s" given',
                                $relationshipKey,
                                gettype($relationshipEntryResources[0])
                            ));
                        }
                    }
                }

                if ($recordDataAttribute !== null) {
                    //Unset this attribute (MUST not be visible in resource's attributes)
                    unset($record->{$recordDataAttribute});
                }

                //Push relationship to relationships
                $resourceRelationships->{$relationshipKey} = $relationshipEntry;
            }


            //Attach only if set
            if ($flagRelationships) {
                $resource->relationships = $resourceRelationships;
            }
        //}

        //Attach resource attributes
        if ($flagAttributes) {
            $resource->attributes = $record;
        }

        //Attach resource link
        //TODO RESTORE
        /*if ($flagLinks) {
            $resource->links = (object) [
                'self' => $resourceModel::getSelfLink(
                    $resource->id
                )
            ];
        }*/

        return $resource;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        //Important
        unset($vars['private-attributes']);

        return $vars;
    }

    /**
     * Get resource id
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Get resource type
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return \stdClass
     */
    public function getLinks() : \stdClass
    {
        return $this->links;
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
    public function getPrivateAttributes() : \stdClass
    {
        return $this->{'private-attributes'};
    }

    /**
     * @return \stdClass
     */
    public function getRelationships() : \stdClass
    {
        return $this->relationships;
    }
}
