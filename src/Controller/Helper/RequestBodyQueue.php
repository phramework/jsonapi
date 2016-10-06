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
namespace Phramework\JSONAPI\Controller\Helper;

use Phramework\Exceptions\MissingParametersException;
use Phramework\Exceptions\NotFoundException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\ISource;
use Phramework\Exceptions\Source\Pointer;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\ResourceModel;
use Phramework\JSONAPI\ValidationModel;
use Phramework\Validate\ArrayValidator;
use Phramework\Validate\EnumValidator;
use Phramework\Validate\ObjectValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait RequestBodyQueue
{
    /**
     * What is resource ?
     * @todo
     * @param \stdClass $resource Primary data resource
     */
    public static function handleResource(
        \stdClass $resource,
        ISource $source,
        ResourceModel $model,
        ValidationModel $validationModel,
        array $validationCallbacks = []
    )
    {
        //Fetch request attributes
        $requestAttributes = $resource->attributes    ?? new \stdClass();
        $requestRelationships = $resource->relationships ?? new \stdClass();

        /*
         * Validate attributes against attributes validator
         */
        $parsedAttributes = $validationModel->getAttributes()
            ->setSource(new Pointer($source->getPath() . '/attributes'))
            ->parse(
            $requestAttributes
        );

        $parsedRelationships = new \stdClass();

        /**
         * Format, object with
         * - relationshipKey1 -> id1
         * - relationshipKey2 -> [id1, id2]
         */
        $relationships = new \stdClass();

        /**
         * Foreach request relationship
         * - check if relationship exists
         * - if TYPE_TO_ONE check if data is object with type and id
         * - if TYPE_TO_MANY check if data is an array of objects with type and id
         * - check if types are correct
         * - copy ids to $relationshipAttributes object
         */
        foreach ($requestRelationships as $rKey => $rValue) {
            if (!$model->issetRelationship($rKey)) {
                throw new RequestException(sprintf(
                    'Relationship "%s" is not defined',
                    $rKey
                ));
            }

            $rSource = new Pointer(
                $source->getPath() . '/relationships/' . $rKey
            );

            if (!isset($rValue->data)) {
                throw new MissingParametersException(
                    ['data'],
                    $rSource
                );
            }

            $r = $model->getRelationship($rKey);
            $resourceType = $r->getResourceModel()->getResourceType();

            $relationshipData = $rValue->data;

            switch ($r->getType()) {
                case Relationship::TYPE_TO_ONE:
                    (new ObjectValidator(
                        (object)[
                            'id'   => $r->getResourceModel()->getIdAttributeValidator(),
                            'type' => new EnumValidator($resourceType, true)
                        ],
                        ['id', 'type']
                    ))->setSource(new Pointer(
                        $rSource->getPath() . '/data'
                    ))->parse($relationshipData);

                    //Push relationship for this relationship key
                    $relationships->{$rKey} = $relationshipData->id;
                    break;
                case Relationship::TYPE_TO_MANY:
                    $parsed = (new ArrayValidator(
                        0,
                        null,
                        (new ObjectValidator(
                            (object)[
                                'id'   => $r->getResourceModel()->getIdAttributeValidator(),
                                'type' => new EnumValidator($resourceType, true)
                            ],
                            ['id', 'type']
                        ))->setSource(new Pointer(
                            $rSource->getPath() . '/data'
                        ))
                    ))->parse($relationshipData);

                    //Push relationship for this relationship key
                    $relationships->{$relationshipKey} = array_map(
                        function (\stdClass $p) {
                            return $p->id;
                        },
                        $parsed
                    );
                    break;
            }
        }

        /*
         * Validate relationships against relationships validator
         */
        if (count((array)$relationships)) {
            $parsedRelationships = $validationModel->getRelationships()->parse(
                $relationships
            );
        }

        /*
         * Foreach request relationship
         * Check if requested relationship resources exist
         * Copy TYPE_TO_ONE attributes to primary data's attributes
         */
        foreach ($parsedRelationships as $rKey => $rValue) {
            $r = $model->getRelationship($rKey);
            $rResourceModel = $r->getResourceModel();

            //Convert to array
            $tempIds = (
            is_array($rValue)
                ? $rValue
                : [$rValue]
            );

            $data = $rResourceModel->getById(
                $tempIds
            );

            /*
             * Check if any of given ids is not found
             */
            foreach ($data as $dId => $dValue) {
                if ($dValue === null) {
                    throw new NotFoundException(sprintf(
                        'Resource of type "%s" and id "%s" is not found',
                        $rResourceModel->getResourceType(),
                        $dId
                    ));
                }
            }

            /*
             * Copy to primary attributes
             * //todo make sure getRecordDataAttribute is not null
             * //todo what if a TO_MANY has getRecordDataAttribute ?
             */
            if ($r->getType() === Relationship::TYPE_TO_ONE) {
                $parsedAttributes->{$r->getRecordDataAttribute()} = $relationshipData;
            }
        }


        /*
         * Call Validation callbacks
         */
        foreach ($validationCallbacks as $callback) {
            $callback(
                $resource,
                $parsedAttributes, //parsed
                $parsedRelationships //parsed
            );
        }

        return new ResourceQueueItem(
            $parsedAttributes,
            $parsedRelationships
        );
    }
}

