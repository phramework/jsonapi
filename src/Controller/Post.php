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
namespace Phramework\JSONAPI\Controller;

use Phramework\Exceptions\ForbiddenException;
use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\Source\Pointer;
use Phramework\JSONAPI\Controller\Helper\RequestBodyQueue;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\ResourceModel;
use Phramework\Util\Util;
use Phramework\Validate\EnumValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
trait Post
{
    use RequestBodyQueue;

    //prototype
    public static function handlePost(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ResourceModel $model,
        array $validationCallbacks = [],
        callable $viewCallback = null,
        int $bulkLimit = null,
        array $directives
    ) : ResponseInterface {
        //Request primary data
        $data = $request->getParsedBody()->data;

        //Treat all request data as an array of resources
        if (
            is_object($data)
            || (is_array($data) && Util::isArrayAssoc($data))
        ) {
            $data = [$data];
        }
        
        //check bulk limit
        if ($bulkLimit !== null && count($data) > $bulkLimit) {
            throw new RequestException(sprintf(
                'Number of batch requests is exceeding the maximum of %s',
                $bulkLimit
            ));
        }

        $typeValidator = (new EnumValidator([$model->getResourceType()]));
        
        $requestQueue = new \SplQueue();
        
        //prefer POST validation resourceModel
        $validationModel = $model->getValidationModel(
            'POST'
        );

        $index = 0;
        //gather data as a queue
        foreach ($data as $resource) {
            //Request::requireParameters($resource, 'type');
            //todo remove index if no bulk
            $source = new Pointer('/data/' . $index );

            Controller::requireProperties($resource, $source, 'type');

            //Validate resource type
            $typeValidator
                ->setSource($source->getPath() . '/type')
                ->parse($resource->type);

            if (property_exists($resource, 'id')) {
                throw new ForbiddenException(
                    'Unsupported request to create a resource with a client-generated ID'
                );
            }

            $requestAttributes = (
                isset($resource->attributes) && $resource->attributes
                ? $resource->attributes
                : new \stdClass()
            );

            if (property_exists($resource, 'relationships')) {
                $requestRelationships = $resource->relationships;
            } else {
                $requestRelationships = new \stdClass();
            }

            $queueItem; //todo;
                
            $requestQueue->push($queueItem);
            
            ++$index;
        }

        //on each validate


        //on each call validation callback

        //post

        /**
         * @var string[]
         */
        $ids = [];

        while (!$requestQueue->isEmpty()) {
            $queueItem = $requestQueue->pop();

            $id = $model->post(
                $queueItem->attributes
            );

            Controller::testUnknownError($id);


            //POST item's relationships
            $relationships = $queueItem->getRelationships();

            foreach ($relationships as $key => $relationship) {
                //Call post relationship method to post each of relationships pairs
                foreach ($relationship->resources as $resourceId) {
                    call_user_func(
                        $relationship->callback,
                        $id,
                        $resourceId,
                        null //$additionalAttributes
                    );
                }
            }

            unset($queueItem);

            //push id
            $ids[] = $id;
        }

        //return view callback, MUST return a ResponseInterface

        if ($viewCallback !== null) {
            return $viewCallback(
                $ids
            );
        }

        if (count($ids) === 1) {
            //Prepare response with 201 Created status code
            return Response::created('link' . $ids[0]);
        }

        //Return 204
        return Response::noContent();
    }
}