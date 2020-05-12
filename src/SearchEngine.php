<?php
namespace Axllent\FTSearch;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ViewableData;

class SearchEngine
{
    /**
     * Dataobjects to index
     *
     * @var array
     */
    private static $data_objects = [];

    /**
     * Exclude classes
     *
     * @var array
     */
    private static $exclude_classes = [
        'SilverStripe\Assets\Folder',
        'SilverStripe\CMS\Model\RedirectorPage',
        'SilverStripe\CMS\Model\VirtualPage',
        'SilverStripe\ErrorPage\ErrorPage',
    ];

    /**
     * Search limit for results
     *
     * @var int
     */
    private static $search_limit = 1000;

    /**
     * Score weight for title
     *
     * @var int
     */
    private static $title_score = 2;

    /**
     * Scor weight for content
     *
     * @var int
     */
    private static $content_score = 2;

    /**
     * Length of excerpt
     *
     * @var int
     */
    private static $excerpt_length = 200;

    /**
     * End excerpts with...
     *
     * @var string
     */
    private static $excerpt_ending = '...';

    /**
     * CSS class to apply to mathing words in excerpt
     *
     * @var string
     */
    private static $excerpt_css_class = 'highlight';

    /**
     * Search function
     *
     * @param string $search     fulltext search string
     * @param array  $classnames optional array of classnames to search
     *
     * @return ArrayList
     */
    public static function search($search = false, $classnames = [])
    {
        $search_limit      = Config::inst()->get(self::class, 'search_limit');
        $title_score       = Config::inst()->get(self::class, 'title_score');
        $content_score     = Config::inst()->get(self::class, 'content_score');
        $min_score         = Config::inst()->get(self::class, 'min_score');
        $excerpt_length    = Config::inst()->get(self::class, 'excerpt_length');
        $excerpt_ending    = Config::inst()->get(self::class, 'excerpt_ending');
        $excerpt_css_class = Config::inst()->get(self::class, 'excerpt_css_class');

        // prevent memory exhaustion
        if (strlen($search) > 150) {
            $search = substr($search, 0, 150);
        }

        $search_string = Convert::raw2sql($search);
        $query         = new SQLSelect();
        $query->setFrom('FTSearch');
        $query->selectField(
            '(MATCH("SearchTitle") AGAINST(\'' . $search_string . '\') * ' . $title_score . ') +
            (MATCH("SearchContent") AGAINST(\'' . $search_string . '\') * ' . $content_score . ')
            AS Score'
        );

        $query->setWhere('MATCH("SearchTitle","SearchContent") AGAINST(\'' . $search_string . '\' IN BOOLEAN MODE)');

        if (!empty($classnames)) {
            if (is_array($classnames)) {
                $c = [];
                foreach ($classnames as $cn) {
                    array_push($c, "'" . Convert::raw2sql($cn) . "'");
                }
                $add_where = implode(',', $c);
            } else {
                $add_where = "'" . Convert::raw2sql($classnames) . "'";
            }
            $query->addWhere('"ObjectClass" IN (' . $add_where . ')');
        }

        $query->setOrderBy('"Score" DESC');

        if (is_numeric($search_limit) && $search_limit > 0) {
            $query->setLimit($search_limit);
        }

        if (is_numeric($min_score) && $min_score > 0) {
            $query->addHaving('"Score" >= ' . $min_score);
        }

        $records = $query->execute();

        $result = ArrayList::create();

        foreach ($records as $row) {
            $do = $row['ObjectClass']::get()->byID($row['ObjectID']);
            if (!$do) {
                continue; // result doesn't exist?
            }
            $record                = ViewableData::create();
            $record->Score         = $row['Score'];
            $record->ObjectClass   = $row['ObjectClass'];
            $search_title          = FTSearchLib::Highlight(htmlspecialchars($row['SearchTitle']), $search);
            $record->SearchTitle   = DBHTMLText::create()->setValue($search_title);
            $record->SearchContent = $row['SearchContent'];
            $record->Link          = $do->Link();
            $search_excerpt        = FTSearchLib::Highlight(
                htmlspecialchars(
                    FTSearchLib::excerpt($row['SearchContent'], $search, $excerpt_length, $excerpt_ending)
                ), $search
            );
            $record->Excerpt = DBHTMLText::create()->setValue($search_excerpt);
            $record->Object  = $do;
            $result->push($record);
        }

        return $result;
    }

    /**
     * FTSearchListener
     * Attaches multiple extensions (workers or triggers)
     * based on whether object is versioned or not.
     * Also attached to every relating dataobject (has_one, has_many etc).
     *
     * @return void
     */
    public static function attachFTSearchListener()
    {
        $all_classes     = ClassInfo::allClasses();
        $data_objects    = Config::inst()->get(self::class, 'data_objects');
        $exclude_classes = Config::inst()->get(self::class, 'exclude_classes');

        foreach ($data_objects as $classname => $fields) {
            if (isset($all_classes[strtolower($classname)]) && !in_array($classname, $exclude_classes)) {
                // detect which class we should extend with
                $ext_prefix = DataObject::has_extension($classname, Versioned::class) ? 'Versioned' : 'NonVersioned';

                $classname::add_extension('Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchExt');

                $do = DataObject::singleton($classname);

                if ($hasOne = $do->hasOne()) {
                    foreach ($hasOne as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::_attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($hasMany = $do->hasMany()) {
                    foreach ($hasMany as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::_attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($manyMany = $do->manyMany()) {
                    foreach ($manyMany as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::_attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($belongsTo = $do->belongsTo()) {
                    foreach ($belongsTo as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::_attachFTSearchTrigger($class);
                        }
                    }
                }
            }
        }
    }

    /**
     * Attach a trigger to fire index updated when modified
     *
     * @param string $classname class name
     *
     * @return void
     */
    private static function _attachFTSearchTrigger($classname)
    {
        $ext_prefix = DataObject::has_extension($classname, Versioned::class)
        ? 'Versioned' : 'NonVersioned';

        if (!DataObject::has_extension(
            $classname,
            'Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchTriggerExt'
        )
        ) {
            $classname::add_extension('Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchTriggerExt');
        }
    }
}
