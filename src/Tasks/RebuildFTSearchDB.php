<?php
namespace Axllent\FTSearch\Tasks;

use Axllent\FTSearch\SearchEngine;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * Rebuilds FTSearch table.
 */
class RebuildFTSearchDB extends BuildTask
{
    /**
     * @var String
     */
    private static $segment = 'RebuildFTSearchDB';

    /**
     * @var String
     */
    protected $title = 'Rebuild FTSearch Table';

    /**
     * @var String
     */
    protected $description = 'Empties & rebuilds the entire FTSearch database table';

    /**
     * @var Array
     */
    protected static $completed = [];

    /**
     * @param $request
     */
    public function run($request)
    {
        $data_objects    = Config::inst()->get(SearchEngine::class, 'data_objects');
        $exclude_classes = Config::inst()->get(SearchEngine::class, 'exclude_classes');

        print '<ul>';

        DB::query('TRUNCATE FTSearch');

        print '<li>Truncated FTSearch</li>';

        $cnt = 0;

        foreach ($data_objects as $class => $fields) {
            $objects = $class::get();
            foreach ($objects as $obj) {
                if ($obj->hasMethod('updateFTSearch') && !in_array($obj->ClassName, $exclude_classes)) {
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

        print '<li>Inserted ' . $cnt . ' records.</li></lu>';
    }
}
