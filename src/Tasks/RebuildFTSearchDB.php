<?php

namespace Axllent\FTSearch\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;

/**
 * Rebuilds FTSearch table.
 */
class RebuildFTSearchDB extends BuildTask
{
    private static $segment = 'RebuildFTSearchDB';

    protected $title = 'Rebuild FTSearch Table';

    protected $description = 'Empties & rebuilds the entire FTSearch database table';

    protected static $completed = [];

    public function run($request)
    {
        $data_objects = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'data_objects');
        $exclude_classes = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'exclude_classes');

        echo '<ul>';

        DB::query('TRUNCATE FTSearch');

        echo '<li>Truncated FTSearch</li>';

        $cnt = 0;

        foreach ($data_objects as $class => $fields) {
            $objects = $class::get();
            foreach ($objects as $obj) {
                if ($obj->hasMethod('updateFTSearch')  && !in_array($obj->ClassName, $exclude_classes)) {
                    $unique_id = md5($obj->ID . '-' . $obj->ClassName);
                    if (in_array($unique_id, self::$completed)) {
                        continue;
                    }
                    self::$completed[] = $unique_id;
                    $obj->updateFTSearch();
                    $cnt++;
                }
            }
        }

        echo '<li>Inserted ' . $cnt . ' records.</li></lu>';
    }
}
