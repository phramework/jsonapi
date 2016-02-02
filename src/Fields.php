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

use Exception;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;

/**
 * Fields helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-read object $fields
 */
class Fields
{
    /**
     * @var object
     */
    protected $fields;

    /**
     * @return object
     * @deprecated
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param object|null $fields
     * @throws \Exception
     * @example
     * ```php
     * new Fields((object)
     *     Article::getType() => ['title']
     * ]);
     * ```
     * @example
     * ```php
     * new Fields((object)
     *     Article::getType() => ['title', 'updated'],
     *     Tag::getType()     => ['title']
     * ]);
     * ```
     */
    public function __construct($fields = null)
    {
        if ($fields === null) {
            $this->fields = new \stdClass();
        } else {
            foreach ($fields as $resourceType => $field) {
                if (!is_array($field)) {
                    throw new \Exception(sprintf(
                        'Resource type "%s" fields value expected to be an array',
                        $resourceType
                    ));
                }
            }
            $this->fields = $fields;
        }
    }

    /**
     * @param string          $resourceType
     * @return string[]
     */
    public function get($resourceType)
    {
        if (!isset($this->fields->{$resourceType})) {
            return [];
        }

        return $this->fields->{$resourceType};
    }

    /**
     * @param string          $resourceType
     * @param string|string[] $field
     * @return $this
     */
    public function add($resourceType, $field)
    {
        //Initialize if not set
        if (!isset($this->fields->{$resourceType})) {
            $this->fields->{$resourceType} = [];
        }

        if (!is_array($field)) {
            $field = [$field];
        }

        $this->fields->{$resourceType} = array_unique(array_merge(
            $this->fields->{$resourceType},
            $field
        ));

        return $this;
    }

    /**
     * @param object $parameters
     * @param string $modelClass
     * @return Fields|null
     * @throws RequestException
     * @throws IncorrectParametersException
     * @example
     * ```php
     * $fields = Fields::parseFromParameters(
     *     (object) [
     *         'fields' => [
     *             Article::getType() => ['title, updated'],
     *             Tag::getType()     => ['title']
     *         ]
     *     ], //Request parameters object
     *     Article::class
     * );
     * ```
     */
    public static function parseFromParameters($parameters, $modelClass)
    {
        if (!isset($parameters->fields)) {
            return null;
        }

        $fields = new Fields();

        foreach ($parameters->fields as $resourceType => $value) {
            //check if $resourceType allowed,
            //TODO incomplete since we will support 2nd level relationship data inclusion
            if ($modelClass::getType() == $resourceType
                || $modelClass::relationshipExists($resourceType)
            ) {
            } else {
                throw new RequestException(sprintf(
                    'Not allowed resource type "%s" for fields',
                    $resourceType
                ));
            }

            if (!is_string($value)) {
                throw new IncorrectParametersException(sprintf(
                    'Expecting string value for fields of resource type "%s"',
                    $resourceType
                ));
            }

            $parsedFields = array_map(
                'trim',
                explode(',', trim($value))
            );

            //Validate parsedFields
            //TODO

            //Push parsed fields
            $fields->add($resourceType, $parsedFields);
        }

        return $fields;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        switch ($name) {
            case 'fields':
                return $this->fields;
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }
}
