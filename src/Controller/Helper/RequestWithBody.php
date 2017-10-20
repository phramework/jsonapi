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
namespace Phramework\JSONAPI\Controller\Helper;

use Phramework\Exceptions\RequestException;
use Phramework\Util\Util;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 3.0.0
 */
class RequestWithBody
{
    public static function prepareData(
        ServerRequestInterface $request,
        int $bulkLimit = null
    ) {
        //todo figure out a permanent solution to have body as object instead of array, for every framework
        $body = json_decode(json_encode($request->getParsedBody()));

        //Access request body primary data
        $data = $body->data ?? new \stdClass();

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

        return [$data, $isBulk];
    }
}
