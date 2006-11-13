<?php
//===============================
// SNMPwalk utility.
//===============================
session_start(); 
if( !preg_match("/net/",$_SESSION['group']) ){
	echo $nokmsg;
	die;
}
?>
<html><body bgcolor=#887766>
<h2><?=$_GET['ip']?> (<?=$_GET['c']?>)</h2>
<img src=../img/32/bdwn.png hspace=10><b><?=$_GET['oid']?></b>
<pre style="background-color:#998877">
<?
foreach (snmpwalk($_GET['ip'],$_GET['c'],$_GET['oid']) as $val){
	echo "$val<br>\n";
}
?>
</pre>
</body>
</html>
