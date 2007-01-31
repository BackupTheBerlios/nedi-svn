<?
/*
#============================================================================
# Program: Other-Defgen.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 04/07/06	initial version
# 08/30/06	formfill and SNMP OID check added
# 31/01/07	added dot3 and baystack interface templates
*/

$bg1	= "887766";
$bg2	= "998877";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

$now = date ("j.M y G:i",time());

include_once ("inc/header.php");

$_GET = sanitize($_GET);
$c = isset($_GET['c']) ? $_GET['c'] : "public";
$so = isset($_GET['so']) ? $_GET['so'] : "1.3.6.1.4.1.?";
$ip = isset($_GET['ip']) ? $_GET['ip'] : "";
$wr = isset($_POST['wr']) ? $_POST['wr'] : "";

$def = "1) Adjust the fields above first, then customize the resulting text, if necessary.\n2) Click generate when you're done and the .def file will be saved.\n3) Copy the generated file from the log to your sysobj folder.\n4) If they're 100% accurate (and only then!), you can email them to be included in the distribution.\n\nHints: A shortcut is, to click on sysobj-id in Devices List. If the id is found in the Cisco Producds Mib, some values are insterted for you.\n\nUse the icons to the left of a section title for templates. An OID can be tested on the fly, if a target and a community is specified. Please remove what's not applicable though.\n\nPlease add a comment, if you define an alternative use for the temperature value.";
$dis = "";

$typ = "";

$snt = "";
$thc = "";					# I decided to use $thc for the two-hc variable due to extensive listening to older Pink Floyd songs...

$ios = "";
$cos = "";
$c19 = "";
$cvp = "";
$bas = "";
$iro = "";
$pro = "";

$ico = "genh";

$bno = "";
$bvl = "";
$bca = "";

$cdp = "";
$ldp = "";

$bi = "";
$sn = "";
$vln = "";
$vtd = "";
$vtm = "";

$ial = "";
$iax = "";
$idu = "";
$idx = "";
$hdv = "";
$fdv = "";
$ivl = "";
$ivx = "";

$msl = "";
$mcl = "";
$mcv = "";
$mde = "";
$mhw = "";
$msw = "";
$mfw = "";
$msn = "";
$mmo = "";

$cpu = "";
$tmp = "";
$mcp = "";
$mio = "";

