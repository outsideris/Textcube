<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

set_error_handler( "__error" );

function __error( $errno, $errstr, $errfile, $errline )
{
	if(in_array($errno, array(2048))) return;
	print("$errstr($errno)<br />");
	print("File: $errfile:$errline<br /><hr size='1' />");
}

global $__tcSqlLog;
global $__tcSqlLogCount;
global $__tcSqlLogBeginTime;

$__tcSqlLog= array();
$__tcSqlLogCount = 0;

function __tcSqlLogBegin( $sql )
{
	global $__tcSqlLog, $__tcSqlLogBeginTime, $__tcSqlLogCount;

	$backtrace = debug_backtrace();
	array_shift($backtrace);
	array_shift($backtrace);

	$__tcSqlLog[$__tcSqlLogCount] = array( 'sql' => trim($sql), 'backtrace' => $backtrace );
	$__tcSqlLogBeginTime = explode(' ', microtime());
}
function __tcSqlLogEnd( $result, $cachedResult = 0 )
{
	global $__tcSqlLog, $__tcSqlLogBeginTime, $__tcSqlLogCount;
	static $client_encoding = '';
	$tcSqlLogEndTime = explode(' ', microtime());
	$elapsed = ($tcSqlLogEndTime[1] - $__tcSqlLogBeginTime[1]) + ($tcSqlLogEndTime[0] - $__tcSqlLogBeginTime[0]);
	if( !$client_encoding ) {
		$client_encoding = str_replace('_','-',mysql_client_encoding());
	}

	if( $client_encoding != 'utf8' && function_exists('iconv') ) {
		$__tcSqlLog[$__tcSqlLogCount]['error'] = iconv( $client_encoding, 'utf-8', mysql_error());
	}
	else {
		$__tcSqlLog[$__tcSqlLogCount]['error'] = mysql_error();
	}
	$__tcSqlLog[$__tcSqlLogCount]['errno'] = mysql_errno();

	if( $cachedResult == 0 ) {
		$__tcSqlLog[$__tcSqlLogCount]['elapsed'] = ceil($elapsed * 10000) / 10;
	} else {
		$__tcSqlLog[$__tcSqlLogCount]['elapsed'] = 0;
	}
	$__tcSqlLog[$__tcSqlLogCount]['cached'] = $cachedResult;
	$__tcSqlLog[$__tcSqlLogCount]['rows'] = 0;
	if( ! $cachedResult && mysql_errno() == 0 ) {
		switch( strtolower(substr($__tcSqlLog[$__tcSqlLogCount]['sql'], 0, 6 )) )
		{
			case 'select':
				$__tcSqlLog[$__tcSqlLogCount]['rows'] = mysql_num_rows($result);
				break;
			case 'insert':
			case 'delete':
			case 'update':
				$__tcSqlLog[$__tcSqlLogCount]['rows'] = mysql_affected_rows();
				break;
		}
	}
	$__tcSqlLogCount++;
	$__tcSqlLogBeginTime = 0;
}

