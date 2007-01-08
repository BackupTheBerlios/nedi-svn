<?
/*
#============================================================================
# Program: Other-Export.php
# Programmer: Pascal Voegeli
#
# DATE     		COMMENT
# -------- ------------------------------------------------------------------
# 27/03/06	initial version.
# 8/01/07		cosmetic changes, added DB layout (Remo Rickli).
*/

//  These are the variables used for the table colours
$bg1 = "CEC881";
$bg2 = "DFD992";

$btag = "";
$nocache = 0;
$calendar= 0;
$refresh = 0;

// Header.php contains the navigation and general settings for the UI
include_once("inc/header.php");

// The GET variables are rid of ".." to avoid directory traversal attacks
$_GET = sanitize($_GET);

// This is used later in the HTML form to ensure that there is always something selected,
// even if nothing has been passed to the script with GET
$sqltbl = isset($_GET['sqltbl']) ? $_GET['sqltbl'] : array("configs");
$action = isset($_GET['action']) ? $_GET['action'] : "";
$exptbl = isset($_GET['exptbl']) ? $_GET['exptbl'] : "";
$query  = isset($_GET['query']) ? $_GET['query'] : "";
$sep    = isset($_GET['sep']) ? $_GET['sep'] : "";
$quotes = isset($_GET['quotes']) ? "checked" : "";
$arch   = isset($_GET['arch']) ? $_GET['arch'] : "";
$timest = isset($_GET['timest']) ? "checked" : "";

// A connection to the database has to be made
$dblink = DbConnect($dbhost, $dbuser, $dbpass, $dbname);
?>

<!-- Begin of the HTML part -->

<h1>Data Export</h1>

<form method="get" name="export" action="<?=$_SERVER['PHP_SELF']?>">

