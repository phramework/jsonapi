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

/**
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add magic set and get, set will instantiate a property if it's null, to reduce output of json to minimal required
 *
 */
class Resource
{
    const PARSE_DEFAULT            = Resource::PARSE_ATTRIBUTES   | Resource::PARSE_LINKS
                                   | Resource::PARSE_RELATIONSHIP | Resource::PARSE_RELATIONSHIP_LINKS;

    const PARSE_ATTRIBUTES              = 1;
    const PARSE_LINKS                   = 2;
    const PARSE_RELATIONSHIP            = 4;
    const PARSE_RELATIONSHIP_LINKS      = 8;
    const PARSE_RELATIONSHIP_ATTRIBUTES = 64;
    /**
     * @deprecated
     */
    const PARSE_META          = 128;

    /**
     * Resource's type
     * @var string
     */
    public $type;

    /**
     * *NOTE* The id member is not required when the resource object originates
     * at the client and represents a new resource to be created on the server.
     * @var string
     */
    public $id;

    /**
     * An attributes object representing some of the resource's data
     * @var object
     */
    public $attributes;

    /**
     * A relationships object describing relationships between the resource and other JSON API resources.
     * @var object
     */
    public $relationships;

    /**
     * A links object containing links related to the resource
     * @var object
     */
    public $links;

    /**
     * Non-standard meta-information about a resource that can not be represented as an attribute or relationship.
     * @var object
     */
    public $meta;

    /**
     * Resource constructor.
     * @param string $type
     * @param string $id
     */
    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id   = (string)$id;

        $this->links         = new \stdClass();
        $this->attributes    = new \stdClass();
        $this->relationships = new \stdClass();
        $this->meta          = new \stdClass();
    }

    public static function parseFromRecords(
        $records,
        $modelClass,
        Fields $fields = null,
        $flags = Resource::PARSE_DEFAULT,
        $meta = null
    ) {
        if (empty($records)) {
            return [];
        }

        $collection = [];

        foreach ($records as $record) {
            //Convert this record to resource object
            $resource = static::parseFromRecord($record, $modelClass, $fields, $flags, $meta);

            //Attach links.self to this resource
            if (!empty($resource)) {
                //Push to collection
                $collection[] = $resource;
            }
        }

        return $collection;
    }

    /**
     * @param array|object $record
     * @param $modelClass
     * @param Fields|null $fields
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
     * @todo Rewrite relationship code
     * @todo WHAT ABOUT meta?
     */
    public static function parseFromRecord(
        $record,
        $modelClass,
        Fields $fields = null,
        $flags = Resource::PARSE_DEFAULT,
        $meta = null
    ) {
        if (empty($record)) {
            return null;
        }

        $flagAttributes              = ($flags & Resource::PARSE_ATTRIBUTES             ) != 0;
        $flagLinks                   = ($flags & Resource::PARSE_LINKS                  ) != 0;
        $flagRelationships           = ($flags & Resource::PARSE_RELATIONSHIP           ) != 0;
        $flagRelationshipsLinks      = ($flags & Resource::PARSE_RELATIONSHIP_LINKS     ) != 0;
        $flagRelationshipsAttributes = ($flags & Resource::PARSE_RELATIONSHIP_ATTRIBUTES) != 0;

        //Work with objects
        if (!is_object($record) && is_array($record)) {
            $record = (object)$record;
        }

        $idAttribute = $modelClass::getIdAttribute();

        if (!isset($record->{$idAttribute})) {
            throw new \Exception(sprintf(
                'Attribute "%s" is not set for record',
                $idAttribute
            ));
        }

        //Determine which class called parsed method
        $resourceClass = static::class;

        //Initialize resource
        $resource = new $resourceClass(
            $modelClass::getType(),
            (string)$record->{$idAttribute}
        );

        //Delete $idAttribute from record's attributes
        unset($record->{$idAttribute});

        //Attach relationships if resource's relationships are set
        if ($flagRelationships && ($relationships = $modelClass::getRelationships())) {

            //Parse relationships
            foreach ($relationships as $relationshipKey => $relationshipObject) {
                //Initialize an new relationship entry object
                $relationshipEntry = new \stdClass();

                //Attach relationship links
                if ($flagRelationshipsLinks) {

                    $relationshipEntry->links = [
                        'self' => $modelClass::getSelfLink(
                            $resource->id . '/relationships/' . $relationshipKey
                        ),
                        'related' => $modelClass::getSelfLink(
                            $resource->id . '/' . $relationshipKey
                        )
                    ];
                }

                $attribute = $relationshipObject->getAttribute();
                $relationshipType = $relationshipObject->getRelationshipType();
                $type = $relationshipObject->getResourceType();

                //If relationship data exists in record's attributes use them
                if (isset($record->{$attribute}) && $record->{$attribute}) {

                    //In case of TYPE_TO_ONE attach single object to data
                    if ($relationshipType == Relationship::TYPE_TO_ONE) {
                        //TODO PARSE AS RESOURCE!
                        //TODO ALLOW An optional set to handle return item else use plain data ??
                        $relationshipEntry->data = (object)[
                            'id' => (string)$record->{$attribute},
                            'type' => $type
                        ];

                    } elseif ($relationshipType == Relationship::TYPE_TO_MANY) {

                        //In case of TYPE_TO_MANY attach an array of objects
                        $relationshipEntry->data = [];

                        foreach ($record->{$attribute} as $k => $d) {
                            if (!is_array($d)) {
                                $d = [$d];
                            }
                            //TODO PARSE AS RESOURCE!
                            foreach ($d as $dd) {
                                //Push object
                                $relationshipEntry->data[] = (object)[
                                    'id' => (string)$dd,
                                    'type' => $type
                                ];
                            }
                        }
                    }
                } else { //Else try to use relationship`s class method to retrieve data
                    //TODO can there be an optional method to return TO_ONE items?

                    if ($relationshipType == Relationship::TYPE_TO_MANY) {
                        $callMethod = [
                            $relationshipObject->getRelationshipClass(),
                            $modelClass::GET_RELATIONSHIP_BY_PREFIX . ucfirst($resource->type)
                        ];
                        //Check if method exists
                        if (is_callable($callMethod)) {
                            $relationshipEntry->data = [];

                            $relationshipEntryResources = call_user_func(
                                $callMethod,
                                $resource->id,
                                $relationshipKey,
                                $fields,
                                $flags // use $flagRelationshipsAttributes to enable/disable parsing of relationship attributes
                            );

                            //TODO Can we detect if array of strings or array of resources is returned
                            //So we can continue to support string[]

                            foreach ($relationshipEntryResources as $k => $d) {
                                //Push object
                                $relationshipEntry->data[] = $d;

                                /*(object)[
                                    'id' => (string)$d,
                                    'type' => $type
                                ];*/
                            }
                        }
                    }
                }

                //Unset this attribute (MUST not be visible in resource's attributes)
                unset($record->{$attribute});

                //Push relationship to relationships
                $resource->relationships->{$relationshipKey} = $relationshipEntry;
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
            )];
        }

        //Return final resource object
        return $resource;
    }
}