if ($so){
	if( file_exists("log/$so.def") ){
		$logdef = file("log/$so.def");
		$def = "Definition loaded:\n" . array_shift($logdef);
		foreach ($logdef as $l) {
			if( !preg_match('/^[#;]|^$/', $l) ){
				$d = preg_split('/\s+/',$l);
				if($d[0] == 'SNMPv' AND $d[1] == '2HC'){$thc = 'selected';}
				elseif($d[0] == 'SNMPv' AND $d[1] == '2'){$snt = 'selected';}
				elseif($d[0] == 'SNMPv' AND $d[1] == '2HC'){$thc = 'selected';}
				elseif($d[0] == 'Type'){$typ = $d[1];}
				elseif($d[0] == 'OS' AND $d[1] == 'IOS'){$ios = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'CatOS'){$cos = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'C19'){$c19 = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'Cvpn'){$cvp = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'Baystack'){$bas = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'Ironware'){$iro = 'selected';}
				elseif($d[0] == 'OS' AND $d[1] == 'ProCurve'){$pro = 'selected';}
				elseif($d[0] == 'Icon'){$ico = $d[1];}
				elseif($d[0] == 'Bridge' AND $d[1] == 'normal'){$bno = 'selected';}
				elseif($d[0] == 'Bridge' AND $d[1] == 'VLX'){$bvl = 'selected';}
				elseif($d[0] == 'Bridge' AND $d[1] == 'CAP'){$bca = 'selected';}
				elseif($d[0] == 'Dispro' AND $d[1] == 'CDP'){$cdp = 'selected';}
				elseif($d[0] == 'Dispro' AND $d[1] == 'LLDP'){$ldp = 'selected';}
				elseif($d[0] == 'Serial'){$sn = $d[1];}
				elseif($d[0] == 'Bimage'){$bi = $d[1];}
				elseif($d[0] == 'VLnams'){$vln = $d[1];}
				elseif($d[0] == 'VTPdom'){$vtd = $d[1];}
				elseif($d[0] == 'VTPmod'){$vtm = $d[1];}
				elseif($d[0] == 'IFalia'){$ial = $d[1];}
				elseif($d[0] == 'IFalix'){$iax = $d[1];}
				elseif($d[0] == 'IFdupl'){$idu = $d[1];}
				elseif($d[0] == 'IFduix'){$idx = $d[1];}
				elseif($d[0] == 'Halfdp'){$hdv = $d[1];}
				elseif($d[0] == 'Fulldp'){$fdv = $d[1];}
				elseif($d[0] == 'IFvlan'){$ivl = $d[1];}
				elseif($d[0] == 'IFvlix'){$ivx = $d[1];}
				elseif($d[0] == 'Modesc'){$mde = $d[1];}
				elseif($d[0] == 'Moclas'){$mcl = $d[1];}
				elseif($d[0] == 'Movalu'){$mcv = $d[1];}
				elseif($d[0] == 'Moslot'){$msl = $d[1];}
				elseif($d[0] == 'Modhw'){$mhw = $d[1];}
				elseif($d[0] == 'Modsw'){$msw = $d[1];}
				elseif($d[0] == 'Modfw'){$mfw = $d[1];}
				elseif($d[0] == 'Modser'){$msn = $d[1];}
				elseif($d[0] == 'Momodl'){$mmo = $d[1];}
				elseif($d[0] == 'CPUutl'){$cpu = $d[1];}
				elseif($d[0] == 'Temp'){$tmp = $d[1];}
				elseif($d[0] == 'MemCPU'){$mcp = $d[1];}
				elseif($d[0] == 'MemIO'){$mio = $d[1];}
#			else{echo "<h4>$l?</h4>";}
			}
		}
	}else{
		$ciscos = str_replace("\"","",file('inc/CISCO-PRODUCTS-MIB.oid') );
		foreach($ciscos as $p){
			$prod = preg_split('/\s+/',$p);
			if($so == $prod[1]){
				$typ = $prod[0];
				$thc = "selected";
				$ios = "selected";
				$cdp = "selected";
				$ico = "genc";
			}
		}
	}
}

echo "<h1>Device Definition Generator</h1>\n";
if($wr){
	$def  = preg_replace("/\r|\/|\\|\.\.|/", "", $_POST['def'] );
	$so  = preg_replace("/\/|\\|\.\.|/", "", $_POST['so'] );
	$hdle = fopen("log/$so.def", "w");
	fwrite($hdle, $def);
	fclose($hdle);
	echo "<h3>$so.def written in log. Copy it to your sysobj folder and test with ./nedi.pl -t [ip]</h3>\n";
	echo "<table align=center bgcolor=#cccccc cellpadding=20><tr><td><pre><a href=\"log/$so.def\">$def</a></pre></td></tr></table>\n";
	include_once ("inc/footer.php");
	die;
}
?>


<script language="JavaScript">
<!--
dis = '<?=$dis?>';