<table bgcolor="#000000" <?=$tabtag?>>
	<tr bgcolor="#<?=$bg1?>">
		<th width="80">
			<a href="<?=$_SERVER['PHP_SELF'] ?>">
				<img src="img/32/flop.png" border="0" title="Create a config file archive or export/backup the NeDi database.">
			</a>
		</th>

		<!-- This <th> contains the export part of the form -->
		<th valign="top" width="35%" title="Alter the SQL query if you like. Only SELECT queries are allowed!">
			<!-- If the module is loaded without any GET variables the selected action is "Export" -->
			<input type="radio" name="action" value="export" <?=$action=="export"?"checked":""?>>Export</input>
			<p><br>
				<table align="center">
				<tr><td>Query Templates:</td>
				<!-- There are 3 different types of things that can be selected in this box: -->
				<!-- If a database table is selected, a "SELECT * FROM..." query is automatically written to the text box -->
				<!-- If the "Device Config Files" entry is selected, the separator and quotes fields are disabled and a specific -->
				<!-- query is written to the text box -->
				<!-- If one of the meaningless entiries is selected nothing's changed in the text box -->
				<td><select size="1" name="exptbl"  size=1 onchange="
					if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='none') {
						document.forms['export'].sep.disabled=false;
						document.forms['export'].quotes.disabled=false;
					}
					else if(document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value=='cfgfiles') {
						document.forms['export'].query.value='SELECT device, config, time FROM configs';
						document.forms['export'].sep.disabled=true;
						document.forms['export'].quotes.disabled=true;
					}
					else {
						document.forms['export'].query.value='SELECT * FROM '+document.forms['export'].exptbl.options[document.forms['export'].exptbl.selectedIndex].value;
						document.forms['export'].sep.disabled=false;
						document.forms['export'].quotes.disabled=false;
					}
				">
					<option value="none">select...</option>
					<option value="none">------ DB tables ------</option>
				<?  // Some PHP code
					// All the names of the database tables are collected and put into the select box
					$res = DbQuery(GenQuery("", "t"), $dblink);
					while($n = DbFetchRow($res)){
						echo "<option value=\"".$n[0]."\"".($n[0]==$exptbl?" selected":"").">".$n[0]."</option>\n";
					}
					echo "<option value=\"none\">-----------------------</option>";
					echo "<option value=\"cfgfiles\"".($exptbl=="cfgfiles"?" selected":"").">Device Config Files</option>\n";
				?>
				</select></td></tr>
				<tr><td>SELECT Query:</td>
				<td><input type="text" name="query" size="37" value="<?=$query?>"></input></td></tr>
				<tr><td>CSV Separator:</td>
				<td><select size="1" name="sep">
				<?  // Some PHP code
					$separators = array(";", ";;", ":", "::", ",", "/");
					foreach($separators as $s){
						echo "<option value=\"$s\"".($s==$sep?" selected":"").">".$s."</option>\n";
						#echo "<option value=\"".$sep."\"".($s==$sep?" selected":"").">".$s."</option>\n";		<-- Pascals Kaese ;-)
					}
				?>
				</select>
				&nbsp;&nbsp;<input type="checkbox" name="quotes" <?=$quotes?>>Use quotes</td></tr>
				</table>
			</p>
		</th>
	
		<!-- This <th> contains the SQL dump part of the form -->
		<th valign="top" width="25%" >
			<input type="radio" name="action" value="sqldump" <?=$action=="sqldump"?"checked":""?>>SQL Dump</input>
			<input type="radio" name="action" value="db" <?=$action=="db"?"checked":""?>>Layout</input><p>
			<p>
				Tables:
			</p>
			<p>
				<select multiple size="5" name="sqltbl[]">
				<?  // Some PHP code
					$res = DbQuery(GenQuery("", "t"), $dblink);
					while($n = DbFetchRow($res)){
						echo "<option value=\"".$n[0]."\"".(in_array($n[0], $sqltbl)?" selected":"").">".$n[0]."</option>\n";
					}
				?>
				</select>
			</p>
		</th>

		<!-- This <th> contains the archive settings -->
		<th valign="top">
			Archive type:
			<p>
				<table border="0" align="center">
				<tr valign="middle"><td><input type="radio" name="arch" value="gz" <?=($arch=="gz"||$arch=="")?"checked":""?>></td><td>Gzip</td></tr>
				<tr valign="middle"><td><input type="radio" name="arch" value="bz2" <?=$arch=="bz2"?"checked":""?>></td><td>Bzip2</td></tr>
				<tr valign="middle"><td><input type="radio" name="arch" value="tar" <?=$arch=="tar"?"checked":""?>></td><td>Tar</td></tr>
				<tr valign="middle"><td><input type="radio" name="arch" value="plain" <?=$arch=="plain"?"checked":""?>></td><td>Plain</td></tr>
				<tr valign="middle" height="5"></tr>
				<tr valign="middle"><td><input type="checkbox" name="timest" <?=$timest?>></td><td>With timestamp</td></tr>
				</table>
			</p>
		</th>
		<th width="80">
			<input type="submit" value="Create">
		</th>
	</tr>
</table>

</form>

<!-- End of the HTML part -->

