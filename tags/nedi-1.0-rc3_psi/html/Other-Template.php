<?
/*
#============================================================================
# Program: Other-Template.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 01/01/06	initial version.
*/

$bg1	= "88AADD";
$bg2	= "99BBEE";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
?>
<h1>Other Template</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/acs.png border=0 title="brief user hint">
</a></th>
<th>
</th>
<th width=80><input type="submit" value="Action"></th>
</tr></table></form><p>
<?
include_once ("inc/footer.php");
?>
