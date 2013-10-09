<?php
$path = dirname(dirname(dirname(dirname(__FILE__)))).'/';
include_once($path.'engine/start.php');

//
//$metadata = elgg_get_metadata(array(
//	'guid' => 45,
//	'metadata_name' => 'keetup_fivestar_rating',
//));
//
//echo "<pre>";
//print_r($metadata);
//echo "</pre>";
//die;

$kh = new KeetupFivestarHandler();

echo "<pre>";
var_dump($kh);
echo "</pre>";
die;