<?
// If the "Export" radio button has been selected
if($action == "export") {
	// An empty query produces an error message
	if($query == "") {
		echo $qrymsg;
	}
	// An error message is also printed, if the query is not an SELECT query
	elseif(strtoupper(substr($query, 0, 7)) != "SELECT ") {
		echo $selmsg;
	}
	// And finally, if the query is invalid for any other reasons, an error message is printed
	elseif(!($res = DbQuery($query, $dblink))) {
		echo $resmsg;
	}
	// If the query starts with "SELECT device, config, time FROM configs " a config export is made
	// instead of a CSV export
	elseif(strtoupper(substr($query, 0, 43)) == "SELECT DEVICE, CONFIG, TIME FROM CONFIGS") {
		// This is the beginning of the output table
		echo "<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
		echo "<tr bgcolor=\"".$bg2."\"><th>Log</th></tr>\n";
		echo "<tr bgcolor=\"".$bgb."\"><td>\n";

		echo "Retrieving data from database<br>\n";
		//The query from the text box is executed
		$res = DbQuery($query, $dblink);
		$row = array();
		$configs = array();

		echo "Found ".DbNumRows($res)." devices<br>\n";

		// For each device found a new .conf file with the device name and the date of the
		// last configuration change contained in the file name is created
		while($row = DbFetchArray($res)) {
			$filename = "./log/".$row['device']."_".date("Ymd_Hi", $row['time']).".conf";

			$cfgfile = fopen($filename, "w");
			fwrite($cfgfile, $row['config']);
			fclose($cfgfile);

			// The filename is added to an array.
			// This array is later used to delete the .conf files after
			// they have been copied to the archive
			$configs[] = $filename;

			echo "Saved ".$filename."<br>\n";
			flush();
		}

		// CreateArchive() is called to make an archive out of all the configuration files that have been created
		if($arch == "plain") $arch = "tar";
		$archive = CreateArchive("./log/nediconfigs_".$_SESSION['user'], $arch, $configs, ($timest=="checked"?1:0));
		echo "Created archive ".$archive."<br>\n";

		// Now all the .conf files are deleted
		foreach($configs as $cfg) {
			unlink($cfg);
		}
		echo "Cleaned configuration files<br>\n";

		// This is the end of the output table. It also contains the link to the archive
		echo "</td></tr></table>\n<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
		echo "<tr bgcolor=\"#".$bg2."\"><td><a href=\"".$archive."\">Download your NeDi device config archive</a></td></tr>\n</table>\n";

		echo "<meta http-equiv=\"refresh\" content=\"0; URL=".$archive."\">\n";
	}
	// For any other SQL query this is processed
	else {
		// This is the beginning of the output table
		echo "<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
		echo "<tr bgcolor=\"".$bg2."\"><th>Log</th></tr>\n";
		echo "<tr bgcolor=\"".$bgb."\"><td>\n";

		// The CSV file is created by calling DbCsv()
		$csv = DbCsv($res, $sep, ($quotes=="checked"?"on":""), "./log/nedi.csv");
		echo "Created file ./log/nedi.csv from table ".$exptbl.($quotes=="checked"?" with surrounding quotes":"");
		echo " using separator '".$sep."'<br>\n";
		flush();

		// CreateArchive() is called to make an archive out of the CSV file that has been created
		$archive = CreateArchive("./log/nediexport_".$_SESSION['user'], $arch, "./log/nedi.csv", ($timest=="checked"?1:0));

		echo "Created archive ".$archive."<br>\n";

		// Now the CSV file is deleted
		unlink("./log/nedi.csv");
		echo "Cleaned ./log/nedi.csv<br>\n";

		// This is the end of the output table. It also contains the link to the archive
		echo "</td></tr></table>\n<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
		echo "<tr bgcolor=\"#".$bg2."\"><td><a href=\"".$archive."\">Download your NeDi CSV export</a></td></tr>\n</table>\n";

		echo "<meta http-equiv=\"refresh\" content=\"0; URL=".$archive."\">\n";
	}
}
// If the "SQL Dump" radio button has been selected
else if($action == "sqldump") {
	// This is the beginning of the output table
	echo "<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
	echo "<tr bgcolor=\"".$bg2."\"><th>Log</th></tr>\n";
	echo "<tr bgcolor=\"".$bgb."\"><td>\n";

	// The MySQL dump file is created by calling DbDump()
	$dump = DbDump($sqltbl, $dblink, "./log/nedi.sql");
	echo "Created file ./log/nedi.sql from table".(count($sqltbl)>1?"s":"")."<br>\n";
	foreach($sqltbl as $tbl) { echo "&nbsp;&nbsp;&nbsp;&nbsp;".$tbl."<br>\n"; }
	flush();

	// CreateArchive() is called to make an archive out of the SQL dump file that has been created
	$archive = CreateArchive("./log/nedidump_".$_SESSION['user'], $arch, "./log/nedi.sql", ($timest=="checked"?1:0));
	echo "Created archive ".$archive."<br>\n";

	// Now the dump file is deleted
	unlink("./log/nedi.sql");
	echo "Cleaned ./log/nedi.sql<br>\n";

	// This is the end of the output table. It also contains the link to the archive
	echo "</td></tr></table>\n<table align=\"center\" bgcolor=\"#666666\" cellspacing=\"1\" cellpadding=\"6\" border=\"0\" width=\"40%\">\n";
	echo "<tr bgcolor=\"#".$bg2."\"><td><a href=\"".$archive."\">Download your NeDi database dump</a></td></tr>\n</table>\n";

	echo "<meta http-equiv=\"refresh\" content=\"0; URL=".$archive."\">\n";
}
else if($action == "db") {
echo "<h2>NeDi Database Layout</h2>\n";
	$res = DbQuery(GenQuery("", "t"), $dblink);
	$col = 0;
	echo "<table width=100%><tr valign=top>\n";
	while($tab = DbFetchRow($res)){
		if($col == 4){echo "</tr><tr valign=top>\n";$col=0;}
		echo "<td><table bgcolor=#666666 $tabtag><tr bgcolor=#$bg2><th colspan=2>$tab[0]</th><th>NULL</th><th>KEY</th></tr>\n";
		$cres = DbQuery(GenQuery($tab[0], "c"), $dblink);
		$row = 0;
		while($c = DbFetchRow($cres)){
			if ($row % 2){$bg = $bga; $bi = $bia; }else{$bg = $bgb; $bi = $bib;}
			$row++;
			echo "<tr bgcolor=#$bg><th bgcolor=#$bi align=left>$c[0]</th><td>$c[1]</td><td>$c[2]</td><td>$c[3]</td></tr>\n";
		}
		echo "</table></td>";
		$col++;
	}
	echo "</td></tr></table>\n";
}
// Now the database connection can be closed
@DbClose($dblink);

