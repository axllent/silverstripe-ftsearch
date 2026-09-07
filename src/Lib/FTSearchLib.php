<?php

namespace Axllent\FTSearch\Lib;

use Axllent\FTSearch\Model\FTSearch;
use Axllent\FTSearch\SearchEngine;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class FTSearchLib
{
    /**
     * Return a search result excerpt
     *
     * @param string $text   Search result text
     * @param string $phrase Search phrase
     * @param int    $radius Radius of letters on either side of match
     * @param string $ending Truncate end string
     *
     * @return mixed
     */
    public static function excerpt($text, $phrase, $radius = 100, $ending = '...')
    {
        $text      = strval($text);
        $phrase    = strval($phrase);
        $phraseLen = mb_strlen($phrase);
        if ($radius < $phraseLen) {
            $radius = $phraseLen;
        }

        $phrases = preg_split('/\s/u', $phrase, -1, PREG_SPLIT_NO_EMPTY);

        $pos = false;
        foreach ($phrases as $p) {
            $pos = mb_stripos($text, $p);
            if (false !== $pos) {
                $phrase    = $p;
                $phraseLen = mb_strlen($p);

                break;
            }
        }

        $startPos = 0;
        if (false !== $pos && $pos > $radius) {
            $startPos = $pos - $radius;
        }

        $textLen = mb_strlen($text);

        $endPos = (false === $pos ? 0 : $pos) + $phraseLen + $radius;
        if ($endPos >= $textLen) {
            $endPos = $textLen;
        }

        $excerpt = mb_substr($text, $startPos, $endPos - $startPos);
        if (0 !== $startPos) {
            $excerpt = self::mbSubstrReplace($excerpt, $ending, 0, $phraseLen);
        }

        if ($endPos !== $textLen) {
            $excerpt = self::mbSubstrReplace($excerpt, $ending, -$phraseLen);
        }

        return $excerpt;
    }

    /**
     * Multibyte-safe substr_replace. PHP's built-in substr_replace operates on
     * bytes, which can split multibyte characters mid-codepoint.
     *
     * @param string   $string      Input string
     * @param string   $replacement Replacement string
     * @param int      $start       Start offset (chars, may be negative)
     * @param null|int $length      Length to replace (chars, may be negative or null)
     *
     * @return string
     */
    private static function mbSubstrReplace($string, $replacement, $start, $length = null)
    {
        $strLen = mb_strlen($string);
        if ($start < 0) {
            $start = max(0, $strLen + $start);
        }
        if (null === $length) {
            $length = $strLen - $start;
        } elseif ($length < 0) {
            $length = max(0, $strLen - $start + $length);
        }

        return mb_substr($string, 0, $start)
            . $replacement
            . mb_substr($string, $start + $length);
    }

    /**
     * Highlight words
     *
     * @param string $c String to highlight
     * @param string $q Search words
     *
     * @return mixed
     */
    public static function highlight($c, $q)
    {
        $c = strval($c);
        $q = strval($q);

        $excerpt_css_class = Config::inst()->get(SearchEngine::class, 'excerpt_css_class');
        if (!$excerpt_css_class) {
            return $c;
        }
        $q = explode(
            ' ',
            str_replace(
                [
                    '\\', '+', '*', '?', '[', '^', ']', '$', '(', ')',
                    '{', '}', '=', '!', '<', '>', '|', ':', '#',
                ],
                '',
                $q
            )
        );
        for ($i = 0; $i < sizeof($q); ++$i) {
            $c = preg_replace(
                '/(' . preg_quote($q[$i], '/') . ')(?![^<]*>)/iu',
                '<span class="' . htmlspecialchars($excerpt_css_class) . '">${1}</span>',
                $c
            );
        }

        return $c;
    }

    /**
     * Return the live version of a versioned object, or just the object
     *
     * @param DataObject $obj The DO
     *
     * @return DataObject
     */
    public static function getLiveVersionObject($obj)
    {
        if ($obj->hasExtension(Versioned::class)) {
            $baseClass = DataObject::getSchema()->baseDataClass($obj->ClassName);
            $table     = DataObject::getSchema()->tableName($baseClass);

            return Versioned::get_one_by_stage(
                $baseClass,
                Versioned::LIVE,
                [sprintf('"%s"."ID" = %d', $table, $obj->ID)]
            );
        }

        return $obj;
    }

    /**
     * Remove an object from the index
     *
     * @param DataObject $obj DO to remove
     *
     * @return void
     */
    public static function removeFromFTSearchDB($obj)
    {
        if (!$obj->ClassName) {
            return false;
        }
        $so = FTSearch::get()->Filter(
            [
                'ObjectClass' => $obj->ClassName,
                'ObjectID'    => $obj->ID,
            ]
        )->First();
        if ($so) {
            $so->delete();
        }
    }

    /**
     * Trigger index update on all related objects
     *
     * @param DataObject $obj The relating DO
     *
     * @return void
     */
    public static function triggerLinkedObjects($obj)
    {
        if (!$obj->ClassName) {
            return false;
        }
        if ($hasOne = $obj->hasOne()) {
            foreach ($hasOne as $relationship => $class) {
                if ($obj->{$relationship}()->hasMethod('updateFTSearch')) {
                    $obj->{$relationship}()->updateFTSearch();
                }
            }
        }
        if ($hasMany = $obj->hasMany()) {
            foreach ($hasMany as $relationship => $class) {
                if ($obj->{$relationship}()->hasMethod('updateFTSearch')) {
                    $obj->{$relationship}()->updateFTSearch();
                }
            }
        }
        if ($manyMany = $obj->manyMany()) {
            foreach ($manyMany as $relationship => $class) {
                if ($obj->{$relationship}()->hasMethod('updateFTSearch')) {
                    $obj->{$relationship}()->updateFTSearch();
                }
            }
        }
        if ($belongsTo = $obj->belongsTo()) {
            foreach (array_keys($belongsTo) as $relationship) {
                if ($obj->{$relationship}()->hasMethod('updateFTSearch')) {
                    $obj->{$relationship}()->updateFTSearch();
                }
            }
        }
    }

    /**
     * Update search index
     *
     * @param DataObject $obj DO to index
     *
     * @return void
     */
    public static function updateSearchRecord($obj)
    {
        $data_objects    = Config::inst()->get(SearchEngine::class, 'data_objects');
        $exclude_classes = Config::inst()->get(SearchEngine::class, 'exclude_classes');

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

                if ((!isset($obj->ShowInSearch) || $obj->ShowInSearch)
                    && !in_array($obj->ClassName, $exclude_classes)
                    && ClassInfo::hasMethod($obj, 'Link')
                ) {
                    foreach ($fields as $field) {
                        if (ClassInfo::hasMethod($obj, $field)) {
                            $ft_data[] = self::cleanText($obj->{$field}());
                        } else {
                            $ft_data[] = self::cleanText($obj->{$field});
                        }
                    }
                }

                $so = FTSearch::get()->Filter(
                    [
                        'ObjectClass' => $obj->ClassName,
                        'ObjectID'    => $obj->ID,
                    ]
                )->First();

                if (!count($ft_data)) {
                    if ($so) {
                        $so->delete(); // delete the object
                    }

                    return;
                }
                if (!$so) {
                    $so = FTSearch::create();
                }

                $search_title = trim(preg_replace('/\s+/', ' ', array_shift($ft_data)));
                $search_data  = trim(preg_replace('/\s+/', ' ', implode(' ', $ft_data)));

                $so->ObjectClass   = $obj->ClassName;
                $so->ObjectID      = $obj->ID;
                $so->SearchTitle   = $search_title;
                $so->SearchContent = $search_data;
                $so->write();

                return;
            }
        }
    }

    /**
     * Clean text, remove html
     *
     * @param string $str String to clean
     *
     * @return string
     */
    public static function cleanText($str)
    {
        // manually remove p, table, span etc tags
        $str = preg_replace('/(<\/[0-9a-z]+>|<br\s?\/?>)/i', ' ', strval($str));
        // remove images
        $str = preg_replace('/\[image (class|src|title)="[^\]]*\]/', '', $str);

        return html_entity_decode(strip_tags($str));
    }
}
