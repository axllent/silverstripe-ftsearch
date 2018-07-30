<?php

namespace Axllent\FTSearch;

use Axllent\FTSearch\Lib\FTSearchLib;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\View\ViewableData;

class SearchEngine
{
    /**
     * @Config
     */
    private static $data_objects = [];

    private static $exclude_classes = [
        'SilverStripe\Assets\Folder',
        'SilverStripe\CMS\Model\RedirectorPage',
        'SilverStripe\CMS\Model\VirtualPage',
        'SilverStripe\ErrorPage\ErrorPage',
    ];

    private static $search_limit = 1000;

    private static $title_score = 2;

    private static $content_score = 2;

    private static $excerpt_length = 200;

    private static $excerpt_ending = '...';

    private static $excerpt_css_class = 'highlight';


    public static function Search($search = false, $classnames = [])
    {
        $search_limit = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'search_limit');
        $title_score = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'title_score');
        $content_score = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'content_score');
        $min_score = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'min_score');
        $excerpt_length = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'excerpt_length');
        $excerpt_ending = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'excerpt_ending');
        $excerpt_css_class = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'excerpt_css_class');

        $search_string = Convert::raw2sql($search);
        $query = new SQLSelect();
        $query->setFrom('FTSearch');
        $query->selectField('
            (MATCH("SearchTitle") AGAINST(\'' . $search_string . '\') * ' . $title_score . ') +
			(MATCH("SearchContent") AGAINST(\'' . $search_string . '\') * ' . $content_score . ') AS Score
		');

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
            $query->addWhere("\"ObjectClass\" IN (" . $add_where . ")");
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
            $record = ViewableData::create();
            $record->Score = $row['Score'];
            $record->ObjectClass = $row['ObjectClass'];
            $search_title = FTSearchLib::Highlight(htmlspecialchars($row['SearchTitle']), $search);
            $record->SearchTitle = DBHTMLText::create()->setValue($search_title);
            $record->SearchContent = $row['SearchContent'];
            $record->Link = $do->Link();
            $search_excerpt = FTSearchLib::Highlight(
                htmlspecialchars(
                    FTSearchLib::excerpt($row['SearchContent'], $search, $excerpt_length, $excerpt_ending)
                ), $search
            );
            $record->Excerpt = DBHTMLText::create()->setValue($search_excerpt);
            $record->Object = $do;
            $result->push($record);
        }

        return $result;
    }

    /**
     * FTSearchListener
     * Attaches multiple extensions (workers or triggers)
     * based on whether object is versioned or not.
     * Also attached to every relating dataobject (has_one, has_many etc).
     */
    public static function attachFTSearchListener()
    {
        $all_classes = ClassInfo::allClasses();
        $data_objects = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'data_objects');
        $exclude_classes = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'exclude_classes');

        foreach ($data_objects as $classname => $fields) {
            if (isset($all_classes[strtolower($classname)]) && !in_array($classname, $exclude_classes)) {
                $do = singleton($classname);

                if ($do->hasExtension('SilverStripe\\Versioned\\Versioned')) {
                    $ext_prefix = 'Versioned';
                } else {
                    $ext_prefix = 'NonVersioned';
                }

                $classname::add_extension('Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchExt');

                if ($hasOne = $do->hasOne()) {
                    foreach ($hasOne as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($hasMany = $do->hasMany()) {
                    foreach ($hasMany as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($manyMany = $do->manyMany()) {
                    foreach ($manyMany as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::attachFTSearchTrigger($class);
                        }
                    }
                }

                if ($belongsTo = $do->belongsTo()) {
                    foreach ($belongsTo as $relationship => $class) {
                        if (!is_string($class)) {
                            continue;
                        }
                        if (isset($all_classes[strtolower($class)]) && !in_array($class, $exclude_classes)) {
                            self::attachFTSearchTrigger($class);
                        }
                    }
                }
            }
        }
    }

    private static function attachFTSearchTrigger($class_name)
    {
        $class = singleton($class_name);
        if ($class->hasExtension('SilverStripe\\Versioned\\Versioned')) {
            $ext_prefix = 'Versioned';
        } else {
            $ext_prefix = 'NonVersioned';
        }
        if (!$class->hasExtension('Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchTriggerExt')) {
            $class::add_extension('Axllent\\FTSearch\\Extensions\\' . $ext_prefix . 'FTSearchTriggerExt');
        }
    }
}
