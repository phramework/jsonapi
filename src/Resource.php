<?php
/**
 * Copyright 2015 - 2016 Xenofon Spafaridis
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

use Phramework\Phramework;
use Phramework\Util\Util;

/**
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add magic set and get, set will instantiate a property
 * if it's null, to reduce output of json to minimal required
 * @property-read string $id
 * @property-read string $type
 * @property string $links
 * @property string $attributes
 * @property string $relationships
 * @property string $data
 */
class Resource extends \stdClass implements \JsonSerializable
{
    const META_MEMBER = 'attributes-meta';

    const PARSE_DEFAULT            = Resource::PARSE_ATTRIBUTES   | Resource::PARSE_LINKS
                                   | Resource::PARSE_RELATIONSHIP | Resource::PARSE_RELATIONSHIP_LINKS; //15

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
    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id   = (string) $id;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if (in_array($name, ['links', 'attributes', 'relationships', 'meta', 'private-attributes'])) {
            if (!isset($this->{$name}) || $this->{$name} === null) {
                $this->{$name} = new \stdClass();
            }

            $this->{$name} = $value;
        } elseif (in_array($name, ['id', 'type'])) {
            $this->{$name} = $value;
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
        } elseif (in_array($name, ['links', 'attributes', 'relationships', 'meta', 'private-attributes'])) {
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

    public static function parseFromRecords(
        $records,
        $modelClass,
        Fields $fields = null,
        $flags = Resource::PARSE_DEFAULT
    ) {
        if (empty($records)) {
            return [];
        }

        //Initialize collection
        $collection = [];

        foreach ($records as $record) {
            //Parse resource from record
            $resource = static::parseFromRecord($record, $modelClass, $fields, $flags);

            if (!empty($resource)) {
                //Push to collection
                $collection[] = $resource;
            }
        }

        return $collection;
    }

    /**
     * @param array|object $record
     * @param string       $modelClass
     * @param Fields|null  $fields
     * @param $flags
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
     *     Tag::class
     * );
     * ```
     * @todo what about getRelationshipData method ?
     */
    public static function parseFromRecord(
        $record,
        $modelClass,
        Fields $fields = null,
        $flags = Resource::PARSE_DEFAULT
    ) {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \Exception(sprintf(
                'modelClass MUST extend "%s"',
                Model::class
            ));
        }

        if (empty($record)) {
            return null;
        }

        $flagAttributes              = ($flags & Resource::PARSE_ATTRIBUTES) != 0;
        $flagLinks                   = ($flags & Resource::PARSE_LINKS) != 0;
        $flagRelationships           = ($flags & Resource::PARSE_RELATIONSHIP) != 0;
        $flagRelationshipLinks       = ($flags & Resource::PARSE_RELATIONSHIP_LINKS) != 0;
        $flagRelationshipData        = ($flags & Resource::PARSE_RELATIONSHIP_DATA) != 0;

        //Work with objects
        if (!is_object($record) && is_array($record)) {
            $record = (object) $record;
        }

        $idAttribute = $modelClass::getIdAttribute();

        if (!isset($record->{$idAttribute})) {
            throw new \Exception(sprintf(
                'Attribute "%s" is not set for record',
                $idAttribute
            ));
        }

        //Determine in which class parse method was called (create a Resource or a RelationshipResource)
        $resourceClass = static::class;

        //Initialize resource
        $resource = new $resourceClass(
            $modelClass::getType(),
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

        foreach ($modelClass::getPrivateAttributes() as $attribute) {
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
        if (/*$flagRelationships &&*/ ($relationships = $modelClass::getRelationships())) {
            $resourceRelationships = new \stdClass();
            //Parse relationships
            foreach ($relationships as $relationshipKey => $relationshipObject) {
                //Initialize an new relationship entry object
                $relationshipEntry = new \stdClass();

                //Attach relationship links
                if ($flagRelationshipLinks) {
                    $relationshipEntry->links = [
                        'self' => $modelClass::getSelfLink(
                            $resource->id . '/relationships/' . $relationshipKey
                        ),
                        'related' => $modelClass::getSelfLink(
                            $resource->id . '/' . $relationshipKey
                        )
                    ];
                }
                $relationshipFlagData     = ($relationshipObject->flags & Relationship::FLAG_DATA) != 0;

                $relationshipModelClass   = $relationshipObject->modelClass;
                $relationshipType         = $relationshipObject->type;
                $recordDataAttribute      = $relationshipObject->recordDataAttribute;

                $relationshipResourceType = $relationshipModelClass::getType();

                //Define callback to fetch data
                /**
                 * @return string[]|RelationshipResource[]
                 */
                $dataCallback = function () use (
                    $relationshipObject,
                    $resource,
                    $relationshipKey,
                    $fields,
                    $flags
                ) {
                    return call_user_func(
                        $relationshipObject->callbacks->{Phramework::METHOD_GET},
                        $resource->id,
                        $fields,
                        $flags // use $flagRelationshipsAttributes to enable/disable parsing of relationship attributes
                    );
                };

                if ($flagRelationships && ($flagRelationshipData || $relationshipFlagData)) {
                    //Include data only if $flagRelationshipData is true
                    if ($relationshipType == Relationship::TYPE_TO_ONE) {
                        $relationshipEntryResource = null;

                        if (isset($record->{$recordDataAttribute}) && $record->{$recordDataAttribute}) { //preloaded
                            $relationshipEntryResource = $record->{$recordDataAttribute};
                        } elseif (isset($relationshipObject->callbacks->{Phramework::METHOD_GET})) { //available from callback
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
                    } elseif ($relationshipType == Relationship::TYPE_TO_MANY) {
                        //Initialize
                        $relationshipEntry->data = [];

                        $relationshipEntryResources = [];

                        if (isset($record->{$recordDataAttribute}) && $record->{$recordDataAttribute}) { //preloaded
                            $relationshipEntryResources = $record->{$recordDataAttribute};
                        } elseif (isset($relationshipObject->callbacks->{Phramework::METHOD_GET})) { //available from callback
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
                            $relationshipEntry->data[] = $relationshipEntryResources;
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
        }

        //Attach resource attributes
        if ($flagAttributes) {
            $resource->attributes = (object) $record;
        }

        //Attach resource link
        if ($flagLinks) {
            $resource->links = (object) [
                'self' => $modelClass::getSelfLink(
                    $resource->id
                )
            ];
        }

        //Return final resource object
        return $resource;
    }

    public function jsonSerialize()
    {
        $vars = get_object_vars($this);

        unset($vars['private-attributes']);

        return $vars;
    }
}
