<?php
	/* db config */
	$conf = parse_ini_file ("../config.ini.php");
	$db_inserts_per_call = 2000;
	$db_disable_keys_during_insert = true;
	$db_truncate_table = true;

	/* data config */
	$table_source_file = "GeoLiteCity-Blocks.csv";
	$table_name = "city_blocks";
	$table_colums = 3;
	/*                                        name_in_table      pos_in_csv_file   */
	$table_field_config = array ( 	0 => array( "name" => "startIpNum", "csv_pos" => 0),
					1 => array( "name" => "endIpNum", "csv_pos" => 1),
					2 => array( "name" => "locId", "csv_pos" => 2),
					);
	$table_ignore_first_lines = 2;

	/*********************************************************************/
	function enable_keys($db, $table_name) {
		echo "\n *** enable keys ***\n\n";
		$db->query("ALTER TABLE `".$table_name."` ENABLE KEYS; SET autocommit=1;SET unique_checks=1;");
	}

	/*********************************************************************/

	if (count($table_field_config) != $table_colums) {
		die("\nmissing column entry in table_field_config\n\n");
	}
	
	$reorder = false;
	$fields = array();
	for ($v = 0; $v < $table_colums; $v++) {
		if ($table_field_config[$v]["csv_pos"] != $v) {
			$reorder = true;
		}
		$fields[$v] = $table_field_config[$v]["name"];
	}

	$insert_head = "insert into $table_name (".implode(",", $fields).") VALUES ";

	$db = new PDO("mysql:host=".$conf["db_host"].";dbname=".$conf["db_schema"], $conf["db_user"], $conf["db_password"]) or die ("\nFailed to open db connection\n\n");

	if ($db_disable_keys_during_insert) {
		$db->query("ALTER TABLE `".$table_name."` DISABLE KEYS; SET autocommit=0;SET unique_checks=0;") or die("\nfailed to disable keys\n\n");
		register_shutdown_function('enable_keys', $db, $table_name);
	}

	if ($db_truncate_table) {
		$db->query("TRUNCATE TABLE `".$table_name."`");
	}
		
	$f=fopen($table_source_file, "r") or die("\nfailed to open file ".$table_source_file."\n\n");

	for ($i = 0; $i < $table_ignore_first_lines; $i++) {
		fgets($f);
	}
	
	$i = 0;
	$entry = 0;
	$values = "";
	$tmp = "";
	while ($line = fgetcsv($f)) {
		$i++;
		if (count($line) != $table_colums ) {
			echo "ignored entry in line ".$i+$table_ignore_first_lines.":";
			print_r($line);
		} else {
			$entry++;
			if ($reorder) {
				$tmp = array();
				for ($v = 0; $v < $table_colums; $v++) {
					$tmp[$v] = $line[$table_field_config[$v]["csv_pos"]];
				}
				$line = $tmp;
			}
			
			/* build entry */
			$values .= "(\"" . implode("\",\"",$line) ."\"),";

			if ( ($entry % $db_inserts_per_call) == 0 ) {
				$values = substr($values,0, -1);
				echo "insert chunk ".($entry/$db_inserts_per_call)."...";
				$affected = $db->exec($insert_head.$values);
				if ($db_inserts_per_call != $affected) {
					print_r($db->errorInfo());
					echo "\n\n".$values."\n\n";
					die("\nfailed to insert all values (only inserted $affected lines)\n\n");
				}
				echo "Done.\n";
				$values = "";
			}
		} /* __is_valid_line */
	} /* __loop_lines */

	$values = substr($values,0, -1);
	echo "insert last chunk ".($entry/$db_inserts_per_call)."...";
	$db->exec($insert_head.$values);
	echo "Done.\n";

	fclose($f);
?>
