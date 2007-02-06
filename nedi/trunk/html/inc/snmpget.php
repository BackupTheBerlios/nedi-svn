<?php
//===============================
// SNMPget utility.
//===============================
session_start(); 
require_once ('libmisc.php');
require_once ("lang-$_SESSION[lang].php");

if( !preg_match("/net/",$_SESSION['group']) ){
	echo $nokmsg;
	die;
}
$_GET = sanitize($_GET);
?>
<html><body bgcolor=#887766>
<h2><?=$_GET['ip']?> (<?=$_GET['c']?>)</h2>
<img src=../img/32/brgt.png hspace=10><b><?=$_GET['oid']?></b>
<pre style="background-color:#998877">
<?
if($_GET['ip'] and $_GET['ip'] and $_GET['ip'] and $_GET['oid']){
	echo snmpget($_GET['ip'],$_GET['c'],$_GET['oid']);
}else{
	echo $resmsg;
}
?>
</pre>
</body>
</html>
