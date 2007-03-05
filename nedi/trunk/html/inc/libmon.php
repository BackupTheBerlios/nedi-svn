<?PHP

//===============================
// Monitoring related functions (and variables)
//===============================

$mico['10']  = "fogy";
$mico['50']  = "fogr";
$mico['100'] = "fobl";
$mico['150'] = "fovi";
$mico['200'] = "foor";
$mico['250'] = "ford";

//===================================================================
// Assign an icon to a node.
function Cimg($cat) {

	if($cat == 0)		{return "star";}
	elseif($cat == 2)	{return "find";}
	elseif($cat < 10)	{return "fiqu";}
	elseif($cat == 11)	{return "glof";}
	elseif($cat == 13)	{return "ele";}
	elseif($cat < 20)	{return "home";}
	elseif($cat == 21)	{return "powr";}
	elseif($cat == 23)	{return "nic";}
	elseif($cat == 24)	{return "cog";}
	elseif($cat == 25)	{return "chart";}
	elseif($cat < 30)	{return "dev";}
	elseif($cat == 31)	{return "flop";}
	elseif($cat == 32)	{return "cfg2";}
	elseif($cat == 33)	{return "dumy";}
	elseif($cat == 34)	{return "eyes";}
	elseif($cat < 40)	{return "user";}
}
//===================================================================
// Return bg color based on monitoring status
function GetStatus($n,$m,$a){
	
	global $pause;

	$downtime = $a * $pause;

	if($m){
		if ($n == 1){
			if($downtime > 86400){
				return array ("ff4422","is down for more than a day");
			}elseif($downtime > 3600){
				return array ("ff8866","is down for more than an hour");
			}elseif($downtime > 300){
				return array ("ffcc88","is down for more than 5 mins");
			}elseif($downtime > 0){
				return array ("ffff88","just went down");
			}else{
				return array ("ccff88","is up");
			}
		}else{
			if ($m == $n){
				$blu = "88";
			}else{
				$blu = "bb";
			}
			if($a > 1){
				return array ("ff8866","something is down");
			}elseif($a){
				return array ("ffff$blu","something is going down");
			}else{
				return array ("ccff$blu","all up");
			}
		}
	}else{
		return array ("FFFFFF","not monitored");
	}
}

?>
