<?php
/**
 * @package discuss
 */
$xpdo_meta_map['disBoardUserGroup']= array (
  'package' => 'discuss',
  'table' => 'discuss_board_usergroups',
  'fields' => 
  array (
    'usergroup' => 0,
    'board' => 0,
  ),
  'fieldMeta' => 
  array (
    'usergroup' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
    'board' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
      'index' => 'index',
    ),
  ),
  'aggregates' => 
  array (
    'Board' => 
    array (
      'class' => 'disBoard',
      'local' => 'board',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'UserGroup' => 
    array (
      'class' => 'modUserGroup',
      'local' => 'usergroup',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'UserGroupProfile' => 
    array (
      'class' => 'disUserGroupProfile',
      'local' => 'usergroup',
      'foreign' => 'usergroup',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
