<?php
	/* db config */
	$conf = parse_ini_file ("../config.ini.php");
	$db_inserts_per_call = 1000;
	$db_disable_keys_during_insert = true;
	$db_truncate_table = true;

	/* data config */
	$table_source_file = "GeoIPASNum2.csv";
	$table_name = "ipv4_as_names";
	$table_colums = 4;
	/*                                        name_in_table      pos_in_csv_file   */
	$table_field_config = array ( 	0 => array( "name" => "startIpNum", "csv_pos" => 0),
					1 => array( "name" => "endIpNum", "csv_pos" => 1),
					2 => array( "name" => "asId", "csv_pos" => 2, "reformater" => 'as_id_filter'),
					3 => array( "name" => "name", "csv_pos" => 2, "reformater" => 'as_name_filter')
					);
	$table_ignore_first_lines = 0;

	function as_id_filter($val) {
		$space_pos = 0;
		if (0 === strpos($val, "AS") && (false !== ($space_pos=strpos($val, " ")))) {
			return substr($val, 2, $space_pos - 2);
		}
		return;
	}

	function as_name_filter($val) {
		$space_pos = 0;
		if (0 === strpos($val, "AS") && (false !== ($space_pos=strpos($val, " ")))) {
			return substr($val, $space_pos + 1);
		}
		return;
	}

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
		if ( ($table_field_config[$v]["csv_pos"] != $v) || isset($table_field_config[$v]["reformater"])) {
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
		if (count($line) + 1 != $table_colums ) {
			echo "ignored entry in line ".$i+$table_ignore_first_lines.":";
			print_r($line);
		} else {
			$entry++;
			if ($reorder) {
				$tmp = array();
				for ($v = 0; $v < $table_colums; $v++) {
					$tmp[$v] = $line[$table_field_config[$v]["csv_pos"]];

					if (isset($table_field_config[$v]["reformater"])) {
						$tmp[$v] = $table_field_config[$v]["reformater"]($tmp[$v]);
					}

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
