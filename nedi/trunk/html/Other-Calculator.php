<?
/*
#============================================================================
# Program: Other-Calculator.php
# Programmer: Remo Rickli (based on the Perl IP Calculator of Krischan Jodies)
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 22/03/05 v0.1		initial version (sub/supernetting doesn't work yet!).
# 23/03/05 v0.2		full version with fixed 32bit signs (so, I think!)
*/

$bg1	= "DDDDAA";
$bg2	= "EEEEBB";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$getip  = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
$getmsk = isset($_GET['nmsk']) ? $_GET['nmsk'] : "24";
$getsub = isset($_GET['smsk']) ? $_GET['smsk'] : "";

?>
<h1>NeDi Calculator</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="calc">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=<?=$bg1?> ><th width=80><a href=<?=$_SERVER['PHP_SELF']?> ><img src=img/32/calc.png border=0></a>
</th>
<th>
IP Address <input type="text" name="ip" value="<?=$getip?>" size="15">
/ <input type="text" name="nmsk" value="<?=$getmsk?>" size="15">
</th><th>
Sub/Supernet Mask <input type="text" name="smsk" value="<?=$getsub?>" size="15">
</th>
<th width=80>
<input type="submit" value="Calculate" name="calc">
</th>
</tr>
</table></form>
<?
if (isset ($_GET['calc']) ) {

if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$getip) ){
	$ip	= $getip;
	$dip	= ip2long($getip);
}else{
	$getip = $getip + 0;													// force 32 Bit unsigned for PHP!!!
	$ip	= long2ip($getip);
	$dip	= $getip;
}
list($pfix,$mask,$bmsk)	= Masker($getmsk);

$hmsk	= "0x".ip2hex($mask);
$dmsk	= ip2long($mask);
$hip	= "0x".ip2hex($ip);
$bip	= ip2bin($ip);
//or $bip	= base_convert($dip,10,2);

$dwmsk	= ~$dmsk;
$wmsk	= long2ip($dwmsk);
$bwmsk	= ip2bin($wmsk);
$hwmsk	= "0x".ip2hex($wmsk);

$dnet	= ($dip & $dmsk);
$net	= long2ip($dnet);
$bnet	= ip2bin($net);
$hnet	= "0x".ip2hex($net);

$bc	= long2ip($dnet + $dwmsk);
$dbc	= ip2long($bc);
$bbc	= ip2bin($bc);
$hbc	= "0x".ip2hex($bc);

$fho	= long2ip($dnet + 1);
$bfho	= ip2bin($fho);
$hfho	= "0x".ip2hex($fho);

$lho	= long2ip($dbc - 1);
$blho	= ip2bin($lho);
$hlho	= "0x".ip2hex($lho);

$nho	= $dbc - $dnet - 1;

