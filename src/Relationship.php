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
 * JSONAPI relationship class
 * @since 0.0.0
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @property-read string        $modelClass
 * @property-read int           $type
 * @property-read string|null   $recordDataAttribute
 * @property-read callable|null $dataCallback
 * @property-read int           $flags
 */
class Relationship
{
    const FLAG_DEFAULT = 0;
    /**
     * @deprecated because relationships should not have attributes
     */
    const FLAG_ATTRIBUTES = 1;
    /**
     * Include relationship by default
     */
    const FLAG_INCLUDE_BY_DEFAULT = 32;

    /**
     * Relationship type to one resource.
     */
    const TYPE_TO_ONE  = 1;

    /**
     * Relationship type to zero, one or more resources.
     */
    const TYPE_TO_MANY = 2;

    /**
     * Class path of relationship resource model
     * @var string
     */
    protected $modelClass;

    /**
     * The type of relationship from the resource to relationship resource
     * @var int
     */
    protected $type;

    /**
     * Attribute name in record containing relationship data
     * @var string|null
     */
    protected $recordDataAttribute;

    /**
     * Callable method can be used to fetch relationship data, see TODO
     * @var callable|null
     */
    protected $dataCallback;

    /**
     * Relationship flags
     * @var int
     */
    protected $flags;

    /**
     * @param string        $modelClass            Class path of relationship resource model
     * @param int           $type                  *[Optional] Relationship type
     * @param string|null   $recordDataAttribute   *[Optional] Attribute name in record containing relationship data
     * @param callable|null $dataCallback          *[Optional] Callable method can be used
     * to fetch relationship data, see TODO
     * @param int           $flags                 *[Optional] Relationship flags
     * @throws \Exception When modelClass  doesn't extend Phramework\JSONAPI\Model
     * @throws \Exception When dataCallback is different than null and not callable
     * @example
     * ```php
     * getValidationModel() {
     *     return (object) [
     *         'author' => new Relationship(
     *             Tag::class,
     *             Relationship::TYPE_TO_ONE,
     *             'author-user_id'
     *         );
     *     ];
     * }
     * ```
     * @example
     * ```php
     * getValidationModel() {
     *     return (object) [
     *         'tag' => new Relationship(
     *             Tag::class,
     *             Relationship::TYPE_TO_MANY,
     *             null,
     *             [Tag::class, 'getRelationshipByArticle']
     *         );
     *     ];
     * }
     * ```
     * @todo what about POST, PATCH callback ??
     */
    public function __construct(
        $modelClass,
        $type = Relationship::TYPE_TO_ONE,
        $recordDataAttribute = null,
        $dataCallback = null,
        $flags = Relationship::FLAG_DEFAULT
    ) {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \Exception(sprintf(
                'modelClass MUST extend "%s"',
                Model::class
            ));
        }

        if ($dataCallback !== null && !is_callable($dataCallback)) {
            throw new \Exception('dataCallback MUST be callable');
        }

        $this->modelClass           = $modelClass;
        $this->type                 = $type;
        $this->recordDataAttribute  = $recordDataAttribute;
        $this->dataCallback         = $dataCallback;
        $this->flags                = $flags;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        switch ($name) {
            case 'modelClass':
                return $this->modelClass;
            case 'type':
                return $this->type;
            case 'recordDataAttribute':
                return $this->recordDataAttribute;
            case 'dataCallback':
                return $this->dataCallback;
            case 'flags':
                return $this->flags;
        }

        throw new \Exception(sprintf(
            'Undefined property via __get(): %s',
            $name
        ));
    }
}