// This is the footer on the very bottom of the page
include_once("inc/footer.php");

//================================================================================
// Name: DbDump()
// 
// Description: Creates a MySQL dump of a given set of database tables.
//              The dump is written to a file, whose name has to be passed to the function
//              when calling it
//
// Parameters:
//     $tables	- An array containing the names of the database tables that
//            	  should be included in the dump
//     $link	- A valid database connection identifier
//     $outfile	- The name of the file that should be created
//
// Return value:
//     none
//
function DbDump($tables, $link, $outfile) {
	// The dump file is created and opened
	$sqlfile = fopen($outfile, "w");

	// The comment header for the MySQL dump is created...
	$sql = "--\n";
	$sql .= "-- NeDi MySQL Dump - ".date("d M Y H:i")."\n";
	$sql .= "-- ------------------------------------------------------\n\n";
	// ...and written to the file
	fwrite($sqlfile, $sql);
	$sql = "";

	// All the tables are dumped one after the other
	foreach($tables as $tbl) {

		// Some SQL comments
		$sql .= "--\n";
		$sql .= "-- Table structure for table `".$tbl."`\n";
		$sql .= "--\n\n";

		// This is to make sure, that there is no table with the same name
		$sql .= "DROP TABLE IF EXISTS `".$tbl."`;\n";

		// This query gives us the complete SQL query to create the table structure
		$res = DbQuery("SHOW CREATE TABLE `$tbl`;", $link);

		$field = array();
		while($field = DbFetchArray($res)) {
			// Now the SQL command used to create the table structure is read from the database
			$sql .= $field['Create Table'].";\n\n";
		}

		// Another block of SQL comments
		$sql .= "--\n";
		$sql .= "-- Dumping data for table `".$tbl."`\n";
		$sql .= "--\n\n";

		// To make sure, that we are the only one working on the table, when importing the dump,
		// this SQL command is used
		$sql .= "LOCK TABLES `".$tbl."` WRITE;\n";

		$chfields = array();
		$field = array();

		// We want to check each column of the table, if its datatype is numeric or not.
		// Because if it's not numeric, we want to surround the content in the INSERT command
		// with "". But if it is numeric we must not put "" around the content.
		$res = DbQuery("DESCRIBE `$tbl`;", $link);
		while($field = DbFetchArray($res)) {
			// If a field is either of type "varchar()" or "text" the we add a '1' to the array...
			if((substr($field['Type'], 0, 8) == "varchar(") || ($field['Type'] == "text")) {
				$chfields[] = 1;
			}
			// ...otherwise, we add a '0'
			else {
				$chfields[] = 0;
			}
		}

		// The data, which we gathered since the last time we wrote something to the file
		// is written down to the SQL dump file.
		fwrite($sqlfile, $sql);
		$sql = "";

		// Now we want to have all the data from the table
		$res = DbQuery(GenQuery($tbl, "s", "*"), $link);
// 		$res = DbQuery("SELECT * FROM `".$tbl."`;", $link);

		$field = array();
		while($field = DbFetchRow($res)) {
			// For each record a new INSERT command is created
			$sql .= "INSERT INTO `".$tbl."` VALUES (";
			// The fields of the record are inserted one after the other
			for($i=0; $i<count($field); $i++) {
				// If the current field is a "varchar()" or "text" field
				// then it is surrounded by "". The array $chfields[]
				// tells us, if the current field is numeric (0) or not (1).
				if(($chfields[$i] == 1)&&($field[$i]!="")) $sql .= "\"";
				if($field[$i] != "") {
					$field[$i] = str_replace("\"", "\\\"", $field[$i]);
					$sql .= $field[$i];
				}
				else {
					$sql .= "NULL";
				}
 				if(($chfields[$i] == 1)&&($field[$i]!="")) $sql .= "\"";
				if($i < count($field)-1) $sql .= ", ";
			}
			$sql .= ");\n";

			// The INSERT command for the current record is written to the dump file
			fwrite($sqlfile, $sql);
			$sql = "";
		}

		// After having inserted all the data to the database table
		// the table can be unlocked
		$sql .= "UNLOCK TABLES;\n\n";
		fwrite($sqlfile, $sql);
		$sql = "";
	}

	// Finally the SQL dump file is closed
	fclose($sqlfile);
}

