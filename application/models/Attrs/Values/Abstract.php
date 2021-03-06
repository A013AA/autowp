<?php

class Attrs_Values_Abstract extends Project_Db_Table
{
    protected $_referenceMap = array(
        'Attribute' => array(
            'columns'       => array('attribut_id'),
            'refTableClass' => 'Attrs_Attributes',
            'refColumns'    => array('id')
        ),
        'ItemType' => array(
            'columns'       => array('item_type_id'),
            'refTableClass' => 'Attrs_Item_Types',
            'refColumns'    => array('id')
        )
    );
}