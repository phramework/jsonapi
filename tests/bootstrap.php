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

use Phramework\JSONAPI\APP\DataSource\MemoryDataSource;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * group
 */
MemoryDataSource::addTable('group');

MemoryDataSource::insert(
    'group',
    (object) [
        'id'   => '1',
        'name' => 'Members'
    ]
);
MemoryDataSource::insert(
    'group',
    (object) [
        'id'   => '2',
        'name' => 'Administrators'
    ]
);


/**
 * tag
 */
MemoryDataSource::addTable('tag');

MemoryDataSource::insert(
    'tag',
    (object) [
        'id'   => '1',
        'name' => 'A tag'
    ]
);
MemoryDataSource::insert(
    'tag',
    (object) [
        'id'   => '2',
        'name' => 'Another tag'
    ]
);

/**
 * company
 */

MemoryDataSource::addTable('company');

MemoryDataSource::insert(
    'company',
    (object) [
        'id'       => '1',
        'username' => 'nohponex',
        'email'    => 'nohponex@gmail.com',
        'group_id' => '1'
    ]
);

MemoryDataSource::insert(
    'company',
    (object) [
        'id'       => '2',
        'username' => 'nohponex2',
        'email'    => 'nohponex+2@gmail.com',
        'group_id' => '2'
    ]
);

/**
 * user
 */

MemoryDataSource::addTable('user');

MemoryDataSource::insert(
    'user',
    (object) [
        'id'       => '1',
        'username' => 'nohponex',
        'email'    => 'nohponex@gmail.com',
        'group_id' => '1',
        'tag_id'   => ['1', '2']
    ]
);

MemoryDataSource::insert(
    'user',
    (object) [
        'id'       => '2',
        'username' => 'nohponex2',
        'email'    => 'nohponex+2@gmail.com',
        'group_id' => '2',
        'tag_id'   => []
    ]
);

MemoryDataSource::insert(
    'user',
    (object) [
        'id'       => '3',
        'username' => 'nohponex3',
        'email'    => 'nohponex+3@gmail.com',
        'group_id' => '2',
        'tag_id'   => ['2']
    ]
);