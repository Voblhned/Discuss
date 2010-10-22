<?php
/**
 * Adds custom System Events
 *
 * @package discuss
 * @subpackage build
 */
$events = array();

$events['OnDiscussBeforePostSave']= $modx->newObject('modEvent');
$events['OnDiscussBeforePostSave']->fromArray(array (
  'name' => 'OnDiscussBeforePostSave',
  'service' => 1,
  'groupname' => 'Discuss',
), '', true, true);
$events['OnDiscussPostSave']= $modx->newObject('modEvent');
$events['OnDiscussPostSave']->fromArray(array (
  'name' => 'OnDiscussPostSave',
  'service' => 1,
  'groupname' => 'Discuss',
), '', true, true);
$events['OnDiscussPostFetchContent']= $modx->newObject('modEvent');
$events['OnDiscussPostFetchContent']->fromArray(array (
  'name' => 'OnDiscussPostFetchContent',
  'service' => 1,
  'groupname' => 'Discuss',
), '', true, true);

return $events;