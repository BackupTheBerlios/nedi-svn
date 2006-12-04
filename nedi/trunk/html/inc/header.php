<?php
//===============================
// NeDi header.
//===============================
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

<html>
<head>
<title>NeDi <?=$self?></title>
<?=($nocache)?"<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">\n":""?>
<?=($refresh)?"<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$pause;$_SERVER[PHP_SELF]\">\n":""?>
<?=($calendar)?"<script language=\"JavaScript\" src=\"inc/cal.js\"></script>\n":""?>

<link href='inc/style.css' type=text/css rel=stylesheet>
<link rel='shortcut icon' href='img/favicon.ico'>
<SCRIPT LANGUAGE='JavaScript' SRC='inc/JSCookMenu.js'></SCRIPT>
<LINK REL='stylesheet' HREF='inc/ThemeN/theme.css' TYPE='text/css'>
<SCRIPT LANGUAGE='JavaScript' SRC='inc/ThemeN/theme.js'></SCRIPT>
</head>

<body <?=$btag?>>
<table bgcolor=#000000 <?=$tabtag?>>
<tr bgcolor=#<?="$bg1" ?>>
<td align=center width=80><a href='http://nedi.sourceforge.net'><img src='img/n.png' border=0 hspace=10 valign=middle></a></td>
<td ID=MainMenuID></td><th width=80><?=$_SESSION['user']?></th></tr></table>

<SCRIPT LANGUAGE="JavaScript"><!--
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
