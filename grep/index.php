<?php
if(isset($_GET['s']) && $_GET['s'] != '') {
	$toSearch="{$_GET['s']}";
	require_once("GrepSimulator.inc.php");
	$grep=new GrepSimulator($toSearch,"../");
}
?>
