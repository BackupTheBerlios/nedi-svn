<?PHP
//===============================
// mySQL functions.
//===============================

function DbConnect($host,$user,$pass,$db){
	$l = @mysql_connect($host,$user,$pass) or die("Could not connect to $db with $user@$host");
	mysql_select_db($db) or die("could not select $db");
	return $l;
}

function DbQuery($q,$l){
	return @mysql_query($q,$l);
}

function DbClose($l){
        return @mysql_close($l);
}

function DbNumRows($r){
        return @mysql_num_rows($r);
}

function DbFetchRow($r){
        return @mysql_fetch_row($r);
}

function DbFetchArray($r){
        return mysql_fetch_array($r, MYSQL_ASSOC);
}

function DbFreeResult($r){
        return @mysql_free_result($r);
}

function DbAffectedRows($l){
        return @mysql_affected_rows($l);
}

function DbError($l){
        return @mysql_error($l);
}

//===============================================================================
// New approach for queries:
//
// $tab	= table to apply query to
// $do 	's'= select (is default), 'i'=insert (using $in for columns and $st for values), 't'=show tables, 'c'=show columns,
//	'g'=group ( $col is counted/grouped), 'a'=average, 'u'=update (using $in,$st to set and $col,$ord to match), 'd'=delete
// $col	= column(s) to display (* is default), or to group by
// $ord	= order by (where device also takes numerical interface sorting (with /) into account)
// $lim	= limiting results
// $in,op,st	= array of columns,operators and strings to be used for WHERE in UPDATE, INSERT, SELECT and DELETE queries
// $co	= combines current values with the next series of $in,op,st
//
// SELECT and DELETE columns treatment: 
// * ip:	Input will be converted to decimal, in case of dotted notation and masked if a prefix is set.
// * time:	Time will be turned into EPOC, if it's not a number already.
// * mac:	. : - are removed
//
function GenQuery($tab,$do='s',$col='*',$ord='',$lim='',$in=array(),$op=array(),$st=array(),$co=array() ){

	if($do == 'i'){
		return "INSERT INTO $tab (". implode(',',$in) .") VALUES (\"". implode('","',$st) ."\")";
	}elseif($do == 'u'){
		if( $in[0] ){
			$x = 0;
			foreach ($in as $c){
				if($c){$s[]="$c=\"$st[$x]\"";}
				$x++;
			}
			return "UPDATE $tab SET ". implode(',',$s) ." WHERE $col=\"$ord\"";
		}
	}elseif($do ==  't'){
		return "SHOW TABLES";
	}elseif($do == 'c'){
		return "SHOW COLUMNS FROM $tab";
	}else{
		$l = ($lim) ? "LIMIT $lim" : "";
		if($ord == 'ifname'){
			$o = "ORDER BY device,SUBSTRING_INDEX(ifname, '/', 1), SUBSTRING_INDEX(ifname, '/', -1)*1+0";
		}elseif($ord == 'ifname desc'){
			$o = "ORDER BY device desc,SUBSTRING_INDEX(ifname, '/', 1), SUBSTRING_INDEX(ifname, '/', -1)*1+0";
		}elseif($ord){
			$o = "ORDER BY $ord";
		}else{
			$o = "";
		}
		if( isset($st[0]) and $st[0] != ""  ){
			$w = "WHERE";
			$x = 0;
			do{
				$cop = isset($co[$x]) ? $co[$x] : "";
				if( $op[$x] ){
					$c = $in[$x];
					$v = $st[$x];
					if( preg_match("/^(firstseen|lastseen|time|i[fp]update)$/",$c) and !preg_match("/^[0-9]+$/",$v) ){
						$v = strtotime($v);
					}elseif($c == 'mac'){
						$v = preg_replace("/[.:-]/","", $v);
					}elseif(preg_match("/^(origip|ip)$/",$c) and !preg_match('/^[0-9]+$/',$v) ){
						if( strstr($v,'/') ){
							list($ip, $prefix) = explode('/', $v);
							$dip = sprintf("%u", ip2long($ip));
							$dmsk = 0xffffffff << (32 - $prefix);
							$dnet = sprintf("%u", ip2long($ip) & $dmsk );
							$c = "$c & $dmsk";
							$v = $dnet;
						}else{
							$v = sprintf("%u", ip2long($v));
						}
					}				
					if(strpos($op[$x],'exp') and $v == '' ){$v = '.';}
					$w .= " $c $op[$x] \"$v\" $cop";
				}
				$x++;
			}while($cop);
		}elseif( isset($co[0]) and $co[0] != "" ){
			$w = "WHERE $in[0] $co[0] $in[1]";
		}else{
			$w = "";
		}
		if($do == 'd'){
			return "DELETE FROM $tab $w $o $l";
		}elseif($do == 'g'){
			return "SELECT $col,count(*) FROM  $tab $w GROUP BY $col $o $l";
		}elseif($do == 'a'){
			return "SELECT $col,count(*),avg($lim) FROM  $tab $w GROUP BY $col $o";
		}else{
			return "SELECT $col FROM $tab $w $o $l";
		}
	}
}

?>