function update() {

	if (dis){
		alert('Controls disabled!');
	}else{
		document.gen.so.value = document.bld.so.value;
		document.gen.def.value = "# Definition for " + document.bld.so.value + " created by Defgen 1.2 on <?=$now?>\n" +
		"\n# General\n" +
		"SNMPv\t" + document.bld.ver.options[document.bld.ver.selectedIndex].value + "\n" +
		"Type\t" + document.bld.typ.value + "\n" +
		"OS\t" + document.bld.os.options[document.bld.os.selectedIndex].value + "\n" +
		"Icon\t" + document.bld.ico.value + "\n" +
		"Bridge\t" + document.bld.brg.options[document.bld.brg.selectedIndex].value + "\n" +
		"Dispro\t" + document.bld.dsp.options[document.bld.dsp.selectedIndex].value + "\n" +
		"Serial\t" + document.bld.sn.value + "\n" +
		"Bimage\t" + document.bld.bi.value + "\n" +
		"\n# Vlan Specific\n" +
		"VLnams\t" + document.bld.vln.value + "\n" +
		"VTPdom\t" + document.bld.vtd.value + "\n" +
		"VTPmod\t" + document.bld.vtm.value + "\n" +
		"\n# Interfaces\n" +
		"IFalia\t" + document.bld.ial.value + "\n" +
		"IFalix\t" + document.bld.iax.value + "\n" +
		"IFvlan\t" + document.bld.ivl.value + "\n" +
		"IFvlix\t" + document.bld.ivx.value + "\n" +
		"IFdupl\t" + document.bld.idu.value + "\n" +
		"IFduix\t" + document.bld.idx.value + "\n" +
		"Halfdp\t" + document.bld.hdv.value + "\n" +
		"Fulldp\t" + document.bld.fdv.value + "\n" +
		"\n# Modules\n" +
		"Modesc\t" + document.bld.mde.value + "\n" +
		"Moclas\t" + document.bld.mcl.value + "\n" +
		"Movalu\t" + document.bld.mcv.value + "\n" +
		"Moslot\t" + document.bld.msl.value + "\n" +
		"Modhw\t" + document.bld.mhw.value + "\n" +
		"Modsw\t" + document.bld.msw.value + "\n" +
		"Modfw\t" + document.bld.mfw.value + "\n" +
		"Modser\t" + document.bld.msn.value + "\n" +
		"Momodl\t" + document.bld.mmo.value + "\n" +
		"\n# RRD Graphing\n" +
		"CPUutl\t" + document.bld.cpu.value + "\n" +
		"Temp\t" + document.bld.tmp.value + "\n" +
		"MemCPU\t" + document.bld.mcp.value + "\n" +
		"MemIO\t" + document.bld.mio.value;
	}
}

function bridgeset(idx) {
	if ('3' == idx){
		entidymod('10');
	}
	update();
}

function setgen(gen) {
	if('1' == gen){
		document.bld.sn.value = "1.3.6.1.4.1.9.3.6.3.0";
		document.bld.bi.value = "1.3.6.1.4.1.9.2.1.73.0";
		document.bld.ico.value = "genc";
		document.bld.ver.selectedIndex  = 2;
		document.bld.os.selectedIndex  = 1;
		document.bld.brg.selectedIndex  = 2;
		document.bld.dsp.selectedIndex  = 1;
		document.bld.vln.value = "1.3.6.1.4.1.9.9.46.1.3.1.1.4.1";
		document.bld.vtd.value = "1.3.6.1.4.1.9.9.46.1.2.1.1.2.1";
		document.bld.vtm.value = "1.3.6.1.4.1.9.9.46.1.2.1.1.3.1";
	}else if ('2' == gen){
		document.bld.sn.value = "1.3.6.1.4.1.1991.1.1.1.1.2.0";
		document.bld.bi.value = "1.3.6.1.4.1.1991.1.1.2.1.49.0";
		document.bld.ico.value = "genf";
		document.bld.ver.selectedIndex  = 2;
		document.bld.os.selectedIndex  = 6;
		document.bld.brg.selectedIndex  = 1;
		document.bld.dsp.selectedIndex  = 0;
		document.bld.vln.value = "1.3.6.1.4.1.1991.1.1.3.2.1.1.25";
	}else if ('3' == gen){
		document.bld.sn.value = "1.3.6.1.4.1.45.1.6.3.1.6.0";
		document.bld.bi.value = "1.3.6.1.4.1.45.1.6.4.2.1.10.0";
		document.bld.ico.value = "genn";
		document.bld.ver.selectedIndex  = 1;
		document.bld.os.selectedIndex  = 5;
		document.bld.brg.selectedIndex  = 1;
		document.bld.dsp.selectedIndex  = 0;
		document.bld.vln.value = "1.3.6.1.4.1.2272.1.3.2.1.2";
	}else{
		document.bld.sn.value = "";
		document.bld.bi.value = "";
		document.bld.ico.value = "genh";
		document.bld.vln.value = "";
		document.bld.vtd.value = "";
		document.bld.vtm.value = "";
	}
	update();
}

