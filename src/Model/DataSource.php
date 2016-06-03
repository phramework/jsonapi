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
namespace Phramework\JSONAPI\Model;

use Phramework\JSONAPI\DataSource\IDataSource;
use Phramework\JSONAPI\Directive\Filter;
use Phramework\JSONAPI\Directive\Directive;
use Phramework\JSONAPI\Directive\Page;

/**
 * @since 3.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
trait DataSource
{
    /**
     * @var callable
     */
    protected $get;

    /**
     * @var callable
     */
    protected $post;

    /**
     * @var callable
     */
    protected $patch;


    /**
     * @var callable
     */
    protected $put;

    /**
     * @var callable
     */
    protected $delete;

    /**
     * @var IDataSource
     */
    public $dataSource;

    /**
     * @var callable
     */
    public $prepareRecords;

    /**
     * @param callable $get
     * @return $this
     * @todo validate callable using https://secure.php.net/manual/en/reflectionfunction.construct.php
     */
    public function setGet(callable $get = null)
    {
        $this->get = $get;

        return $this;
    }

    /**
     * @param callable $post
     * @return $this
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * @param callable $patch
     * @return $this
     */
    public function setPatch($patch)
    {
        $this->patch = $patch;

        return $this;
    }

    /**
     * @param callable $delete
     * @return $this
     */
    public function setPut($delete)
    {
        $this->put = $delete;

        return $this;
    }

    /**
     * @param callable $delete
     * @return $this
     */
    public function setDelete($delete)
    {
        $this->delete = $delete;

        return $this;
    }

    /**
     * @return IDataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @param IDataSource $dataSource
     * @return $this
     */
    public function setDataSource(IDataSource $dataSource)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * @param Directive[] ...$directives
     * @return Resource[]
     * @throws \LogicException When callable get property is not set
     */
    public function get(Directive ...$directives) : array
    {
        $get = $this->get;

        //If callable get property is not set
        if ($get === null) {
            throw new \LogicException('Method "get" is not defined');
        }

        return $get(...$directives);
    }

    public function post(
        \stdClass $attributes,
        $return = \Phramework\Database\Operations\Create::RETURN_ID
    ) {
        $post = $this->post;

        //If callable get property is not set
        if ($post === null) {
            throw new \LogicException('Method "post" is disabled');
        }

        return $post(
            $attributes,
            $return
        );
    }

    public function patch(
        string $id,
        \stdClass $attributes,
        $return = null
    ) {
        $patch = $this->patch;

        //If callable get property is not set
        if ($patch === null) {
            throw new \LogicException('Method "patch" is disabled');
        }

        return $patch(
            $id,
            $attributes,
            $return
        );
    }

    public function put(
        string $id,
        \stdClass $attributes,
        $return = null
    ) {
        $put = $this->put;

        //If callable get property is not set
        if ($put === null) {
            throw new \LogicException('Method "put" is disabled');
        }

        return $put(
            $id,
            $attributes,
            $return
        );
    }

    public function delete(
        string $id,
        \stdClass $additionalAttributes = null
    ) {
        $delete = $this->delete;

        //If callable get property is not set
        if ($delete === null) {
            throw new \LogicException('Method "delete" is disabled');
        }

        return $delete(
            $id,
            $additionalAttributes
        );
    }

    /**
     * @return callable
     */
    public function getPrepareRecords() : callable
    {
        return $this->prepareRecords;
    }

    /**
     * @param callable $prepareRecords
     * @return $this
     */
    public function setPrepareRecords(callable $prepareRecords)
    {
        $this->prepareRecords = $prepareRecords;

        return $this;
    }



    /**
     * @param string|string[] $id
     * @param Directive[]     ...$directives
     * @return Resource|\stdClass|null
     * @todo rewrite cache from scratch
     */
    public function getById($id, Directive ...$directives)
    {
        $collectionObject = new \stdClass();

        if (FALSE && !is_array($id) && ($cached = static::getCache($id)) !== null) {
            //Return a single resource immediately if cached
            return $cached;
        } elseif (is_array($id)) {
            $id = array_unique($id);

            $originalId = $id;

            foreach ($originalId as $resourceId) {
                $collectionObject->{$resourceId} = null;
                if (FALSE && ($cached = static::getCache($resourceId)) !== null) {
                    $collectionObject->{$resourceId} = $cached;
                    //remove $resourceId from id array, so we wont request the same item again,
                    //but it will be returned in $collectionObject
                    $id = array_diff($id, [$resourceId]);
                }
            }

            //If all ids are already available from cache, return immediately
            if (count($id) === 0) {
                return $collectionObject;
            }
        }

        $passedfilter = array_filter(
            $directives,
            function ($directive) {
                return get_class($directive) == Filter::class;
            }
        );

        $defaultFilter = $this->getDefaultDirectives()->{Filter::class} ?? null;

        //Prepare filter
        $filter = new Filter(
            is_array($id)
                ? $id
                : [$id]
        ); //Force array for primary data

        if (!empty($passedfilter)) {
            //merge with passed
            $filter = Filter::merge(
                $passedfilter[0],
                $filter
            );
        } elseif ($defaultFilter !== null) {
            //merge with default
            $filter = Filter::merge(
                $defaultFilter,
                $filter
            );
        }

        //remove already set page and filter
        $toPassDirectives = array_filter(
            $directives,
            function ($directive) {
                return !in_array(
                    get_class($directive),
                    [
                        Page::class,
                        Filter::class
                    ]
                );
            }
        );

        //pass new directives
        $toPassDirectives[] = $filter;
        $toPassDirectives[] = new Page(count($id));

        $collection = $this->get(
            ...$toPassDirectives
        );

        if (!is_array($id)) {
            if (empty($collection)) {
                return null;
            }

            //Store resource
            //FALSE && static::setCache($id, $collection[0]);

            //Return a resource
            return $collection[0];
        }

        //If ids are an array
        foreach ($collection as $resource) {
            $collectionObject->{$resource->id} = $resource;
            //FALSE && static::setCache($resource->id, $resource);
        }

        unset($collection);

        return $collectionObject;
    }
}