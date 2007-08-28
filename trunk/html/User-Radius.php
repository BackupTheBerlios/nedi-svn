<?

/*
#============================================================================
# Program: User-Radius.php
# Programmer: Karel Stadler
#
# DATE     COMMENT
# -------- ------------------------------------------------------------------
# 28/02/06 v0.1		initial version.
*/

$bg1	= "CCAA77";
$bg2	= "DDBB88";

include_once ("inc/header.php");

$_GET = sanitize($_GET);
if(!$_GET[ord]){$_GET[ord] = "UserName";}
// ########################
// # THESE are important radius variables #
// ########################
// DB
$radiushost = "localhost";
$radiusdb   = "radius";
$radiususer = "radius";
$radiuspass = "st3mmtdah";

// NAS list
$nasip[0]   = "129.129.235.5";
$nasname[0] = "VovgaE05";
$nasdesc[0] = "VPN Node1";
$nasip[1]   = "129.129.235.6";
$nasname[1] = "VovgaE06";
$nasdesc[1] = "VPN Node2";
$nasip[2]   = "192.33.126.8";
$nasname[2] = "aragorn";
$nasdesc[2] = "H.323 Gatekeeper";

//connect the freeradius db get users
$radlink  = @DbConnect($radiushost,$radiususer,$radiuspass,$radiusdb);
//                                                    table                    column      where  value  operand,
$radquery = Query('usergroup','GroupName','','','','','','','','','GroupName');
$radres	  = @DbQuery($radquery,$radlink);
?>
<h1>Radius Accounts</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table bgcolor=#000000 cellspacing=1 cellpadding=6 border=0 width=100%>
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/acs.png border=0 title="Radius accounts currently used for companies using VPN and AIT Telematics"></a></th>
<th>User
<input type="text" name="usr" size="12" value="<?=$_GET[usr]?>">
<input type="submit" name="create" value="Create">
</th>
<th>Filter Group
<SELECT size=1 name="grp">
<OPTION VALUE="">----
<?
if($radres){
	while( ($row = @DbFetchArray($radres)) ){
		echo "<OPTION VALUE \"".$row['GroupName']."\" ";
		if ($_GET[grp] == $row['GroupName']) { echo "selected"; }
		echo ">".$row['GroupName']."\n";
	}
	@DbFreeResult($radres);
}else{
	print @DbError($radlink);
}
?>
</SELECT>
Sort by
<SELECT name="ord" size=1>
<OPTION VALUE="UserName" <? if($_GET[ord] == "UserName"){echo "selected";} ?>>UserName
<OPTION VALUE="created" <? if($_GET[ord] == "created"){echo "selected";} ?>>Creation Date
<OPTION VALUE="lastlogin" <? if($_GET[ord] == "lastlogin"){echo "selected";} ?>>Last Login
</SELECT>
<input type="submit" name="search" value="Search"><p>
</th>
</table></form>
<p>
<table bgcolor=#666666 cellspacing=1 cellpadding=8 border=0 width=100%>
<tr bgcolor=#<?=$bg2?> >
<th>UserName</th><th>Name</th><th>EMail</th><th>Department</th><th>Workphone</th><th>Online</th>
<?

//connect the freeradius db get users from userinfo table
$radlink  = @DbConnect($radiushost,$radiususer,$radiuspass,$radiusdb);

if ($_GET[grp]){
	//show group members

	// do a query, gathering all usernames first
	//SELECT UserName FROM usergroup WHERE GroupName = 'denied' ORDER BY UserName;
	$radquery	= Query('usergroup','UserName','GroupName','=',$_GET[grp]);
	$radres	= @DbQuery($radquery,$radlink);

	if($radres){
		while( ($row = @DbFetchArray($radres)) ){
			// build the string, looks like "UserName = 'stadler OR UserName = ..."
			$guser[] = $row['UserName'];
		}
	}else{
		print @DbError($radlink);
	}
	$sta = implode('|',$guser);
	// do the final query
	$radquery	= Query('userinfo','*',"UserName",'regexp',"^($sta)$",'','','','',$_GET[ord],'');
}else{
	$radquery	= Query('userinfo','*','','','','','','','',$_GET[ord],'');
}
$radres	= @DbQuery($radquery,$radlink);
$nres = 0;
if($radres){
	while( ($u = @DbFetchArray($radres)) ){
		// allet in variablen schreiben
		$radusr[$nres]["UserName"]   = $u['UserName'];
		$radusr[$nres]["Name"]       = $u['Name'];
		$radusr[$nres]["Department"] = $u['Department'];
		$radusr[$nres]["Mail"]       = $u['Mail'];
		$radusr[$nres]["WorkPhone"]  = $u['WorkPhone'];
		$nres++;
	}
	@DbFreeResult($radres);
}else{
	print @DbError($radlink);
}

// get online status on these users
for ($i = 0; $i < $nres; $i++) {
	$radquery	= Query('radacct','*','UserName','=',$radusr[$i]["UserName"],'AND','AcctStopTime','=','0',$_GET[ord],'');
	$radres	= @DbQuery($radquery,$radlink);
	if ($u = @DbFetchArray($radres)) { $radusr[$i]["Online"] = true; $radusr[$i]["NAS"] = $u['NASIPAddress']; }
	else { $radusr[$i]["Online"] = false; $radusr[$i]["NAS"] = ''; }
	@DbFreeResult($radres);
}

// print
for ($i = 0; $i < $nres; $i++) {		
	if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
	else{ $row = "1"; $bg = $bgb; $bi = $bib; }	
	$si = ord(substr(strtolower($radusr[$i]["UserName"]), 0, 1)) + ord(substr(strtolower($radusr[$i]["UserName"]), 1, 1)) + ord(substr(strtolower($radusr[$i]["UserName"]), 2, 1)) - 291;
	if($si < 1 or $si > 70){$si = "36";}
	echo "<tr bgcolor=#$bg>\n";
	echo "<th bgcolor=#$bi><img src=img/smiles/$si.png title=\"Klick to modify user\"'><br>".$radusr[$i]["UserName"]."</th>\n";
	echo "<td>".$radusr[$i]["Name"]."</td><td align=center>".$radusr[$i]["Mail"]."</td><td>".$radusr[$i]["Department"]."</td><td align=center>".$radusr[$i]["WorkPhone"]."</td>\n";
	//online
	if ($radusr[$i]["Online"]) { echo "<th><img src=img/smiles/$si.png title=\"Logged in on ".$radusr[$i]["NAS"]."\"'></th>\n"; }
	else { echo "<th>".$radusr[$i]["Online"]."</th>\n"; }
	echo "</tr>\n";
}

echo "</table><table bgcolor=#666666 cellspacing=1 cellpadding=8 border=0 width=100%>\n";
echo "<tr bgcolor=#$bg2><td>$nres results using $query</td></tr></table>\n";

include_once ("inc/footer.php");
?>