function setint(typ) {
	if ('1' == typ){
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "1.3.6.1.2.1.10.7.2.1.19";
		document.bld.idx.value = "1.3.6.1.2.1.10.7.2.1.1";
		document.bld.hdv.value = "2";
		document.bld.fdv.value = "3";
		document.bld.ivl.value = "";
		document.bld.ivx.value = "";
	}else if ('2' == typ){
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "1.3.6.1.4.1.9.9.87.1.4.1.1.32";
		document.bld.idx.value = "1.3.6.1.4.1.9.9.87.1.4.1.1.25";
		document.bld.hdv.value = "2";
		document.bld.fdv.value = "1";
		document.bld.ivl.value = "1.3.6.1.4.1.9.9.68.1.2.2.1.2";
		document.bld.ivx.value = "";
	}else if ('3' == typ){
		document.bld.ial.value = "1.3.6.1.4.1.9.5.1.4.1.1.4";
		document.bld.iax.value = "1.3.6.1.4.1.9.5.1.4.1.1.11";
		document.bld.idu.value = "1.3.6.1.4.1.9.5.1.4.1.1.10";
		document.bld.idx.value = "1.3.6.1.4.1.9.5.1.4.1.1.11";
		document.bld.hdv.value = "1";
		document.bld.fdv.value = "2";
		document.bld.ivl.value = "1.3.6.1.4.1.9.9.68.1.2.2.1.2";
		document.bld.ivx.value = "";
	}else if ('4' == typ){
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "doublespeed";
		document.bld.idx.value = "";
		document.bld.hdv.value = "";
		document.bld.fdv.value = "";
		document.bld.ivl.value = "1.3.6.1.4.1.2272.1.3.3.1.7";
		document.bld.ivx.value = "";
	}else if ('5' == typ){
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "1.3.6.1.4.1.1991.1.1.3.3.1.1.4";
		document.bld.idx.value = "1.3.6.1.4.1.1991.1.1.3.3.1.1.38";
		document.bld.hdv.value = "1";
		document.bld.fdv.value = "2";
		document.bld.ivl.value = "1.3.6.1.4.1.1991.1.1.3.3.1.1.50";
		document.bld.ivx.value = "1.3.6.1.4.1.1991.1.1.3.3.1.1.38";
	}else{
		document.bld.ial.value = "";
		document.bld.iax.value = "";
		document.bld.idu.value = "";
		document.bld.idx.value = "";
		document.bld.hdv.value = "";
		document.bld.fdv.value = "";
		document.bld.ivl.value = "";
		document.bld.ivx.value = "";
	}
	update();
}

