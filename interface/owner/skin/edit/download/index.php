<?php
/// Copyright (c) 2004-2014, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
$IV = array(
	'GET' => array(
		'file' => array('filename')
		)
	);

require ROOT . '/library/preprocessor.php';

if (!file_exists(ROOT . "/skin/customize/".getBlogId()."/".$_GET['file']))
	exit;
header('Content-Type: text/html; charset=utf-8');
$fileHandle = fopen(__TEXTCUBE_SKIN_CUSTOM_DIR__."/".getBlogId()."/".$_GET['file'],'r+');
$result = fread($fileHandle, filesize(__TEXTCUBE_SKIN_CUSTOM_DIR__."/".getBlogId()."/".$_GET['file']));
fclose($fileHandle);
echo $result;
?>
