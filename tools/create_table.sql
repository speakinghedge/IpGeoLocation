--
-- Table structure for table `city_blocks`
--

CREATE TABLE IF NOT EXISTS `city_blocks` (
  `startIpNum` bigint(20) NOT NULL,
  `endIpNum` bigint(20) NOT NULL,
  `locId` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `city_locations`
--

CREATE TABLE IF NOT EXISTS `city_locations` (
  `locId` int(11) NOT NULL,
  `country` varchar(45) DEFAULT NULL,
  `region` varchar(45) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `postalCode` varchar(45) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT '0',
  `metroCode` varchar(45) DEFAULT NULL,
  `areaCode` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`locId`),
  UNIQUE KEY `locId_UNIQUE` (`locId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ipv4_as_names`
--

CREATE TABLE IF NOT EXISTS `ipv4_as_names` (
  `startIpNum` bigint(20) NOT NULL,
  `endIpNum` bigint(20) NOT NULL,
  `asId` varchar(50) CHARACTER SET latin1 NOT NULL,
  `name` varchar(150) CHARACTER SET latin1 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ip_country_whois`
--

CREATE TABLE IF NOT EXISTS `ip_country_whois` (
  `startIpStr` varchar(25) CHARACTER SET latin1 NOT NULL,
  `endIpStr` varchar(25) CHARACTER SET latin1 NOT NULL,
  `startIpNum` bigint(20) NOT NULL,
  `endIpNum` bigint(20) NOT NULL,
  `country_code` varchar(100) CHARACTER SET latin1 NOT NULL,
  `country` varchar(150) CHARACTER SET latin1 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