function setmod(typ) {
	if ('1' == typ){
		document.bld.mde.value = "1.3.6.1.2.1.47.1.1.1.1.2";
		document.bld.mcl.value = "1.3.6.1.2.1.47.1.1.1.1.5";
		document.bld.mcv.value = "9";
		//document.bld.mcs.value = "";
		document.bld.msl.value = "1.3.6.1.2.1.47.1.1.1.1.7";
		document.bld.mhw.value = "1.3.6.1.2.1.47.1.1.1.1.8";
		document.bld.msw.value = "1.3.6.1.2.1.47.1.1.1.1.9";
		document.bld.mfw.value = "1.3.6.1.2.1.47.1.1.1.1.10";
		document.bld.msn.value = "1.3.6.1.2.1.47.1.1.1.1.11";
		document.bld.mmo.value = "1.3.6.1.2.1.47.1.1.1.1.13";
	}else if ('2' == typ){
		document.bld.mde.value = "";
		document.bld.mcl.value = "";
		document.bld.mcv.value = "";
		//document.bld.mcs.value = "";
		document.bld.msl.value = "1.3.6.1.4.1.9.5.1.3.1.1.25";
		document.bld.mhw.value = "1.3.6.1.4.1.9.5.1.3.1.1.18";
		document.bld.msw.value = "1.3.6.1.4.1.9.5.1.3.1.1.20";
		document.bld.mfw.value = "1.3.6.1.4.1.9.5.1.3.1.1.19";
		document.bld.msn.value = "1.3.6.1.4.1.9.5.1.3.1.1.26";
		document.bld.mmo.value = "1.3.6.1.4.1.9.5.1.3.1.1.17";
	}else if ('3' == typ){
		document.bld.mde.value = "1.3.6.1.4.1.9.3.6.11.1.3";
		document.bld.mcl.value = "";
		document.bld.mcv.value = "";
		//document.bld.mcs.value = "";
		document.bld.msl.value = "1.3.6.1.4.1.9.3.6.11.1.7";
		document.bld.mhw.value = "1.3.6.1.4.1.9.3.6.11.1.5";
		document.bld.msw.value = "1.3.6.1.4.1.9.3.6.11.1.6";
		document.bld.mfw.value = "";
		document.bld.msn.value = "1.3.6.1.4.1.9.3.6.11.1.4";
		document.bld.mmo.value = "1.3.6.1.4.1.9.3.6.11.1.2";
	}else if ('4' == typ){
		document.bld.mde.value = "1.3.6.1.4.1.45.1.6.3.3.1.1.5";
		document.bld.mcl.value = "1.3.6.1.4.1.45.1.6.3.3.1.1.1";
		document.bld.mcv.value = "3";
		//document.bld.mcs.value = "";
		document.bld.msl.value = "1.3.6.1.4.1.45.1.6.3.3.1.1.2";
		document.bld.mhw.value = "1.3.6.1.4.1.45.1.6.3.3.1.1.6";
		document.bld.msw.value = "";
		document.bld.mfw.value = "";
		document.bld.msn.value = "1.3.6.1.4.1.45.1.6.3.3.1.1.7";
		document.bld.mmo.value = "";
	}else{
		document.bld.mde.value = "";
		document.bld.mcl.value = "";
		document.bld.mcv.value = "";
		//document.bld.mcs.value = "";
		document.bld.msl.value = "";
		document.bld.mhw.value = "";
		document.bld.msw.value = "";
		document.bld.mfw.value = "";
		document.bld.msn.value = "";
		document.bld.mmo.value = "";
	}
	update();
}

function setrrd(typ) {
	if ('1' == typ){
		document.bld.cpu.value = "1.3.6.1.4.1.9.2.1.58.0";
		document.bld.tmp.value = "";
		document.bld.mcp.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.1";
		document.bld.mio.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.2";
	}else if ('2' == typ){
		document.bld.cpu.value = "1.3.6.1.4.1.9.9.109.1.1.1.1.5.1";
		document.bld.tmp.value = "";
		document.bld.mcp.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.1";
		document.bld.mio.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.2";
	}else if ('3' == typ){
		document.bld.cpu.value = "1.3.6.1.4.1.9.9.109.1.1.1.1.8.1";
		document.bld.tmp.value = "1.3.6.1.4.1.9.9.13.1.3.1.3.1";
		document.bld.mcp.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.1";
		document.bld.mio.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.2";
	}else if ('4' == typ){
		document.bld.cpu.value = "1.3.6.1.4.1.9.9.109.1.1.1.1.5.9";
		document.bld.tmp.value = "1.3.6.1.4.1.9.9.13.1.3.1.3.1";
		document.bld.mcp.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.1";
		document.bld.mio.value = "1.3.6.1.4.1.9.9.48.1.1.1.6.10";
	}else{
		document.bld.cpu.value = "";
		document.bld.tmp.value = "";
		document.bld.mcp.value = "";
		document.bld.mio.value = "";
	}
	update();
}

function get(oid) {
	window.open('inc/snmpget.php?ip=' + document.bld.ip.value + '&c=' + document.bld.co.value + '&oid=' + oid,'SNMP','scrollbars=1,menubar=0,resizable=1,width=400,height=200');
}

