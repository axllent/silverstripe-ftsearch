<?php

namespace Axllent\FTSearch\Lib;

use Axllent\FTSearch\Model\FTSearch;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class FTSearchLib
{
    public static function Excerpt($text, $phrase, $radius = 100, $ending = '...')
    {
        $phraseLen = strlen($phrase);
        if ($radius < $phraseLen) {
            $radius = $phraseLen;
        }

        $phrases = explode(' ', $phrase);

        foreach ($phrases as $phrase) {
            $pos = strpos(strtolower($text), strtolower($phrase));
            if ($pos > -1) {
                break;
            }
        }

        $startPos = 0;
        if ($pos > $radius) {
            $startPos = $pos - $radius;
        }

        $textLen = strlen($text);

        $endPos = $pos + $phraseLen + $radius;
        if ($endPos >= $textLen) {
            $endPos = $textLen;
        }

        $excerpt = substr($text, $startPos, $endPos - $startPos);
        if ($startPos != 0) {
            $excerpt = substr_replace($excerpt, $ending, 0, $phraseLen);
        }

        if ($endPos != $textLen) {
            $excerpt = substr_replace($excerpt, $ending, -$phraseLen);
        }
        return $excerpt;
    }

    public static function Highlight($c, $q)
    {
        $excerpt_css_class = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'excerpt_css_class');
        if (!$excerpt_css_class) {
            return $c;
        }
        $q = explode(' ', str_replace(array('','\\','+','*','?','[','^',']','$','(',')','{','}','=','!','<','>','|',':','#','-','_'), '', $q));
        for ($i=0; $i < sizeOf($q); $i++) {
            $c = preg_replace('/(' . preg_quote($q[$i], '/') . ')(?![^<]*>)/i', '<span class="' . htmlspecialchars($excerpt_css_class) . '">${1}</span>', $c);
        }
        return $c;
    }

    public static function getLiveVersionObject($obj)
    {
        if ($obj->hasExtension('SilverStripe\\Versioned\\Versioned')) {
            $baseTable = ClassInfo::baseDataClass($obj);
            $table_name = DataObject::getSchema()->tableName($baseTable);
            return Versioned::get_one_by_stage($baseTable, Versioned::LIVE, [
                sprintf('"%s"."ID" = %d', $table_name, $obj->ID)
            ]);
        }
        return $obj;
    }

    public static function removeFromFTSearchDB($obj)
    {
        if (!$obj->ClassName) {
            return false;
        }
        $so = FTSearch::get()->Filter([
            'ObjectClass' => $obj->ClassName,
            'ObjectID' => $obj->ID
        ])->First();
        if ($so) {
            $so->delete();
        }
    }

    public static function triggerLinkedObjects($obj)
    {
        if (!$obj->ClassName) {
            return false;
        }
        if ($hasOne = $obj->hasOne()) {
            foreach ($hasOne as $relationship => $class) {
                if ($obj->$relationship()->hasMethod('updateFTSearch')) {
                    $obj->$relationship()->updateFTSearch();
                }
            }
        }
        if ($hasMany = $obj->hasMany()) {
            foreach ($hasMany as $relationship => $class) {
                if ($obj->$relationship()->hasMethod('updateFTSearch')) {
                    $obj->$relationship()->updateFTSearch();
                }
            }
        }
        if ($manyMany = $obj->manyMany()) {
            foreach ($manyMany as $relationship => $class) {
                if ($obj->$relationship()->hasMethod('updateFTSearch')) {
                    $obj->$relationship()->updateFTSearch();
                }
            }
        }
        if ($belongsTo = $obj->belongsTo()) {
            foreach (array_keys($belongsTo) as $relationship) {
                if ($obj->$relationship()->hasMethod('updateFTSearch')) {
                    $obj->$relationship()->updateFTSearch();
                }
            }
        }
    }

    public static function updateSearchRecord($obj)
    {
        $data_objects = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'data_objects');
        $exclude_classes = Config::inst()->get('Axllent\\FTSearch\\SearchEngine', 'exclude_classes');

        $hierarchy = array_reverse(ClassInfo::ancestry($obj->ClassName));

        foreach ($hierarchy as $class) {
            if (!empty($data_objects[$class])) {
                $fields = $data_objects[$class];
                if (empty($fields) || !is_array($fields)) {
                    return; // do nothing
                }
                $obj = self::getLiveVersionObject($obj);

                if (!$obj || !$obj->ClassName) {
                    return; // not a database record
                }

                $ft_data = [];

                if (
                    (!isset($obj->ShowInSearch) || $obj->ShowInSearch)
                    && !in_array($obj->ClassName, $exclude_classes)
                    && ClassInfo::hasMethod($obj, 'Link')
                ) {
                    foreach ($fields as $field) {
                        if (ClassInfo::hasMethod($obj, $field)) {
                            $ft_data[] = self::cleanText($obj->$field());
                        } else {
                            $ft_data[] = self::cleanText($obj->$field);
                        }
                    }
                }

                $so = FTSearch::get()->Filter(array(
                    'ObjectClass' => $obj->ClassName,
                    'ObjectID' => $obj->ID
                ))->First();

                if (!count($ft_data)) {
                    if ($so) {
                        $so->delete(); // delete the object
                    }
                    return false;
                } elseif (!$so) {
                    $so = FTSearch::create();
                }

                $search_title = trim(preg_replace('/\s+/', ' ', array_shift($ft_data)));
                $search_data = trim(preg_replace('/\s+/', ' ', implode(' ', $ft_data)));

                $so->ObjectClass = $obj->ClassName;
                $so->ObjectID = $obj->ID;
                $so->SearchTitle = $search_title;
                $so->SearchContent = $search_data;
                $so->write();

                return;
            }
        }
    }

    public static function cleanText($str)
    {
        $str = preg_replace('/(<\/[0-9a-z]+>|<br\s?\/?>)/i', ' ', $str);
        $str = preg_replace('/\[image src="[^\]]*\]/', '', $str); // remove images
        return html_entity_decode(strip_tags($str));
    }
}
