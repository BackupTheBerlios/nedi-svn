<?PHP

//===============================
// Monitoring related functions (and variables)
//===============================

// Message icons based on level
$mico['10']  = "fogy";
$mico['50']  = "fogr";
$mico['100'] = "fobl";
$mico['150'] = "fovi";
$mico['200'] = "foor";
$mico['250'] = "ford";

//===================================================================
// Assign an icon to an incident category.
function Cimg($cat){

	if($cat == 1)		{return "star";}
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
function StatusBg($n,$m,$a,$bg="ffffff"){
	
	global $pause;

	$downtime = $a * $pause;

	if($m){
		if ($n == 1){
			if($downtime > 86400){
				return array ("ff4422","down for over a day");
			}elseif($downtime > 3600){
				return array ("ff8866","down for over an hour");
			}elseif($downtime > 300){
				return array ("ffcc88","down for over 5 mins");
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
		return array ($bg,"");
	}
}

//===================================================================
// Generate If status tables
function StatusIf($mode){

	global $link,$lim,$rrdstep,$trfa,$tabtag,$bia,$bib,$bg2;
	
	if($mode =="ie"){
		$doerr = 1;
		$query	= GenQuery('interfaces','s','device,ifname,speed,type,dinerr','dinerr desc',$lim,array('dinerr'),array('>'),array("$rrdstep"));
		$label = "In Err";
		$btn   = "rbup";
	}elseif($mode =="oe"){
		$doerr = 1;
		$query	= GenQuery('interfaces','s','device,ifname,speed,type,douterr','douterr desc',$lim,array('douterr'),array('>'),array("$rrdstep"));
		$label = "Out Err";
		$btn   = "rbdn";
	}elseif($mode =="it"){
		$doerr = 0;
		$query	= GenQuery('interfaces','s',"device,ifname,speed,type,dinoct*800/speed/$rrdstep",'dinoct/speed desc',$lim,array("dinoct*800/speed/$rrdstep"),array('>'),array($trfa));
		$label = "In Octets";
		$btn   = "bbup";
	}elseif($mode =="ot"){
		$doerr = 0;
		$query	= GenQuery('interfaces','s',"device,ifname,speed,type,doutoct*800/speed/$rrdstep",'doutoct/speed desc',$lim,array("doutoct*800/speed/$rrdstep"),array('>'),array($trfa));
		$label = "Out Octets";
		$btn   = "bbdn";
	}
	$res	= @DbQuery($query,$link);
	if($res){
		$nr = @DbNumRows($res);
		if($nr){
	?>
	<p><table bgcolor=#666666 cellspacing=1 cellpadding=2 width=100% ><tr bgcolor=<?=$bg2?>>
	<th colspan=2><img src=img/16/dumy.png title="Top <?=$lim?>"><br>Interface</th><th><img src=img/16/<?=$btn?>.png><br><?=$label?></th>
	<?
			$row = 0;
			while( ($t = @DbFetchRow($res)) ){
				if ($row % 2){$off=180;$bi = $bib;}else{$off=195;$bi = $bia;}
				$row++;
				$bg3= sprintf("%02x",$off);
				$tb = ($doerr)?$t[4]*5:($t[4]-$trfa)*2;
				if ($tb > 55){$tb = 55;}
				$rb = sprintf("%02x",$tb + $off);
				$ud	= rawurlencode($t[0]);
				$ui	= rawurlencode($t[1]);
				list($ifimg,$iftit) = Iftype($t[3]);
				echo "<tr bgcolor=#$rb$bg3$bg3><th bgcolor=#$bi><img src=img/$ifimg title=\"$iftit\">";
				echo "</th><td><a href=Devices-Status.php?dev=$ud&shg=on&shp=on>$t[0]</a> ";
				echo "<a href=Nodes-List.php?ina=device&opa==&sta=$ud&cop=AND&inb=ifname&opb==&stb=$ui>$t[1]</a></td>\n";
				if($doerr){
					echo "<th>$t[4]</th></tr>\n";
				}else{
					echo "<th>". sprintf("%1.1f",$t[4])." %</th></tr>\n";
				}
			}
			echo "</table>\n";
		}else{
			?><p><img src=img/32/<?=$btn?>.png title="<?=$label?>" hspace=8><img src=img/32/bchk.png title="OK"><?
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
}

//===================================================================
// Generate system status tables
function StatusSys($mode){

	global $link,$lim,$cpua,$mema,$tmpa,$tabtag,$bia,$bib,$bg2;

	if($mode =="cpu"){
		$query	= GenQuery('devices','s','name,cpu','cpu desc',$lim,array('cpu'),array('>'),array($cpua));
		$label = "CPU";
		$btn   = "cpu";
		$unit  = "%";
	}elseif($mode =="mem"){
		$query	= GenQuery('devices','s','name,memcpu','memcpu desc',$lim,array('memcpu','memcpu'),array('<','>'),array($mema,1),array('AND'));
		$label = "Mem";
		$btn   = "mem";
		$unit  = "B";
	}elseif($mode =="tmp"){
		$query	= GenQuery('devices','s','name,temp','temp desc',$lim,array('temp'),array('>'),array($tmpa));
		$label = "Temp";
		$btn   = "home";
		$unit  = "C";
	}
	$res	= @DbQuery($query,$link);
	if($res){
		$nr = @DbNumRows($res);
		if($nr){
	?>
	<p><table bgcolor=#666666 cellspacing=1 cellpadding=2 width=100%><tr bgcolor=<?=$bg2?>>
	<th colspan=2><img src=img/16/dev.png title="Top <?=$lim?>"><br>Device</th><th><img src=img/16/<?=$btn?>.png><br><?=$label?></th>
	<?
			$row = 0;
			while( ($t = @DbFetchRow($res)) ){
				if ($row % 2){$off=180;$bi = $bib;}else{$off=195;$bi = $bia;}
				$row++;
				$bg3= sprintf("%02x",$off);
				if($mode == "cpu"){
					$tb = $t[1]-$cpua;
				}elseif($mode == "mem"){
					$tb = ($mema - $t[1])/10000;
				}elseif($mode == "tmp"){
					$tb = pow(($t[1]-$tmpa),2);
				}
				if ($tb > 55){$tb = 55;}
				$rb = sprintf("%02x",$tb + $off);
				$ud	= rawurlencode($t[0]);
				echo "<tr bgcolor=#$rb$bg3$bg3><th bgcolor=#$bi>$row</th><td><a href=Devices-Status.php?dev=$ud>$t[0]</a></td>\n";
				echo "<th>$t[1] $unit</th></tr>\n";
			}
			echo "</table>\n";
		}else{
			?><p><img src=img/32/<?=$btn?>.png title="<?=$label?>" hspace=8><img src=img/32/bchk.png title="OK"><?
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
}


//===================================================================
// Generate device metainfo for topology based device tables
function TopoTable($reg="",$cty="",$bld=""){

	global $link,$dev,$deval,$dcity,$dbuild,$locsep,$maxcol,$bg1,$bg2,$tabtag;

	$query	= GenQuery('devices','s','*','','',array('location'),array('regexp'),array( TopoLoc($reg,$cty,$bld) ) );
	$res	= @DbQuery($query,$link);
	if($res){
		while( ($d = @DbFetchRow($res)) ){
			$l = explode($locsep, $d[10]);
			if( isset($deval[$d[0]]) ){						# Device is monitored if set...
				$dcity[$l[0]][$l[1]]['mn']++;
				$dcity[$l[0]][$l[1]]['al'] += $deval[$d[0]];
				$dbuild[$l[0]][$l[1]][$l[2]]['mn']++;
				$dbuild[$l[0]][$l[1]][$l[2]]['al'] += $deval[$d[0]];
				$mn = 1;
			}else{
				$mn = 0;
			}
			if (!$cty){
				$dcity[$l[0]][$l[1]]['nd']++;
			}elseif (!$bld){
				$dbuild[$l[0]][$l[1]][$l[2]]['nd']++;
				if($d[6] > 3){$dbuild[$l[0]][$l[1]][$l[2]]['nr']++;}
			}else{
				$dev[$l[3]][$l[4]][$d[0]]['rk'] = "$l[5] <i>$l[6]</i>";
				$dev[$l[3]][$l[4]][$d[0]]['ip'] = $d[1];
				$dev[$l[3]][$l[4]][$d[0]]['ty'] = $d[3];
				$dev[$l[3]][$l[4]][$d[0]]['co'] = $d[11];
				$dev[$l[3]][$l[4]][$d[0]]['ic'] = $d[18];
				$dev[$l[3]][$l[4]][$d[0]]['mn'] = $mn;
				$dev[$l[3]][$l[4]][$d[0]]['al'] = $deval[$d[0]];
				
			}
		}
		@DbFreeResult($res);
	}else{
		print @DbError($link);
	}
}

//===================================================================
// Generate region table
function TopoCities(){

	global $dcity,$locsep,$maxcol,$bg2,$tabtag;

	echo "<h2>Corporate Network</h2>\n";
	echo "<table bgcolor=#$bg2 $tabtag STYLE=\"table-layout:fixed\"><tr>\n";

	$col = 0;
	$rec = 0;
	foreach (array_keys($dcity) as $r){
		if ($rec == "1"){ $rec = "0"; $bi = "FFFFFF"; }
		else{ $rec = "1"; $bi = "F0F0F0"; }	
		$ur = rawurlencode($r);
		foreach (array_keys($dcity[$r]) as $c){
			$nd = $dcity[$r][$c]['nd'];
			$ci = CtyImg($dcity[$r][$c]['nd']);
			$mn = isset( $dcity[$r][$c]['mn']) ? $dcity[$r][$c]['mn'] : 0;
			$al = isset( $dcity[$r][$c]['al']) ? $dcity[$r][$c]['al'] : 0;
			list($bgm,$stat) = StatusBg($nd,$mn,$al,$bi);
			$uc = rawurlencode($c);
			if ($col == $maxcol){
				$col = 0;
				echo "</tr><tr>";
			}
	                echo "<td bgcolor=#$bgm valign=top>$r<p><center><a href=?reg=$ur&cty=$uc>\n";
			echo "<img src=img/$ci.png title=\"$nd devices $stat\" border=0></a>\n";
			echo "<br><a href=Devices-List.php?ina=location&opa=regexp&sta=$ur$locsep$uc$locsep>";
			echo "<b>$c</a><b></center></td>\n";
	                $col++;
		}
	}
	echo "<tr></table>\n";
}

//===================================================================
// Generate city table
function TopoBuilds($r,$c){

	global $dbuild,$locsep,$maxcol,$redbuild,$bg2,$tabtag;

	$ur = rawurlencode($r);
	$uc = rawurlencode($c);

	echo "<h2>$r - $c</h2>\n";
	echo "<table bgcolor=#$bg2 $tabtag STYLE=\"table-layout:fixed\"><tr>\n";

	$col = 0;
	foreach (array_keys($dbuild[$r][$c]) as $b){
		$nr =  $dbuild[$r][$c][$b]['nr'];
		$nd =  $dbuild[$r][$c][$b]['nd'];
		$mn = isset( $dbuild[$r][$c][$b]['mn']) ? $dbuild[$r][$c][$b]['mn'] : 0;
		$al = isset( $dbuild[$r][$c][$b]['al']) ? $dbuild[$r][$c][$b]['al'] : 0;
		$bi = BldImg($nd,$b);
		list($bgm,$stat) = StatusBg($nd,$mn,$al);
		if($nr > 1){
			$ri = "<img src=img/dev/rsb2.png title=\"$nr routers\" border=0>";
		}elseif($nr == 1){
			$ri = "<img src=img/dev/rsbl.png title=\"1 router\" border=0>";
		}else{
			$ri = "";
		}
		$ub = rawurlencode($b);
		if ($col == $maxcol){
			$col = 0;
			echo "</tr><tr>";
		}
		echo "<td bgcolor=$bgm valign=bottom align=center>\n";
		echo "<a href=?reg=$ur&cty=$uc&bld=$ub><img src=img/$bi.png title=\"$nd devices $stat\" border=0>$ri</a>\n";
		echo "<br><a href=Devices-List.php?ina=location&opa=regexp&sta=$ur$locsep$uc$locsep$ub$locsep><b>$b</b></a>\n";
		echo "</td>\n";
		$col++;
	}
	echo "<tr></table>\n";
}

//===================================================================
// Generate building table
function TopoFloors($r,$c,$b){

	global $dev,$maxcol,$bg1,$bg2,$tabtag;

	echo "<h2>$r - $c - $b</h2>\n";
	echo "<table bgcolor=#$bg1 $tabtag>\n";
	uksort($dev, "floorsort");
	$room = 0;
	foreach (array_keys($dev) as $fl){
		echo "<tr>\n\t<td bgcolor=$bg2 width=80><h2><img src=img/stair.png><br>$fl</h2></td>\n";
		$col = 0;
		ksort( $dev[$fl] );
		foreach (array_keys($dev[$fl]) as $rm){
			if ($room == "1"){ $room = "0"; $bi = "FFFFFF"; }
			else{ $room = "1"; $bi = "F0F0F0"; }	

			foreach (array_keys($dev[$fl][$rm]) as $d){
				$ip = long2ip($dev[$fl][$rm][$d]['ip']);
				$di = $dev[$fl][$rm][$d]['ic'];
				$co = $dev[$fl][$rm][$d]['co'];
				$rk = $dev[$fl][$rm][$d]['rk'];
				$mn = $dev[$fl][$rm][$d]['mn'];
				$al = $dev[$fl][$rm][$d]['al'];
				list($bgm,$stat) = StatusBg(1,$mn,$al,$bi);
				$ud = rawurlencode($d);
				if ($col == $maxcol){
					$col = 0;
					echo "</tr><tr><td>&nbsp;</td>\n";
				}
				echo "<td bgcolor=#$bgm valign=top><b>$rm</b> $rk<p><center>\n";
				echo "<a href=Devices-Status.php?dev=$ud><img src=img/dev/$di.png border=0 vspace=4 title=\"$stat\"></a><br>\n";
				echo "<b>$d</b><br>\n";
				echo "<a href=telnet://$ip>$ip</a><p>\n";
				echo"$co</td>\n";
				$col++;
			}
		}
	}
	echo "<tr></table>\n";
}

?>