function walk(oid) {
	window.open('inc/snmpwalk.php?ip=' + document.bld.ip.value + '&c=' + document.bld.co.value + '&oid=' + oid,'SNMP','scrollbars=1,menubar=0,resizable=1,width=400,height=600');
}

//-->
</script>

<table bgcolor=#000000 cellspacing=1 cellpadding=6 border=0 width=100% align=center>
<tr bgcolor=#<?=$bg1?>><th width=80 rowspan=3><a href=<?=$_SERVER['PHP_SELF'] ?>>
<img src=img/32/tab.png border=0 title="Assists in generating device definitions.">
</a></th><td>

<form name="bld">
<table width=100%>

<tr><th align=right>IP</th><td><input type="text" name="ip" value="<?=$ip?>" size="20" onfocus=select(); title="target's IP address"></td>
<th align=right>Community</th><td><input type="text" name="co" value="<?=$c?>" size="20" onfocus=select(); title="target's SNMP community"></td></tr>

<tr bgcolor=#<?=$bg2?>><th colspan=4>
<img src="img/16/bcnl.png" align=left onClick="setgen();" title="Clear General">
<img src="img/gsw.png" align=left onClick="setgen('1');" title="Possible Cisco OIDs">
<img src="img/ksw.png" align=left onClick="setgen('2');" title="Possible Foundry OIDs">
<img src="img/bsw.png" align=left onClick="setgen('3');" title="Possible Nortel OIDs">
General</th></tr>
<tr><th align=right>
SysObjId</th><td>
<input type="text" name="so" value="<?=$so?>" size="30" title="Enter the sysobj id, which will be used as filename" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get('1.3.6.1.2.1.1.2.0');">
</td><th align=right>
SNMP version</th><td>
<select size=1 name="ver" title="Use 2HC, if device supports 64-bit counters" onchange="update();">
<option value="1">1
<option value="2" <?=$snt?>>2
<option value="2HC" <?=$thc?>>2HC				
</select>
<!-- <img src=img/16/bdwn.png onClick="walk('1.3.6.1.2.1.31.1.1.1.6');">  PHP does only v1, so this OID won't work! -->
</td></tr>
<tr><th align=right>
Type</th><td>
<input type="text" name="typ" value="<?=$typ?>" size="30" title="Use the most official type specification as possible" onfocus=select(); onchange="update();">
</td><th align=right>
OS</th><td>
 <select size=1 name="os" title="Choose operating system for your new device" onchange="update();"><option value="other">other
<option value="IOS" <?=$ios?>>IOS
<option value="CatOS" <?=$cos?>>CatOS
<option value="Cat1900" <?=$c19?>>Cat1900
<option value="Cvpn" <?=$cvp?>>Cvpn
<option value="Baystack" <?=$bas?>>Baystack
<option value="Ironware" <?=$iro?>>Ironware
<option value="ProCurve" <?=$pro?>>ProCurve
</select>
</td></tr>
<tr><th align=right>
Icon</th><td>
<input type="text" name="ico" value="<?=$ico?>" size="24" onfocus=select(); onchange="update();">
 <img src=img/16/tabi.png hspace=6 onClick="window.open('inc/browsedev.php','Icons','scrollbars=1,menubar=0,resizable=1,width=400,height=600');" title="Browse available icons">
