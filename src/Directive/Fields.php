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
namespace Phramework\JSONAPI\Directive;

use Exception;
use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\IncorrectParametersException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\Parameter;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Util\Util;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Fields helper methods
 * @since 1.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class Fields extends Directive
{
    /**
     * @var \stdClass
     */
    protected $fields;

    /**
     * @return \stdClass
     */
    public function getFields() : \stdClass
    {
        return $this->fields;
    }

    /**
     * @param \stdClass|null $fields
     * @throws \Exception
     * @example
     * ```php
     * new Fields((object)
     *     'article' => ['title']
     * ]);
     * ```
     * @example
     * ```php
     * new Fields((object)
     *     'article' => ['title', 'updated'],
     *     'tag'     => ['title']
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
    public function get(string $resourceType) : array
    {
        if (!isset($this->fields->{$resourceType})) {
            return [];
        }

        return $this->fields->{$resourceType};
    }

    /**
     * @param string          $resourceType
     * @param string[] $fields
     * @return $this
     */
    public function add(string $resourceType,string ...$fields)
    {
        //Initialize if not set
        if (!isset($this->fields->{$resourceType})) {
            $this->fields->{$resourceType} = [];
        }

        $this->fields->{$resourceType} = array_unique(array_merge(
            $this->fields->{$resourceType},
            $fields
        ));

        return $this;
    }

    /**
     * @param ResourceModel $model
     * @throws \Exception
     */
    public function validate(ResourceModel $model) : bool
    {
        //todo
        return true;
    }

    /**
     * @param ServerRequestInterface $request Request parameters
     * @param ResourceModel          $model   Primary model class
     * @return Sort|null
     * @throws IncorrectParameterException
     * @uses Model::getFields for each resource type to parse allowed fields
     */
    public static function parseFromRequest(
        ServerRequestInterface $request,
        ResourceModel $model
    ) {
        $param = $request->getQueryParams()['fields'] ?? null;

        if (empty($param)) {
            return null;
        }

        $fields = new Fields();

        if (!is_object($param)
            && (
                !is_array($param)
                || (is_array($param)
                   && !Util::isArrayAssoc($param)
                )
            )
        ) {
            throw new IncorrectParameterException(
                'type',
                'Fields directive must specify resource type',
                new Parameter('fields')
            );
        }

        foreach ($param as $resourceType => $value) {
            if ($model->getResourceType() === $resourceType) {
                //check if $resourceType allowed (primary)
                $resourceModel = $model;
            } elseif ($model->issetRelationship($resourceType)) {
                //check if $resourceType allowed (primary's relationships)
                $resourceModel = $model->getRelationship($resourceType)
                    ->getModel();
            } else {
                //TODO incomplete since we will support 2nd level relationship data inclusion
                throw new IncorrectParameterException(
                    'enum',
                    sprintf(
                        'Not allowed resource type "%s" for fields',
                        $resourceType
                    ),
                    new Parameter('fields[' . $resourceType . ']')
                );
            }

            if (!is_string($value)) {
                throw new IncorrectParameterException(
                    'type',
                    sprintf(
                        'Expecting string value for fields of resource type "%s"',
                        $resourceType
                    ),
                    new Parameter('fields[' . $resourceType . ']')
                );
            }

            $parsedFields = array_map(
                'trim',
                explode(',', trim($value))
            );

            $allowedFields = $resourceModel->getFieldableAtributes();

            //Validate parsedFields (allowed in $model)
            foreach ($parsedFields as $parsedField) {
                if (!in_array($parsedField, $allowedFields)) {
                    throw new IncorrectParameterException(
                        'enum',
                        sprintf(
                            'Field "%s" is not allowed for field directive of resource type "%s"',
                            $parsedField,
                            $resourceType
                        ),
                        new Parameter('fields[' . $resourceType . ']')
                    );
                }
            }

            //Push parsed fields
            $fields->add($resourceType, ...$parsedFields);
        }

        return $fields;
    }
}
