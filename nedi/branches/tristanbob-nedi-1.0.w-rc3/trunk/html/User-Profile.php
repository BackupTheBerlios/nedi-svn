<?

/*
#============================================================================
# Program: User-Profile.php
# Programmer: Remo Rickli
#
# DATE		COMMENT
# -----------------------------------------------------------
# 08/03/05	initial version.
# 10/03/06	new SQL query support
*/

$bg1	= "DDBB99";
$bg2	= "EECCAA";
$btag	= "";
$nocache= 0;
$calendar= 0;
$refresh = 0;

include_once ("inc/header.php");

$name = $_SESSION['user'];

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if(isset($_GET['up']) ){
	if($_GET['pass'] and $_GET['pass'] == $_GET['vpas']  ){
		$pass = md5( $_GET['pass'] );
		$query	= GenQuery('user','u','name',$name,'',array('password'),array('='),array($pass) );
		if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h3>";}else{echo "<h3>$name's password $upokmsg</h3>";}
	}else{
		echo "$n1rmsg";
	}
	if(isset($_GET['email'])){
		$query	= GenQuery('user','u','name',$name,'',array('email'),array('='),array($_GET['email']) );
		if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h3>";}else{echo "<h3>$name's email $upokmsg</h3>";}
	}
	if(isset($_GET['phone'])){
		$query	= GenQuery('user','u','name',$name,'',array('phone'),array('='),array($_GET['phone']) );
		if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h3>";}else{echo "<h3>$name's phone $upokmsg</h3>";}
	}
	if(isset ($_GET['comment'])){
		$query	= GenQuery('user','u','name',$name,'',array('comment'),array('='),array($_GET['comment']) );
		if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h3>";}else{echo "<h3>$name's comment $upokmsg</h3>";}
	}
}elseif( isset($_GET['lang']) ){
	echo "<h3>Feedback language set to $_GET[lang]</h3>";
	$query	= GenQuery('user','u','name',$name,'',array('language'),array('='),array($_GET['lang']) );
	@DbQuery($query,$link);
}
$query	= GenQuery('user','s','*','','',array('name'),array('='),array($name) );
$res	= @DbQuery($query,$link);
$uok	= @DbNumRows($res);
if ($uok == 1) {
	$u = @DbFetchRow($res);
}else{
	echo "<h4 align=center>user $name doesn't exist! ($uok)</h4>";
	die;
}

?>
<h1>User Profile</h1>
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name=pro>
<table bgcolor=#000000 cellspacing=1 cellpadding=6 border=0 width=100%>
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=img/32/smil.png border=0 title="Set your personal information"></a>
<br><?=$name?></th>
<th valign=top align=right>
Password <input type="password" name="pass" size="12"><p>
Verify <input type="password" name="vpas" size="12">
</th>

<th valign=top>Language<p>
<SELECT name="lang" size=2 onchange="this.form.submit();" >
<OPTION VALUE="eng" <?=($u[13] == 'eng')?"selected":""?> >English
<OPTION VALUE="ger" <?=($u[13] == 'ger')?"selected":""?> >Deutsch
</SELECT>
</th>
<th valign=top align=right>
Email <input type="text" name="email" size="32" value="<?=$u[8]?>" >
Phone <input type="text" name="phone" size="12" value="<?=$u[9]?>" >
<p>
Comment <input type="text" name="comment" size="50" value="<?=$u[12]?>" >
</th>

</th>
<th width=80><input type="submit" name="up" value="Update"></th>
</tr></table>
<h2>Groups</h2>
<table bgcolor=#666666 cellspacing=1 cellpadding=8 border=0 width=100%>
<tr bgcolor=#<?=$bg2?> >
<th>Admin</th><th>Network</th><th>Helpdesk</th><th>Monitoring</th><th>Manager</th><th>Other</th>
<th>Created on</th>
<tr bgcolor=#<?=$bgb?> >
<th><?=($u[2])?"<img src=img/32/cfg2.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[3])?"<img src=img/32/net.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[4])?"<img src=img/32/ring.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[5])?"<img src=img/32/sys.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[6])?"<img src=img/32/umgr.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[7])?"<img src=img/32/glob.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=date("j. M Y",$u[10])?></th>
</tr></table></form>
<?
include_once ("inc/footer.php");
?>
