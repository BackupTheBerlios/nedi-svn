<?php
//===============================
// NeDi header.
//===============================

// Some defaults 

$lang	= 'eng';
$datfmt = "j.M G:i:s";

$bga	= "D0D0D0";
$bgb	= "C0C0C0";
$bia	= "F0F0F0";
$bib	= "E6E6E6";

$tabtag = "cellspacing=1 cellpadding=5 border=0 width=100%";

ini_set("memory_limit","16M");							# Added 8.1.2007 due to reporting problems on large networks

session_start(); 

$self = preg_replace("/.*\/(.+).php/","$1",$_SERVER['PHP_SELF']);
require_once ('libmisc.php');
if(isset ($_SESSION['group']) ){
	ReadConf($_SESSION['group']);
}else{
	echo "<script>document.location.href='index.php';</script>\n";
	die;
}
require_once ("lang-$_SESSION[lang].php");
require_once ("lib" . strtolower($backend) . ".php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>NeDi <?=$self?></title>
<?=(isset($nocache))?"<meta http-equiv=\"cache-control\" content=\"no-cache\">\n":""?>
<?=(isset($refresh))?"<meta http-equiv=\"refresh\" content=\"$refresh;$_SERVER[PHP_SELF]\">\n":""?>
<?=(isset($calendar))?"<script language=\"JavaScript\" src=\"inc/cal.js\"></script>\n":""?>

<link href='inc/style.css' type=text/css rel=stylesheet>
<link rel='shortcut icon' href='img/favicon.ico'>
<script language='JavaScript' src='inc/JSCookMenu.js'></script>
<link rel='stylesheet' href='inc/ThemeN/theme.css' TYPE='text/css'>
<script language='JavaScript' src='inc/ThemeN/theme.js'></script>
</head>

<body>
<table bgcolor=#000000 <?=$tabtag?>>
<tr bgcolor=#<?="$bg1" ?>>
<td align=center width=80><a href='http://www.nedi.ch'><img src='img/n.png' border=0 hspace=10 valign=middle></a></td>
<td ID=MainMenuID></td><th width=80><?=$_SESSION['user']?></th></tr></table>

<script language="JavaScript"><!--
var mainmenu = [
<?
	foreach (array_keys($mod) as $m) {
		echo "	[null,'$m',null,null,null,\n";
		foreach ($mod[$m] as $s => $i) {
			echo "		['<img src=./img/16/$i.png>','$s','$m-$s.php',null,null],\n";
		}
		echo "	],\n";
	}
?>
];
cmDraw ('MainMenuID', mainmenu, 'hbr', cmThemeN, 'ThemeN');
--></SCRIPT>
<p>
<?
if( strpos($_SESSION['group'],$modgroup[$self]) === false){
	echo $nokmsg;
	die;
}
?>
