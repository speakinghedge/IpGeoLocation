<?php

/**
 * IpGeoLocation: query ip geo database (data by http://www.maxmind.com) for 
 * client/given ip and return location informations and the autonomous system 
 * the ip belongs to.
 *
 * external dependencies
 * =====================
 *
 * 1. this script needs an external config file (plain ini format) named config.ini.php 
 *    it must contain the following values: db_host, db_schema, db_user, db_password
 *
 * 2. this script needs a database - use the table definitions and import scripts from the
 *    folder tools (and - of course - the data from maxmind)
 * 
 * 3. yaml output needs class spyc by https://github.com/mustangostang in folder lib/
 *
 *
 * optional parameter
 * ==================
 *
 * addr := { IP_DOTTED_FORMAT | IP_LONG }
 *
 *     if no address is given, the address of the calling host is used
 *
 * format := { json | yaml | xml }
 *
 *     the repsone can be formated in json (default), yaml or xml
 *
 * info := { a, l, s, c }
 *
 *     only query city (l)ocation, (c)ountry whois or autonomous (s)ystem.
 *     (a)ll informations is the default value. it is also possible to combine
 *     the flags 
 *
 *
 * examples
 * ========
 *
 * mylocation.naberius.de  -> returns all informations formated as json
 * 
 * mylocation.naberius.de?format=yaml&info=cs  -> returns all informations about country whois and the AS
 *
 * mylocation.naberius.de?format=yaml&info=l&addr=6.6.6.6  -> returns city location for ip 6.6.6.6
 *
 * 
 * used external code
 * ==================
 *
 * array2xml code by http://stackoverflow.com/users/396745/onokazu
 *
 *
 * license
 * =======
 * 
 * <hecke@naberius.de> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return.
 * 
 *
 * ToDo
 * ====
 * 
 * add support for IPv6
 *
 */

error_reporting(0);

class IpGeoLocation {

	private $data = array();
	private $db_con;
	private $output_formater;
	private $request_info;

	public function __construct($db_host, $db_schema, $db_user, $db_password, $request_info = "a",$output_format = "json") {

		try {
			$this->db_con = new PDO("mysql:host=$db_host;dbname=$db_schema", "$db_user", "$db_password");
		}
		catch (PDOException $e) {
			throw new Exception ("database connection");
		}

		$requsted_infos = array(
			"a" => array("get_location", "get_as", "get_country_whois"),
			"l" => array("get_location"),
			"s" => array("get_as"),
			"c" => array("get_country_whois"));

		$this->request_info = array();
		foreach($requsted_infos as $info_name => $info_fncs) {
			if (stristr($request_info, $info_name) !== false) {
				$this->request_info = array_unique(array_merge($this->request_info,$info_fncs));
			}
		}
		if (count($this->request_info) == 0) {
			throw new Exception ("invalid information requested");
		}

		$output_formaters = array(
			"json" => function($obj) {
				return json_encode($obj->getData());	
			}, 
			"yaml" => function($obj) {
				include('lib/Spyc.php');
				return $yaml = Spyc::YAMLDump($obj->getData(), 4, 60);
			}, 
			"xml" => function($obj) {
				function array_to_xml(array $arr, SimpleXMLElement $xml)
				{
					foreach ($arr as $k => $v) {
						is_array($v) ? array_to_xml($v, $xml->addChild($k)) : $xml->addChild($k, $v);
					}
					return $xml;
				}
				return array_to_xml($obj->getData(), new SimpleXMLElement('<geoiplocation/>'))->asXML();
			});
		if (!isset($output_formaters[$output_format])) {
			throw new Exception("invalid output format requested");
		}
		$this->output_formater = $output_formaters[$output_format];
	}

	public function __destruct() {
		$this->db_con = NULL;
	}

	public function localize($ip) {

		if (is_int($ip)) {
			$this->data["ipStr"] = long2ip($ip);
			$this->data["ipNum"] = $ip;
		} else {
			$this->data["ipNum"] = ip2long($ip);
			$this->data["ipStr"] = $ip;
		}

		if ($this->data["ipNum"] != ip2long($this->data["ipStr"]) || !filter_var($this->data["ipStr"], FILTER_VALIDATE_IP) ) {
			throw new Exception("inavlid value for ip");
		}

		$this->data["ipVer"] = 4;
		
		foreach($this->request_info as $info_fnc) {
			$this->$info_fnc();
		}
	}

	private function query_db($table, $fields, $section, $query = "") {

		try {
			$sth = NULL;
			if ($query == "") {
				$cols = "";
				foreach($fields as $field) {
					$cols .= $field.",";
				}
				$cols = substr($cols, 0, -1);
				$sth = $this->db_con->prepare("SELECT startIpNum,endIpNum,$cols from $table where startIpNum<=".$this->data['ipNum']." and endIpNum>=".$this->data['ipNum']);
			} else {
				$sth = $this->db_con->prepare($query);
			}

			if ($sth->execute() > 0) {
				$res = $sth->fetch();
				if (count($res) != (4 + count($fields)*2)) {
					throw new Exception("failed to get $section");
				}
				$this->data[$section]["ipRange"] = long2ip($res["startIpNum"])."..".long2ip($res["endIpNum"]);
				foreach($fields as $data_name => $field_name) {
					$this->data[$section][$data_name] = $res[$field_name];
				}
			}
		}
		catch (Exception $e ) {
			$this->data[$section]["error"] = "no data";
		}		
	}

	private function get_country_whois() {

		$fields = array("countryCode" => "country_code",
				"country" => "country");

		$this->query_db("ip_country_whois", $fields, "countryWhois");
	}	

	private function get_as() {

		$fields = array("ASN" => "asId",
				"company" => "name");

		$this->query_db("ipv4_as_names", $fields, "as");
	}

	private function get_location() {

		$fields = array("countryCode" => "country",
				"region" => "region",
				"city" => "city",
				"postalCode" => "postalCode",
				"latitude" => "latitude",
				"longitude" => "longitude",
				"metroCode" => "metroCode",
				"areaCode" => "areaCode");

		$query = "SELECT startIpNum,endIpNum,country,region,city,postalCode,latitude,longitude,metroCode,areaCode FROM city_blocks JOIN city_locations on city_locations.locId=city_blocks.locId where startIpNum<=".$this->data['ipNum']." and endIpNum>=".$this->data['ipNum'];

		$this->query_db("", $fields, "location", $query);
	}

	public function getData() {
		return $this->data;
	}

	public function __toString() {
		$call = $this->output_formater;
		return $call($this);
	}
}

try {
	$conf = parse_ini_file ("config.ini.php");
	$ip = isset($_GET['addr'])?$_GET['addr']:$_SERVER['REMOTE_ADDR'];
	$format = isset($_GET['format'])?$_GET['format']:"json";
	$info = isset($_GET['info'])?$_GET['info']:"a";

	$v = new IpGeoLocation($conf["db_host"],$conf["db_schema"],$conf["db_user"],$conf["db_password"], $info, $format);

	$v->localize($ip);	
	echo $v;
}
catch (Exception $e) {
	header("Error:".$e->getMessage(), false, 500);
}

?>
