IpGeoLocation
=============

IP geo location: a landing page supporting json, yaml and xml output. based on maxmind GeoIP data.

 query ip geo database (data by http://www.maxmind.com) for 
 client/given ip and return location informations and the autonomous system 
 the ip belongs to.

external dependencies
---------------------

 1. this script needs an external config file (plain ini format) named config.ini.php 
    it must contain the following values: db_host, db_schema, db_user, db_password

 2. this script needs a database - use the table definitions and import scripts from the
    folder tools (and - of course - the data from maxmind)
 
 3. yaml output needs class spyc by https://github.com/mustangostang in folder lib/


optional parameter
------------------

 addr := { IP_DOTTED_FORMAT | IP_LONG }
 
     if no address is given, the address of the calling host is used

 format := { json | yaml | xml }

     the repsone can be formated in json (default), yaml or xml

 info := { a, l, s, c }

     only query city (l)ocation, (c)ountry whois or autonomous (s)ystem.
     (a)ll informations is the default value. it is also possible to combine
     the flags 


examples
--------

 mylocation.naberius.de  -> returns all informations formated as json
 
 mylocation.naberius.de?format=yaml&info=cs  -> returns all informations about country whois and the AS

 mylocation.naberius.de?format=yaml&info=l&addr=6.6.6.6  -> returns city location for ip 6.6.6.6

 
used external code
------------------

 array2xml code by http://stackoverflow.com/users/396745/onokazu


license
-------
 
 <hecke@naberius.de> wrote this file. As long as you retain this notice you
 can do whatever you want with this stuff. If we meet some day, and you think
 this stuff is worth it, you can buy me a beer in return.
 

ToDo
----

add support for IPv6
