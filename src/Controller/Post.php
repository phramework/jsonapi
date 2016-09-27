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
 * Handle HTTP POST request method to create new resources
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo modify to allow batch, remove id ?
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
        callable $viewCallback = null, //function (request, response, $ids) : ResponseInterface
        int $bulkLimit = null, //todo decide 1 or null for default
        array $directives = []
    ) : ResponseInterface {
        //Request primary data
        $data = $request->getParsedBody()->data ?? new \stdClass();

        /**
         * @var bool
         */
        $isBulk = true;

        //Treat all request data (bulk or not) as an array of resources
        if (
            is_object($data)
            || (is_array($data) && Util::isArrayAssoc($data))
        ) {
            $isBulk = false;
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

        $bulkIndex = 0;
        //gather data as a queue
        foreach ($data as $resource) {
            //Prepare exception source
            $source = new Pointer(
                '/data' .
                (
                    $isBulk
                    ? '/' . $bulkIndex
                    : ''
                )
            );

            //Require resource type
            Controller::requireProperties($resource, $source, 'type');

            //Validate resource type
            $typeValidator
                ->setSource(new Pointer($source->getPath() . '/type'))
                ->parse($resource->type);

            //Throw exception if resource id is forced
            if (property_exists($resource, 'id')) {
                //todo include source
                throw new ForbiddenException(
                    'Unsupported request to create a resource with a client-generated ID'
                );
            }

            //Fetch request attributes
            $requestAttributes    = $resource->attributes    ?? new \stdClass();
            $requestRelationships = $resource->relationships ?? new \stdClass();

            //todo use helper class
            $queueItem = (object) [
                'attributes'    => $requestAttributes,
                'relationships' => $requestRelationships
            ];
                
            $requestQueue->push($queueItem);
            
            ++$bulkIndex;
        }

        //on each validate
        //todo
        foreach ($requestQueue as $i => $q) {
            $validationModel->attributes
                ->setSource(new Pointer('/data/' . $i . '/attributes'))
                ->parse($q->attributes);
        }

        //on each call validation callback
        //todo

        //post

        /**
         * Gather the created ids
         * @var string[]
         */
        $ids = [];

        //process queue
        while (!$requestQueue->isEmpty()) {
            $queueItem = $requestQueue->pop();

            $id = $model->post(
                $queueItem->attributes
            );

            Controller::assertUnknownError(
                $id,
                'Unknown error while posting resource'
            );

            //POST item's relationships
            $relationships = $queueItem->relationships;

            foreach ($relationships as $key => $relationship) {
                //Call post relationship method to post each of relationships pairs
                //todo fix
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

        //return view callback, it MUST return a ResponseInterface

        if ($viewCallback !== null) {
            return $viewCallback(
                $request,
                $response,
                $ids
            );
        }

        if (count($ids) === 1) {
            //Prepare response with 201 Created status code
            return Response::created(
                $response,
                'link' . $ids[0] // location
            );
        }

        //Return 204 No Content
        return Response::noContent($response);
    }
}