//================================================================================
// Name: DbCsv()
// 
// Description: Creates a CSV file of a given MySQL query result.
//              When calling the function you can choose if you want
//              to have quotes around the elements of the CSV file.
//              The separator between the elements has to be provided when
//              calling DbCsv()
//
// Parameters:
//     $res		- A valid MySQL result identifier
//     $sep		- The separator to put between the elements
//         		  This can also be longer than one character
//     $quotes	- "on" to have quotes around the elements
//     $outfile	- The name of the file that should be created
//
// Return value:
//     none
//
function DbCsv($res, $sep, $quotes, $outfile) {
	// The CSV file is created and opened
	$csvfile = fopen($outfile, "w");

	// The rows of the given result are processed one after the other
	while($row = DbFetchRow($res)) {
		$csv = "";

		// Each element is added to the string individually
		foreach($row as $field) {
			// If quotes are wished, they are put around the element
			if($quotes == "on") $csv .= "\"";
			$csv .= $field;
			if($quotes == "on") $csv .= "\"";
			$csv .= $sep;
		}
		// The last separator of a line is always cut off
		$csv = trim($csv, $sep);

		// For each row a single line of the file is used
		$csv .= "\r\n";

		// After having prepared the CSV row, it is written to the file
		fwrite($csvfile, $csv);
	}

	// When finished, the CSV file is closed
	fclose($csvfile);
}
?>

