<?php

namespace Axllent\FTSearch\Model;

use SilverStripe\ORM\DataObject;

/**
 * FTSearch database table.
 */
class FTSearch extends DataObject {

    private static $table_name = 'FTSearch';

    private static $db = [
        'ObjectClass' => 'Varchar(100)',
        'ObjectID' => 'Int',
        'SearchTitle' => 'Text',
        'SearchContent' => 'Text'
    ];

    private static $indexes = [
		'LookupIdx' => array(
			'type' => 'unique',
			'columns' => ['ObjectClass','ObjectID']
		),
		'FulltextTitle' => array(
			'type' => 'fulltext',
			'columns' => ['SearchTitle']
		),
		'FulltextContent' => array(
			'type' => 'fulltext',
			'columns' => ['SearchContent']
		),
		'Fulltext' => array(
			'type' => 'fulltext',
			'columns' => ['SearchTitle', 'SearchContent']
		)
	];

    private static $create_table_options = [
        'MySQLDatabase' => 'ENGINE=MyISAM'
    ];

}
