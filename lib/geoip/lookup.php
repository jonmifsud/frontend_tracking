<?php
/**
 * Lookup the country by IP Address
 */
$ip = $_GET['ip']; 
include("geoip.inc");
$gi = geoip_open(dirname(__FILE__)."/data/GeoIP.dat",GEOIP_STANDARD);
echo strtolower(geoip_country_code_by_addr($gi, $ip)) . '-' . geoip_country_name_by_addr($gi, $ip);
geoip_close($gi);
?>