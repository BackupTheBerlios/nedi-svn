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
		echo "<OPTION VALUE \"".$row['GroupName']."\"\n";
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
<th>UserName</th><th>Name</th><th>EMail</th><th>Department</th><th>Workphone</th><th>Online</th><th>Action</th>
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
		// zuerst allet in variablen schreiebn, danach online SQL statement ausfuehren

//		radusr[$nres]["UserName"]  = $u['UserName'];
//		radusr[$nres]["Name"]      = $u['Name'];
//		radusr[$nres]["Mail"]      = $u['Mail'];
//		radusr[$nres]["WorkPhone"] = $u['WorkPhone'];
		//radusr[$nres]["Online"]    = $u['WorkPhone'];




		if ($row == "1"){ $row = "0"; $bg = $bga; $bi = $bia; }
		else{ $row = "1"; $bg = $bgb; $bi = $bib; }	
		$si = ord(substr(strtolower($u['UserName']), 0, 1)) + ord(substr(strtolower($u['UserName']), 1, 1)) + ord(substr(strtolower($u['UserName']), 2, 1)) - 291;
		if($si < 1 or $si > 70){$si = "36";}
		echo "<tr bgcolor=#$bg>\n";
		echo "<th bgcolor=#$bi><img src=img/smiles/$si.png title=\"Smile Index $si\"'><br>".$u['UserName']."</th>\n";
		echo "<td>".$u['Name']."</td><td align=center>".$u['Mail']."</td><td>".$u['Department']."</td><td align=center>".$u['WorkPhone']."</td>\n";
//		echo "<td bgcolor=#$cc>".$u['HomePhone']."</td>\n";
//		echo "<td bgcolor=#$lc>".$u['Mobile']."</td><th>\n";
		echo "<th>\n<a href=$_SERVER[PHP_SELF]?grp=$_GET[grp]&ord=$_GET[ord]&gad=$u[0]&mgp=oth><img hspace=$hs src=img/16/bcls.png border=0 title=+other></a>";
		echo "</th>\n";
		echo "<th><a href=$_SERVER[PHP_SELF]?grp=$_GET[grp]&ord=$_GET[ord]&del=$u[0]><img src=img/16/bcnl.png border=0 title='delete user' onclick=\"return confirm('Delete User $u[0]?')\"></a>\n";
		echo "<a href=$_SERVER[PHP_SELF]?grp=$_GET[grp]&ord=$_GET[ord]&psw=$u[0]><img src=img/16/key.png border=0 title='reset password' onclick=\"return confirm('Reset password for $u[0]?')\"></a></th></tr>\n";
		$nres++;
	}
	@DbFreeResult($radres);
}else{
	print @DbError($radlink);
}
//@DbClose($link);
echo "</table><table bgcolor=#666666 cellspacing=1 cellpadding=8 border=0 width=100%>\n";
echo "<tr bgcolor=#$bg2><td>$nres results using $query</td></tr></table>\n";

include_once ("inc/footer.php");
?>
