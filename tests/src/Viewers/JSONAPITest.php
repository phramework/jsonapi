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
namespace Phramework\JSONAPI\Viewers;

use Phramework\JSONAPI\APP\Models\Article;
use Phramework\JSONAPI\APP\Models\Tag;
use Phramework\JSONAPI\Relationship;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Phramework\JSONAPI\Viewers\JSONAPI
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class JSONAPITest extends TestCase
{
    /**
     * @covers ::view
     */
    public function testView(): void
    {
        ob_start();
        (new JSONAPI())->view((object) [
            'data' => (object) [
                'type' => Article::getType(),
                'id' => '1'
            ]
        ]);

        (new JSONAPI())->view([
            'data' => (object) [
                'type' => Article::getType(),
                'id' => '10'
            ]
        ]);
        ob_end_clean();
    }

    /**
     * @covers ::header
     * @before testView
     */
    public function testHeader(): void
    {
        JSONAPI::header();
    }
}