function __tcSqlLogDump()
{
	global $__tcSqlLog, $__tcSqlLogBeginTime, $__tcSqlLogCount;
	global $service;
	print <<<EOS
<style type='text/css'>
	.debugTable
	{
		border-left: 1px solid #999;
		border-top: 1px solid #999;
		border-collapse: collapse;
		margin-bottom: 20px;
	}
	
	.debugTable *
	{
		border: none;
		margin: 0;
		padding: 0;
	}
	
	.debugTable td, .debugTable th
	{
		border-bottom: 1px solid #999;
		border-right: 1px solid #999;
		color: #000;
		font-family: Arial, Tahoma, Verdana, sans-serif;
		font-size: 12px;
		padding: 3px 5px;
	}
	
	.debugTable th
	{
		background-color: #dedede;
		text-align: center;
	}
	
	tr.debugSQLLine .elapsed, tr.debugSQLLine .rows, tr.debugSQLLine .error
	{
		text-align: center;
	}
	
	tr.debugSQLLine code
	{
		cursor: pointer;
	}
	
	.debugActiveLines
	{
		color: #555;
		font-family: verdana;
		font-size: 11px;
		padding: 5px 5px 5px 10px;
	}
	
	tr.debugCached *
	{
		color: #888888 !important;
	}
	
	tr.debugWarning *
	{
		background-color: #fee5e5;
		color: #961f1d !important;
	}
	
	tr.debugWarning th
	{
		background-color: #fccbca;
	}
	
	tr.debugWarning, tr.debugWarning td, tr.debugWarning th
	{
		/*border: 1px solid #c72927 !important;*/
	}
	
	tr.debugWarning .debugActiveLines
	{
		color: #974c4c !important;
	}
	
	tfoot td
	{
		padding: 15px !important;
		text-align: center;
	}
</style>
EOS;

	$elapsed_total = 0;

	$elapsed = array();
	$count = 1;
	$cached_count = 0;
	foreach( $__tcSqlLog as $c => $log ) {
		$elapsed[$count] = array( $log['elapsed'], $count );
		$count++;
	}

	arsort( $elapsed );
	$bgcolor = array();
	foreach( array_splice($elapsed,0,5) as $e ) {
		$top5[$e[1]] = true;
	}

	$count = 1;
	print '<table class="debugTable">';
	print <<<THEAD
		<thead>
			<tr>
				<th>count</th><th class="sql">query string</th><th>elapsed</th><th>elapsed sum</th><th>rows</th><th>error</th>
			</tr>
		</thead>
THEAD;
	print '<tbody>';
	$install_base = dirname(dirname(__FILE__)) . DS;
	foreach( $__tcSqlLog as $c => $log ) {
		$error = '';
		$backtrace = '';
		$frame_count = 1;
		foreach($log['backtrace'] as $frame) {
			$cl = isset($frame['class']) ? "{$frame['class']}::" : '';
			$fn = isset($frame['function']) ? "{$frame['function']}" : '';
			$fl = isset($frame['file']) ? "{$frame['file']}:" : '';
			if( !empty($service['debug_dbquery_strip_install_path'] )) {
				$fl = str_replace($install_base, '', $fl );
			}
			$ln = isset($frame['line']) ? "{$frame['line']}" : '';
			$line = "$fl$ln in <code>$cl$fn</code>";
			$backtrace .= "$frame_count: $line<br />";
			$frame_count++;
		}
		if( $log['errno'] ) {
			$error = "{$log['errno']}:{$log['error']}";
		}
		$trclass = '';
		if( isset( $top5[$count] ) ) {
			$trclass = ' debugWarning';
		}
		$count_label = $count;
		if( $log['cached'] == 1) {
			$error = "(cached)";
			$trclass .= ' debugCached';
			$cached_count++;
		}
		else if( $log['cached'] == 2 ) {
			$error = "";
			$trclass .= ' debugCached';
			$count_label = '';
		}
		
		$log['elapsed'] = sprintf("%01.4f",$log['elapsed'] / 1000);
		$elapsed_total += $log['elapsed'];
		
		print <<<TBODY
		<tr class='debugSQLLine{$trclass}'>
			<th>{$count_label}</th>
			<td class="code">
				<code onclick="document.getElementById('debugLine_{$count_label}').style.display == 'none' ? document.getElementById('debugLine_{$count_label}').style.display = 'block' : document.getElementById('debugLine_{$count_label}').style.display = 'none';">{$log['sql']}</code>
				<div id='debugLine_{$count_label}' class='debugActiveLines' style='display: none;'>{$backtrace}</div>
			</td>
			<td class="elapsed">{$log['elapsed']}</td>
			<td class="elapsed">{$elapsed_total}</td>
			<td class="rows">{$log['rows']}</td>
			<td class="error">{$error}</td>
		</tr>
TBODY;
		if( $log['cached'] < 2 ) {
			$count++;
		}
	}
	print '</tbody>';
	
	$elapsed_total = $elapsed_total / 1000;
	$count--;
	$real_query_count = $count - $cached_count;
	
	print <<<TFOOT
<tfoot>
	<tr>
		<td colspan='6'>$count ($real_query_count+$cached_count cache) Queries, $elapsed_total seconds elapsed</td>
	</tr>
</tfoot>
TFOOT;
	print '</table>';

	global $service;
	if( ! empty($service['debug_session_dump'])) {
		print '<pre> $_SESSION = ';
		print_r( $_SESSION );
		print '</pre>';
	}
}
?>
