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
namespace Phramework\JSONAPI\Model;

use Phramework\JSONAPI\Directive\Directive;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo sort method and properties to be more readable
 */
trait DirectivesTrait
{
    /**
     * @var \stdClass
     */
    protected $defaultDirectives;
    
    /**
     * @todo check restriction should be applied based on this property
     * List of supported directives
     * @var string[]
     */
    protected $supportedDirectives = [];

    /**
     * Attributes that are allow sort directive to be applied
     * @var string[]
     */
    protected $sortableAttributes  = [];

    /**
     * Attributes that are allowed to be changed by patch method
     * @var string[]
     */
    protected $mutableAttributes   = [];

    /**
     * Attributes that are allow fields directive to be applied
     * @var string[]
     */
    protected $fieldableAtributes =  [];

    /**
     * Attribute that are not returned via the API response,
     * they are useful for internal data processing
     * Resource parse will store the specified attributes under it's
     * private-attributes object member
     * @var string[]
     */
    protected $privateAttributes = [];

    /**
     * Get maximum value of Page directive's limit
     * @var int
     */
    protected $maxPageLimit = 25000;

    /**
     * Dictionary of filterable attributes key => value
     * Where key is the attribute and
     * value the allowed operator classes (represented as flags)
     * @var \stdClass
     * @example
     * <code>
     * (object) [
     *     'email'  => Operator::CLASS_COMPARABLE,
     *     'status' => Operator::CLASS_COMPARABLE,
     *     'title'  => Operator::CLASS_COMPARABLE | Operator::CLASS_LIKE,
     * ]
     * </code>
     */
    protected $filterableAttributes;

    /**
     * Add default directive values
     * Only one default value per directive class is allowed
     * It will include any directive class that are missing to supported directive class
     * @param Directive[] $directives
     * @return $this
     */
    public function addDefaultDirective(Directive ...$directives)
    {
        foreach ($directives as $directive) {
            $class = get_class($directive);

            $this->defaultDirectives->{$class} = $directive;

            if (!in_array($class, $this->supportedDirectives, true)) {
                $this->supportedDirectives[] = $class;
            }
        }

        return $this;
    }

    /**
     * @return \stdClass
     */
    public function getDefaultDirectives() : \stdClass
    {
        return $this->defaultDirectives;
    }

    /**
     * @param string[] $directiveClassName
     * @return $this
     * @throws \InvalidArgumentException If a class name is not implementing IDirective interface
     */
    public function setSupportedDirectives(string ...$directiveClassName)
    {
        foreach ($directiveClassName as $className) {
            if (!in_array(
                Directive::class,
                class_implements($directiveClassName)
            )) {
                throw new \InvalidArgumentException(sprintf(
                    'Class "%s" is not implementing interface "%s"',
                    $className,
                    Directive::class
                ));
            }
        }
        $this->supportedDirectives = $directiveClassName;

        return $this;
    }

    /**
     * Returns an array with class names of supported directives
     * @return string[]
     */
    public function getSupportedDirectives() : array
    {
        return $this->supportedDirectives;
    }

    /**
     * @param int $maxPageLimit
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setMaxPageLimit(int $maxPageLimit)
    {
        if ($maxPageLimit < 1) {
            throw new \InvalidArgumentException(
                'maxPageLimit must be a positive interger'
            );
        }
        $this->maxPageLimit = $maxPageLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPageLimit() : int
    {
        return $this->maxPageLimit;
    }

    /**
     * Get dictionary of filterable attributes key => value
     * Where key is the attribute and
     * value the allowed operator classes (represented as flags)
     * @param \stdClass $filterableAttributes
     * @return $this
     * @example
     * <code>
     * $resourceModel->setFilterableAttributes((object) [
     *     'email'  => Operator::CLASS_COMPARABLE,
     *     'status' => Operator::CLASS_COMPARABLE,
     *     'title'  => Operator::CLASS_COMPARABLE | Operator::CLASS_LIKE,
     * ]);
     * <code>
     */
    public function setFilterableAttributes(\stdClass $filterableAttributes)
    {
        $this->filterableAttributes = $filterableAttributes;

        return $this;
    }

    /**
     * @return \stdClass
     */
    public function getFilterableAttributes() : \stdClass
    {
        return $this->filterableAttributes;
    }

    /**
     * @param string[] $fieldableAtributes
     * @return $this
     */
    public function setFieldableAtributes(string ...$fieldableAtributes)
    {
        $this->fieldableAtributes = $fieldableAtributes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFieldableAtributes() : array
    {
        return $this->fieldableAtributes;
    }

    /**
     * @param string[] $sortableAttributes
     * @return $this
     */
    public function setSortableAttributes(string ...$sortableAttributes)
    {
        $this->sortableAttributes = $sortableAttributes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSortableAttributes() : array
    {
        return $this->sortableAttributes;
    }

    /**
     * Set the resource's that are allowed to be changed by patch method
     * @param string[] $mutableAttributes
     * @return $this
     */
    public function setMutableAttributes(string ...$mutableAttributes)
    {
        $this->mutableAttributes = $mutableAttributes;

        return $this;
    }

    /**
     * Get the resource's that are allowed to be changed by patch method
     * @return string[]
     */
    public function getMutableAttributes() : array
    {
        return $this->mutableAttributes;
    }

    /**
     * @param string[] $privateAttributes
     * @return $this
     */
    public function setPrivateAttributes(string ...$privateAttributes)
    {
        $this->privateAttributes = $privateAttributes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPrivateAttributes() : array
    {
        return $this->privateAttributes;
    }
}