?>
<h2>Base Info</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>><th width=80>&nbsp;</th><th width=30%>Dotted Decimal</th><th>Binary</th><th width=80>Hex</th></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Address</th><td class=blu><?=$ip?> = <?=sprintf("%u", ip2long($ip));?></td><td class=blu><?=$bip?></td><td class=blu><?=$hip?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Mask</th><td class=grn><?=$mask?> = <?=$pfix?></td><td class=grn><?=$bmsk?></td><td class=grn><?=$hmsk?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Wildcard</th><td class=grn><?=$wmsk?></td><td class=grn><?=$bwmsk?></td><td class=grn><?=$hwmsk?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Network</th><td class=prp><?=$net?></td><td class=prp><?=$bnet?></td><td class=prp><?=$hnet?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Broadcast</th><td class=prp><?=$bc?></td><td class=prp><?=$bbc?></td><td class=prp><?=$hbc?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>First Host</th><td class=drd><?=$fho?></td><td class=drd><?=$bfho?></td><td class=drd><?=$hfho?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Last Host</th><td class=drd><?=$lho?> (<?=$nho?> total)</td><td class=drd><?=$blho?></td><td class=drd><?=$hlho?></td></tr>
</table>
<?
}
if ($getsub){
	list($spfix,$smask,$bsmsk)	= masker($getsub);
			
	$hsmsk	= "0x".str_pad(ip2hex($smask),8,0);
	$dsmsk	= ip2long($smask);

	$dwsmsk	= ~ $dsmsk;
	$wsmsk	= long2ip($dwsmsk);
	$bwsmsk	= ip2bin($wsmsk);
	$hwsmsk	= "0x".ip2hex($wsmsk);

	if($pfix < $spfix){
?>
<h2>Subnets</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>><th width=80>&nbsp;</th><th width=30%>Dotted Decimal</th><th>Binary</th><th width=80>Hex</th></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Mask</th><td class=grn><?=$smask?> = <?=$spfix?></td><td class=grn><?=$bsmsk?></td><td class=grn><?=$hsmsk?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Wildcard</th><td class=grn><?=$wsmsk?></td><td class=grn><?=$bwsmsk?></td><td class=grn><?=$hwsmsk?></td></tr>
</table>
<p><table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>><th width=80 colspan=2>Subnet</th><th>Subnet IP/Prefix</th><th>First Host</th><th>Last Host</th><th>Broadcast</th><th width=80>Total Hosts</th></tr>

<?
		$nsnets = pow(2, ($spfix-$pfix) );
		$snoff  = pow(2, (32 - $spfix) );
	
		$row = 0;
		$nsho= 0;
		for ($s=0;$s < $nsnets; $s++){
			if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
			else{ $row = "1"; $bg = $bgb; $bi = $bib; }
						
			$dsnet	= $dnet + $s * $snoff;
			$snet	= long2ip($dsnet);
			list($ntimg,$ntit)	= Nettype($snet);
			$fsho	= long2ip($dsnet + 1);
			$sbc	= long2ip($dsnet + $dwsmsk);
			$lsho	= long2ip($dsnet + $dwsmsk - 1);
			$nsho	+= $snoff - 2;
  			echo "<tr bgcolor=#$bg><th bgcolor=#$bi><img src=img/16/$ntimg title=$ntit></th><th>$s</th>\n";
			echo "<td class=prp>$snet/$spfix</td><td class=drd>$fsho</td><td class=drd>$lsho</td><td class=prp>$sbc</td><td align=center class=blu>$nsho</td></tr>";
		}
	}elseif($pfix > $spfix){
		$snet	= long2ip($dip & $dsmsk);
		$dsnet	= ip2long($snet);
		$bsnet	= ip2bin($snet);
		$hsnet	= "0x".str_pad(ip2hex($snet),8,0);

		$sbc	= long2ip($dsnet + $dwsmsk);
		$dsbc	= ip2long($sbc);
		$bsbc	= ip2bin($sbc);
		$hsbc	= "0x".ip2hex($sbc);

?>
<h2>Supernet</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?>><th width=80>&nbsp;</th><th width=30%>Dotted Decimal</th><th>Binary</th><th>Hexadecimal</th></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Mask</th><td class=grn><?=$smask?> = <?=$spfix?></td><td class=grn><?=$bsmsk?></td><td class=grn><?=$hsmsk?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Wildcard</th><td class=grn><?=$wsmsk?></td><td class=grn><?=$bwsmsk?></td><td class=grn><?=$hwsmsk?></td></tr>
<tr bgcolor=#<?=$bga?>><th bgcolor=#<?=$bg2?>>Network</th><td class=prp><?=$snet?></td><td class=prp><?=$bsnet?></td><td class=prp><?=$hsnet?></td></tr>
<tr bgcolor=#<?=$bgb?>><th bgcolor=#<?=$bg1?>>Broadcast</th><td class=prp><?=$sbc?></td><td class=prp><?=$bsbc?></td><td class=prp><?=$hsbc?></td></tr>
</table>
<?
	}
	echo "</table>\n";
}
include_once ("inc/footer.php");
?>

