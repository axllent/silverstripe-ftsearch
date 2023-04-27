<?php

namespace Axllent\FTSearch\Model;

use SilverStripe\ORM\DataObject;

/**
 * FTSearch database table.
 */
class FTSearch extends DataObject
{
    /**
     * Table name
     *
     * @var string
     */
    private static $table_name = 'FTSearch';

    /**
     * Database field definitions
     *
     * @var array
     *
     * @config
     */
    private static $db = [
        'ObjectClass'   => 'Varchar(100)',
        'ObjectID'      => 'Int',
        'SearchTitle'   => 'Text',
        'SearchContent' => 'Text',
    ];

    /**
     * Additional table indexes
     *
     * @var array
     */
    private static $indexes = [
        'LookupIdx' => [
            'type'    => 'unique',
            'columns' => ['ObjectClass', 'ObjectID'],
        ],
        'FulltextTitle' => [
            'type'    => 'fulltext',
            'columns' => ['SearchTitle'],
        ],
        'FulltextContent' => [
            'type'    => 'fulltext',
            'columns' => ['SearchContent'],
        ],
        'Fulltext' => [
            'type'    => 'fulltext',
            'columns' => ['SearchTitle', 'SearchContent'],
        ],
    ];

    /**
     * Table create options
     *
     * @var array
     */
    private static $create_table_options = [
        'MySQLDatabase' => 'ENGINE=MyISAM',
    ];
}
