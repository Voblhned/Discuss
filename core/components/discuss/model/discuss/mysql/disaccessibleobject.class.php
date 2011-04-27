<?php
/**
 * @package discuss
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\', '/') . '/disaccessibleobject.class.php');
class disAccessibleObject_mysql extends disAccessibleObject {}
?>