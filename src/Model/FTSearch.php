<?php
namespace Axllent\FTSearch\Model;

use SilverStripe\ORM\DataObject;

/**
 * FTSearch database table.
 */
class FTSearch extends DataObject
{

    /**
     * @var String
     */
    private static $table_name = 'FTSearch';

    /**
     * @var Array
     */
    private static $db = [
        'ObjectClass'   => 'Varchar(100)',
        'ObjectID'      => 'Int',
        'SearchTitle'   => 'Text',
        'SearchContent' => 'Text',
    ];

    /**
     * @var Array
     */
    private static $indexes = [
        'LookupIdx'       => [
            'type'    => 'unique',
            'columns' => ['ObjectClass', 'ObjectID'],
        ],
        'FulltextTitle'   => [
            'type'    => 'fulltext',
            'columns' => ['SearchTitle'],
        ],
        'FulltextContent' => [
            'type'    => 'fulltext',
            'columns' => ['SearchContent'],
        ],
        'Fulltext'        => [
            'type'    => 'fulltext',
            'columns' => ['SearchTitle', 'SearchContent'],
        ],
    ];

    /**
     * @var Array
     */
    private static $create_table_options = [
        'MySQLDatabase' => 'ENGINE=MyISAM',
    ];

}
