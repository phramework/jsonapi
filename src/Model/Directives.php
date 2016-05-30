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


use Phramework\JSONAPI\IDirective;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 * @todo sort method and properties to be more readable
 */
trait Directives
{
    /**
     * @var \stdClass
     */
    protected $defaultDirectives;
    
    /**
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
     * @todo improve grammar
     */
    protected $fieldableAtributes =  [];

    /**
     * Get maximum value of Page directive's limit
     * @var int
     */
    protected $maxPageLimit = 25000;

    /**
     * Attributes that are allow filter directive, and specifying allowed operator classes to be applied
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
     * @param IDirective[] $directives
     * @return $this
     */
    public function addDefaultDirective(IDirective ...$directives)
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
    public function getDefaultDirectives()
    {
        return $this->defaultDirectives;
    }

    /**
     * Returns an array with class names of supported directives
     * @return string[]
     */
    public function getSupportedDirectives()
    {
        return $this->supportedDirectives;
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
                IDirective::class,
                class_implements($directiveClassName)
            )) {
                throw new \InvalidArgumentException(sprintf(
                    'Class "%s" is not implementing interface "%s"',
                    $className,
                    IDirective::class
                ));
            }
        }
        $this->supportedDirectives = $directiveClassName;

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
     * @param int $maxPageLimit
     * @return $this
     */
    public function setMaxPageLimit(int $maxPageLimit)
    {
        $this->maxPageLimit = $maxPageLimit;

        return $this;
    }

    /**
     * @param \stdClass $filterableAttributes
     * @return $this
     * @example
     * <code>
     * $model->setFilterableAttributes((object) [
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
     * @param string[] $fieldableAtributes
     * @return $this
     */
    public function setFieldableAtributes(string ...$fieldableAtributes)
    {
        $this->fieldableAtributes = $fieldableAtributes;

        return $this;
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
     * @param string[] $mutableAttributes
     * @return $this
     */
    public function setMutableAttributes(string ...$mutableAttributes)
    {
        $this->mutableAttributes = $mutableAttributes;

        return $this;
    }



    /**
     * @return \stdClass
     */
    public function getFilterableAttributes()
    {
        return $this->filterableAttributes;
    }

    /**
     * @return \string[]
     */
    public function getSortableAttributes()
    {
        return $this->sortableAttributes;
    }

    /**
     * @return \string[]
     */
    public function getMutableAttributes()
    {
        return $this->mutableAttributes;
    }

    /**
     * @return \string[]
     */
    public function getFieldableAtributes()
    {
        return $this->fieldableAtributes;
    }


}