</td><th align=right>
Bridge</th><td>
<select size=1 name="brg" title="Specify how to read forwarding MIBs, if it's a switch" onchange="bridgeset(document.bld.brg.selectedIndex);" >
<option value=""> none
<option value="normal" <?=$bno?>>normal
<option value="VLX" <?=$bvl?>>Vlan indexing
<option value="CAP"<?=$bca?>>Cisco access point
</select>
</td></tr>
<tr><th align=right>
Serial#</th><td>
<input type="text" name="sn" value="<?=$sn?>" size="30" title="OID for SN#" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.sn.value);">
</td><th align=right>
Discovery</th><td>
<select size=1 name="dsp" title="Choose discovery protocol" onfocus=select(); onchange="update();" >
<option value="">none
<option value="CDP" <?=$cdp?>>CDP
<option value="LLDP" <?=$ldp?>>LLDP
</select>
</td></tr>
<tr><th align=right>
Bootimage</th><td>
<input type="text" name="bi" value="<?=$bi?>" size="30" title="OID for bootimage" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.bi.value);">
</td><th align=right>
Vlan Names</th><td>
<input type="text" name="vln" value="<?=$vln?>" size="30" title="OID for Vlan names, if available" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.vln.value);">
</td></tr>
<tr><th align=right>
VTP Domain</th><td>
<input type="text" name="vtd" value="<?=$vtd?>" size="30" title="VTP OID is Cisco specific, but could be applied to other vendors as well" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.vtd.value);">
</td><th align=right>
VTP Mode</th><td>
<input type="text" name="vtm" value="<?=$vtm?>" size="30" title="OID to check whether it's a client, server or transparent" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.vtm.value);">
</td></tr>

<tr bgcolor=#<?=$bg2?>><th colspan=4>
<img src="img/16/bcnl.png" align=left onClick="setint('0');" title="Clear Section">
<img src="img/wsw.png" align=left onClick="setint('1');" title="Standard dot3">
<img src="img/gsw.png" align=left onClick="setint('2');" title="Cisco c2900">
<img src="img/gsw.png" align=left onClick="setint('3');" title="Cisco Stack">
<img src="img/bsw.png" align=left onClick="setint('4');" title="Nortel">
<img src="img/ksw.png" align=left onClick="setint('5');" title="Foundry">
Interfaces</th></tr>
<tr><th align=right>
IF Alias</th><td>
<input type="text" name="ial" value="<?=$ial?>" size="30" title="Specify, if enterprise specific alias is used instead of MIB2" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.ial.value);">
</td><th align=right>
Alias Index</th><td>
<input type="text" name="iax" value="<?=$iax?>" size="30" title="If needed to map to MIB2 IFindex" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.iax.value);">
</td></tr>
<tr><th align=right>
IF Duplex</th><td>
<input type="text" name="idu" value="<?=$idu?>" size="30" title="Duplex *is* somewhere in the enterprise tree!" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.idu.value);">
</td><th align=right>
Duplex Index</th><td>
<input type="text" name="idx" value="<?=$idx?>" size="30" title="If needed to map to MIB2 IFindex" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.idx.value);">
</td></tr>
<tr><th align=right>
Half Duplex</th><td>
<input type="text" name="hdv" value="<?=$idx?>" size="2" title="Value used for half-duplex" onfocus=select(); onchange="update();">
</td><th align=right>
Full Duplex</th><td>
<input type="text" name="fdv" value="<?=$idx?>" size="2" title="Value used for full-duplex" onfocus=select(); onchange="update();">
</td></tr>
<tr><th align=right>
IF Vlan</th><td>
<input type="text" name="ivl" value="<?=$ivl?>" size="30" title="OID for interface vlan has to be in the enterprise tree as well" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.ivl.value);">
</td><th align=right>
Vlan Index</th><td>
<input type="text" name="ivx" value="<?=$ivx?>" size="30" title="If needed to map to MIB2 IFindex" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.ivx.value);">
</td></tr>

