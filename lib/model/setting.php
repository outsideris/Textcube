<?php
/// Copyright (c) 2004-2006, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function getServiceSetting($name, $default = null) {
	global $database;
	$value = DBQuery::queryCell("SELECT value FROM {$database['prefix']}ServiceSettings WHERE name = '".mysql_tt_escape_string($name)."'");
	return ($value === null) ? $default : $value;
}

function setServiceSetting($name, $value) {
	global $database;
	$name = mysql_tt_escape_string(mysql_lessen($name, 32));
	$value = mysql_tt_escape_string(mysql_lessen($value, 255));
	return DBQuery::execute("REPLACE INTO {$database['prefix']}ServiceSettings VALUES('$name', '$value')");
}

function removeServiceSetting($name) {
	global $database;
	return DBQuery::execute("DELETE FROM {$database['prefix']}ServiceSettings WHERE name = '".mysql_tt_escape_string($name)."'");
}

function getUserSetting($name, $default = null) {
	global $database, $owner;
	$value = DBQuery::queryCell("SELECT value FROM {$database['prefix']}UserSettings WHERE user = $owner AND name = '".mysql_tt_escape_string($name)."'");
	return ($value === null) ? $default : $value;
}

function setUserSetting($name, $value) {
	global $database, $owner;
	$name = mysql_tt_escape_string($name);
	$value = mysql_tt_escape_string($value);
	return DBQuery::execute("REPLACE INTO {$database['prefix']}UserSettings VALUES($owner, '$name', '$value')");
}

function removeUserSetting($name) {
	global $database, $owner;
	return DBQuery::execute("DELETE FROM {$database['prefix']}UserSettings WHERE user = $owner AND name = '".mysql_tt_escape_string($name)."'");
}
?>
