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

use Phramework\JSONAPI\Controller\Controller;
use Phramework\JSONAPI\Resource;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Controller\Controller
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    use Controller;

    /**
     * @return array
     */
    public function testExistsProviderFailure()
    {
        return [
            [null],
            [false],
            [[]]
        ];
    }

    /**
     * @covers ::assertExists
     */
    public function testExists()
    {
        static::assertExists(true);
        static::assertExists(new Resource('user', '1'));
        static::assertExists([new Resource('user', '1')]);
    }


    /**
     * @covers ::assertExists
     * @dataProvider testExistsProviderFailure
     * @expectedException Phramework\Exceptions\NotFoundException
     */
    public function testExistsFailure($assert) {
        static::assertExists($assert);
    }
}
