<?php
$path = dirname(dirname(dirname(dirname(__FILE__)))).'/';
include_once($path.'engine/start.php');


/**
 * @checked $ob->delete()
 * @checked $ob->save() // Create one
 * 
 * @checked $ob->save() // Update one
 */

/**
 * Create one
	$ob = new KeetupFivestarEntity();
	$ob->value = 5;
	$ob->entity_guid = 56;

	$result = $ob->save();
 */

/**
 * Delete one
 * 
 * $ob = new KeetupFivestarEntity(56);
 * @ob->delete();
 */

/**
 * Update one
 
 *  	$ob = new KeetupFivestarEntity(8);
	$ob->value = 1;
	$ob->entity_guid = 56;
 */



//$result = keetup_fivestar_get_entities(array('entity_guid' => 56, 'data_calculation' => 'AVG', 'order_by' => 'value DESC', 'joins' => array('LEFT JOIN elgg_entities e ON e.guid=fv.id	')));

//keetup_fivestar_get_entities(array('entity_guid' => 56, 'modified_time_upper' => time(), 'modified_time_lower' => time()));
keetup_fivestar_get_entities(array('entity_guid' => 56));


echo "<pre>";
var_dump($result);
echo "</pre>";
die;