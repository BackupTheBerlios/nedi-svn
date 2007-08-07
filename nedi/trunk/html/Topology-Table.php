<?

/*
#============================================================================
# Program: Topology-Table.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 6/05/05		initial version.
# 17/03/06		new SQL query support
# 12/07/07		integration with health
*/

error_reporting(E_ALL ^ E_NOTICE);

$bg1	= "66AACC";
$bg2	= "77BBDD";
$maxcol	= 6;

include_once ("inc/header.php");
include_once ('inc/libdev.php');
include_once ('inc/libmon.php');

$_GET = sanitize($_GET);
$reg = isset($_GET['reg']) ? $_GET['reg'] : "";
$cty = isset($_GET['cty']) ? $_GET['cty'] : "";
$bld = isset($_GET['bld']) ? $_GET['bld'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
TopoTable($reg,$cty,$bld);

?>
<h1>Topology Table</h1>

<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="dtab">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/tabi.png border=0 title="Drill down to a single device with this tabular map"></a></th>
<td>&nbsp;</td>
</tr></table></table><p>
<?

if (!$cty){
	TopoCities();
}elseif (!$bld){
	TopoBuilds($reg,$cty);
}else{
	TopoFloors($reg,$cty,$bld);
}

include_once ("inc/footer.php");

?>
