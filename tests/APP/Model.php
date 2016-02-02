<?php
/**
 * Created by PhpStorm.
 * User: nohponex
 * Date: 1/2/2016
 * Time: 2:29 μμ
 */

namespace Phramework\JSONAPI\APP;

use Phramework\JSONAPI\Filter;
use Phramework\JSONAPI\Sort;
use Phramework\JSONAPI\Fields;
use Phramework\JSONAPI\Page;

class Model extends \Phramework\JSONAPI\Model
{
    /**
     * @param array[] $records
     * @param Page $page
     * @param Filter $filter
     * @param Sort $sort
     * @param Fields $fields
     * @return array[]
     */
    public static function handleGetWithArrayOfRecords(
        $records,
        Page $page = null,
        Filter $filter = null,
        Sort $sort = null,
        Fields $fields = null,
        ...$additionalParameters
    ) {
        if ($filter !== null) {
            $records = array_filter(
                $records,
                function ($record) use ($filter) {
                    return in_array($record['id'], $filter->primary, false);
                }
            );
        }

        if ($page !== null) {
            $records = array_values(
                array_slice($records, $page->offset, $page->limit)
            );
        }

        return $records;
    }
}
