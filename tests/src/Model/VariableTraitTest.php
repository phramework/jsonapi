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
namespace Phramework\JSONAPI\Model;

/**
 * @coversDefaultClass Phramework\JSONAPI\Model\VariableTrait
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class VariableTraitTest extends \PHPUnit_Framework_TestCase
{
    use VariableTrait;

    public function setUp()
    {
        $this->initializeVariables();
    }

    /**
     * @covers ::initializeVariables
     */
    public function testInitializeVariables()
    {
        $this->initializeVariables();
    }

    /**
     * @covers ::addVariable
     */
    public function testAddVariable()
    {
        $this->addVariable('key', 'value');

        $this->assertTrue(
            $this->issetVariable('key')
        );

        $this->assertSame(
            'value',
            $this->getVariable('key')
        );
    }

    /**
     * @covers ::getVariable
     */
    public function testGetVariable()
    {
        return $this->testAddVariable();
    }

    /**
     * @covers ::getVariable
     */
    public function testGetVariableDefault()
    {
        $value = 'default';

        $this->assertSame(
            $value,
            $this->getVariable('unset', $value)
        );
    }

    /**
     * @covers ::issetVariable
     */
    public function testIssetVariable()
    {
        return $this->testAddVariable();

        $this->assertFalse(
            $this->issetVariable('not-found')
        );
    }
}