<tr bgcolor=#<?=$bg2?>><th colspan=4>
<img src="img/16/bcnl.png" align=left onClick="setmod('0');" title="Clear Section">
<img src="img/wsw.png" align=left onClick="setmod('1');" title="Standard Entity MIB">
<img src="img/gsw.png" align=left onClick="setmod('2');" title="Cisco Stack MIB">
<img src="img/gsw.png" align=left onClick="setmod('3');" title="Older Cisco HW">
<img src="img/bsw.png" align=left onClick="setmod('4');" title="Nortel Baystack MIB">
Modules</th></tr>
<tr><th align=right>
Slot</th><td>
<input type="text" name="msl" value="<?=$msl?>" size="30" title="This OID is required, if you want to track modules" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.msl.value);">
</td><th align=right>
</th><td>
</td></tr>
<tr><th align=right>
Classlist</th><td>
<input type="text" name="mcl" value="<?=$mcl?>" size="30" title="Classes identify, what an actual module is" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.mcl.value);">
</td><th align=right>
Classvalue</th><td>
<input type="text" name="mcv" value="<?=$mcv?>" size="3" title="The actual value (e.g. Entity-MIB modules use 9" onfocus=select(); onchange="update();">
</td></tr>
<tr><th align=right>
Description</th><td>
<input type="text" name="mde" value="<?=$mde?>" size="30" title="Module description" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.mde.value);">
</td><th align=right>
Hardware</th><td>
<input type="text" name="mhw" value="<?=$mhw?>" size="30" title="Module hardware version" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.mhw.value);">
</td></tr>
<tr><th align=right>
Software</th><td>
<input type="text" name="msw" value="<?=$msw?>" size="30" title="Module software version" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.msw.value);">
</td><th align=right>
Firmware</th><td>
<input type="text" name="mfw" value="<?=$mfw?>" size="30" title="Module firmware version" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.mfw.value);">
</td></tr>
<tr><th align=right>
Serial#</th><td>
<input type="text" name="msn" value="<?=$msn?>" size="30" title="Module serial numbers" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.msn.value);">
</td><th align=right>
Model</th><td>
<input type="text" name="mmo" value="<?=$mmo?>" size="30" title="Sometimes an additional model# can be fetched" onfocus=select(); onchange="update();">
<img src=img/16/bdwn.png onClick="walk(document.bld.mmo.value);">
</td></tr>

<tr bgcolor=#<?=$bg2?>><th colspan=4>
<img src="img/16/bcnl.png" align=left onClick="setrrd('0');" title="Clear Section">
<img src="img/gsw.png" align=left onClick="setrrd('1');" title="Possible OIDs for older Cisco HW">
<img src="img/gsw.png" align=left onClick="setrrd('2');" title="Possible OIDs for newer Cisco HW">
<img src="img/gsw.png" align=left onClick="setrrd('3');" title="Possible OIDs for new Cisco HW">
<img src="img/gsw.png" align=left onClick="setrrd('4');" title="Possible OIDs for CatOS Cisco HW">
RRD Graphing</th></tr>
<tr><th align=right>
CPU Util</th><td>
<input type="text" name="cpu" value="<?=$cpu?>" size="30" title="Try to use a long average (e.g. 5min)" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.cpu.value);">
</td><th align=right>
Temperature</th><td>
<input type="text" name="tmp" value="<?=$tmp?>" size="30" title="Could be used for other values, if temperature is not supported" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.tmp.value);">
</td></tr>
<tr><th align=right>
Free CPU Mem</th><td>
<input type="text" name="mcp" value="<?=$mcp?>" size="30" title="Available cpu memory" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.mcp.value);">
</td><th align=right>
Free IO Mem</th><td>
<input type="text" name="mio" value="<?=$mio?>" size="30" title="Available linecard memory" onfocus=select(); onchange="update();">
<img src=img/16/brgt.png onClick="get(document.bld.mio.value);">
</td></tr>

</table>
</form>

</td>
</tr>
<tr bgcolor=#<?=$bg2?>><th>
<form method="post" action="<?=$_SERVER['PHP_SELF']?>" name="gen" action="<?=$_SERVER['PHP_SELF']?>">
<h2>Editor</h2>
<textarea rows="24" name="def" cols="80" onChange="dis='1';alert('Controls are disabled now!');"><?=$def?></textarea>
</td></tr>
<tr bgcolor=#<?=$bg1?>><th>
<input type="button" value="Update" name="up" onClick="update();" title="Update text now">
<input type="submit" value="Write" name="wr" title="Write .def file">
<input type="text" name="so" value="<?=$so?>" size="24" title="Filename">.def
</form>
</th></tr></table>

<?
include_once ("inc/footer.php");
?>
