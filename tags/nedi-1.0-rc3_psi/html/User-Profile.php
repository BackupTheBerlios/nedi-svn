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
# 17/07/07	improved announcements
*/

$bg1	= "DDBB99";
$bg2	= "EECCAA";
$msgfile= 'log/msg.txt';

include_once ("inc/header.php");

$name = $_SESSION['user'];
$_POST = sanitize($_POST);
$msg = isset( $_POST['msg']) ? $_POST['msg'] : "";

$link	= @DbConnect($dbhost,$dbuser,$dbpass,$dbname);
if(isset($_GET['up']) ){
	if($_GET['pass']){
		if($_GET['pass'] == $_GET['vpas']){
			$pass = md5( $_GET['pass'] );
			$query	= GenQuery('user','u','name',$name,'',array('password'),array('='),array($pass) );
			if( !@DbQuery($query,$link) ){echo "<h4 align=center>".DbError($link)."</h3>";}else{echo "<h3>$name's password $upokmsg</h3>";}
		}else{
			echo "$n1rmsg";
		}
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
<form method="get" action="<?=$_SERVER['PHP_SELF']?>" name="pro">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#<?=$bg1?>><th width=80><a href=<?=$_SERVER['PHP_SELF'] ?>><img src=<?=Smilie($name)?> border=0 title="Set your personal information"></a>
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
</tr></table></form>

<h2>Groups</h2>
<table bgcolor=#666666 <?=$tabtag?> >
<tr bgcolor=#<?=$bg2?> >
<th>Admin</th><th>Network</th><th>Helpdesk</th><th>Monitoring</th><th>Manager</th><th>Other</th>
<th>Created on</th>
<tr bgcolor=#<?=$bgb?> >
<th><?=($u[2])?"<img src=img/32/cfg.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[3])?"<img src=img/32/net.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[4])?"<img src=img/32/ring.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[5])?"<img src=img/32/sys.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[6])?"<img src=img/32/umgr.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=($u[7])?"<img src=img/32/glob.png>":"<img src=img/16/bcls.png>"?></th>
<th><?=date("j. M Y",$u[10])?></th>
</tr></table>

<?
if(preg_match("/adm/",$_SESSION['group']) ){
	if(isset($_POST['cme']) ){
		unlink($msgfile);
	}elseif(isset($_POST['sme']) ){
		$fh = fopen($msgfile, 'w') or die("Cannot write $msgfile!");
		fwrite($fh, "$msg");
		fclose($fh);
	}

?>
<form method="post" action="<?=$_SERVER['PHP_SELF']?>" name="ano">
<table bgcolor=#000000 <?=$tabtag?> >
<tr bgcolor=#eeee88>
<th width="80">
<input type="button" value="Bold" OnClick='document.ano.msg.value = document.ano.msg.value + "<b></b>"';>
<p>
<input type="button" value="Italic" OnClick='document.ano.msg.value = document.ano.msg.value + "<i></i>"';>
<p>
<input type="button" value="Pre" OnClick='document.ano.msg.value = document.ano.msg.value + "<pre></pre>"';>
<p>
<input type="button" value="Break" OnClick='document.ano.msg.value = document.ano.msg.value + "<br>\n"';>
<p>
<input type="button" value="Title" OnClick='document.ano.msg.value = document.ano.msg.value + "<h2></h2>\n"';>
<p>
<input type="button" value="List" OnClick='document.ano.msg.value = document.ano.msg.value + "<ul>\n<li>\n<li>\n</ul>\n"';>
</th><th>
<textarea rows="16" name="msg" cols="100">
<?
	if (file_exists($msgfile)) {
		readfile($msgfile);
	};
?>
</textarea>
</th>
<th width="80">
<input type="submit" name="cme" value="Clear">
<p>
<input type="submit" name="sme" value="Save">
</th></table>
<?
}
if (file_exists('log/msg.txt')) {
	echo "<h2>Admin Message</h2><table bgcolor=#666666 $tabtag ><tr bgcolor=#eeee88 ><td>\n";
	include_once ($msgfile);
	echo "</td></tr></table>";
}

include_once ("inc/footer.php");
?>
