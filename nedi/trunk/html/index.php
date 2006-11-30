<?
/*
#============================================================================
# Program: index.php (NeDi GUI)
# Programmer: Remo Rickli
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#============================================================================
# Visit http://nedi.sourceforge.net/ for more information.
#============================================================================
# DATE		COMMENT
# -----------------------------------------------------------
# 22/02/05	initial version.
# 17/03/06	new SQL query support
#============================================================================
*/

$bg1 = "88AACC";
$bg2 = "99BBDD";

include_once ("inc/libmisc.php");
ReadConf('usr');
include_once ("inc/lang-eng.php");

if($backend == 'MSQ'){
	include_once ('inc/libmsq.php');
}elseif($backend == 'CSV'){
	include_once ('inc/libcsv.php');
}else{
	print 'Backend not configured!';
	die;
}
$_POST = sanitize($_POST);

$failed = 0;
if(isset( $_POST['user'])  ){
	$pass = md5( $_POST['pass'] );

	$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
	if (stristr('p',$guiauth) && $_POST['user'] != "admin"){					# PAM code by Owen Brotherhood & bruberg
		if (!extension_loaded ('pam_auth')){dl("pam_auth.so");}
		$uok	= pam_auth($_POST['user'],$_POST['pass']);
		$query	= GenQuery('user','s','*','','',array('name'),array('='),array($_POST[user]) );
		$res    = @DbQuery($query,$link);
	}else{
		$pass = md5( $_POST['pass'] );
		$query	= GenQuery('user','s','*','','',array('name','password'),array('=','='),array($_POST['user'],$pass),array('AND') );
		$res    = @DbQuery($query,$link);
		$uok    = @DbNumRows($res);
	}
	if ($uok == 1) {
		$usr = @DbFetchRow($res);
		session_start(); 

		$_SESSION['user']	= $_POST['user'];
		$_SESSION['group']	= "usr,";
		if ($usr[2]) {$_SESSION['group']	.= "adm,";}
		if ($usr[3]) {$_SESSION['group']	.= "net,";}
		if ($usr[4]) {$_SESSION['group']	.= "dsk,";}
		if ($usr[5]) {$_SESSION['group']	.= "mon,";}
		if ($usr[6]) {$_SESSION['group']	.= "mgr,";}
		if ($usr[7]) {$_SESSION['group']	.= "oth,";}

		if ($usr[13]) {$_SESSION['lang']	= $usr[13];}
		else{$_SESSION[lang] = "eng";}

		$now = time();
		$query	= GenQuery('user','u','name',$_POST['user'],'',array('lastseen'),'',array($now) );
		@DbQuery($query,$link);

	}else{
		print @DbError($link);
	}
	if(isset ($_SESSION['group'])){
		echo "<script>document.location.href='User-Profile.php';</script>\n";
		exit();
	} else {
		$failed = 1;
	}
}

?>
<html>
<head><title>NeDi Login</title>
<link href="inc/style.css" type=text/css rel=stylesheet>
<link rel="shortcut icon" href="img/favicon.ico">
</head>
<body onLoad=document.login.user.focus();>
<?=($failed)?$logmsg:""?>
<p>
<table border=0 cellspacing=1 cellpadding=8 bgcolor=#000000 width=50% align=center>
<tr bgcolor=#<?="$bg2" ?>><td align=center background=img/blubg.png colspan=3><img src=img/nedib.png border=0></td></tr>
<tr bgcolor=#D0D0D0>
<th align=center colspan=3>
<img src=img/nedie.jpg border=0>

<p><hr>
<?=$disc?>
</th></tr>

<form name="login" method="post" action="<?=$_SERVER['PHP_SELF']?>">

<tr bgcolor=#<?="$bg2" ?>>
<th>User <input type="text" name="user" size="12"></th>
<th>Pass <input type="password" name="pass" size="12"></th>
<th><input type="submit" value="Login"></th>
</tr>

</form>

</table>
</body>
