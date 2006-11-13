<html>
<head>
<script language="JavaScript">
<!--
function update(img){
	opener.document.bld.ico.value=img;
	self.close();
}
//-->
</script>
</head>
<body>
Click on the icon you wish to use. Please have a look at existing icons to find the best one for your device. Eventually I'll create new icons to accommodate new series.
<p>Classes are shown as 2 letter codes (e.g. ad = dual band access point, d2 = L2 distribution switch, rb = big router, w3 = L3 workgroup switch)
<?
if ( $handle = opendir("../img/dev") ){
	while (false !== ($f = readdir($handle))) {
		if ( stristr($f,'.png') ){
			$icon[] = $f;
		}
	}
	closedir($handle);
	sort($icon);
	$p = "";
	foreach ($icon as $i){
			$n = str_replace(".png","",$i);
			$t = substr($i, 0, 2);
			if ($t <> $p){
				echo "<h3>$t</h3>";
			}
			$p = $t;
			echo "<img src=../img/dev/$i title=\"$n\" hspace=4 vspace=4 onClick=\"update('$n');\">\n";

	}
}
?>

</body>
</html>
