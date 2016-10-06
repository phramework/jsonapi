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
namespace Phramework\JSONAPI\Controller;

use Phramework\Exceptions\ForbiddenException;
use Phramework\Exceptions\IncorrectParameterException;
use Phramework\Exceptions\RequestException;
use Phramework\Exceptions\ServerException;
use Phramework\Exceptions\Source\Pointer;
use Phramework\JSONAPI\Controller\Helper\RequestBodyQueue;
use Phramework\JSONAPI\Controller\Helper\ResourceQueueItem;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Relationship;
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
 * @todo modify to allow batch
 */
trait Post
{
    use RequestBodyQueue;

    //prototype
    /**
     * Handle HTTP POST request method to create new resources
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param ResourceModel          $model
     * @param array                  $validationCallbacks
     * @param callable|null          $viewCallback
     * @param int|null               $bulkLimit
     * @param array                  $directives
     * @return ResponseInterface
     * @throws ForbiddenException
     * @throws RequestException
     */
    public static function handlePost(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ResourceModel $model,
        array $validationCallbacks = [],
        callable $viewCallback = null, //function (request, response, $ids) : ResponseInterface
        int $bulkLimit = null, //todo decide 1 or null for default
        array $directives = []
    ) : ResponseInterface {
        //todo figure out a permanent solution to have body as object instead of array, for every framework
        $body = json_decode(json_encode($request->getParsedBody()));

        //Access request body primary data
        $data = $body->data ?? [new \stdClass()];

        /**
         * @var bool
         */
        $isBulk = true;

        //Treat all request data (bulk or not) as an array of resources
        if (is_object($data)
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

        //Prepare exception source
        $source = new Pointer(
            '/data' .
            (
                $isBulk
                ? '/' . $bulkIndex
                : ''
            )
        );

        /*
         * gather data as a queue
         */
        foreach ($data as $resource) {
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

            /*
             * Will call validationCallbacks
             * Will call $validationModel attribute validator on attributes
             * Will call $validationModel relationship validator on relationships
             * Will copy TO_ONE relationship data to parsed attributes
             */
            $item = static::handleResource(
                $resource,
                $source,
                $validationModel,
                $validationCallbacks
            );

            /*//todo use helper class
            $queueItem = (object) [
                'attributes'    => $requestAttributes,
                'relationships' => $requestRelationships
            ];*/
                
            $requestQueue->push($item);
            
            ++$bulkIndex;
        }

        //post

        /**
         * Gather the created ids
         * @var string[]
         */
        $ids = [];

        /*
         * process queue
         */
        while (!$requestQueue->isEmpty()) {
            /**
             * @var ResourceQueueItem
             */
            $queueItem = $requestQueue->pop();

            $id = $model->post(
                $queueItem->getAttributes()
            );

            Controller::assertUnknownError(
                $id,
                'Unknown error while posting resource'
            );

            /**
             * @var \stdClass
             */
            $relationships = $queueItem->getRelationships();

            /**
             * POST item's relationships
             * @param string[] $rValue
             */
            foreach ($relationships as $rKey => $rValue) {
                $r = $model->getRelationship($rKey);

                if ($r->getType() == Relationship::TYPE_TO_MANY) {
                    if (!isset($r->getCallbacks()->{'PATCH'})) {
                        throw new ServerException(sprintf(
                            'POST callback is not defined for relationship "%s"',
                            $rKey
                        ));
                    }
                }

                /*
                 * Call post relationship callback to post each of relationships pairs
                 */
                foreach ($rValue as $v) {
                    call_user_func(
                        $r->getCallbacks()->{'PATCH'},
                        $id, //Inserted resource id
                        $v
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

        /*if (count($ids) === 1) {
            //Prepare response with 201 Created status code
            return Response::created(
                $response,
                'link' . $ids[0] // location
            );
        }*/ //see https://stackoverflow.com/questions/11309444/can-the-location-header-be-used-for-multiple-resource-locations-in-a-201-created

        //Return 204 No Content
        return Response::noContent($response);
    }
}
