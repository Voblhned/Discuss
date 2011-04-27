<?php
/**
 * @package discuss
 */
require_once (strtr(realpath(dirname(dirname(__FILE__))), '\\', '/') . '/disboardaccess.class.php');
class disBoardAccess_mysql extends disBoardAccess {}
?>