<?php
$path = dirname(dirname(dirname(dirname(__FILE__)))).'/';
include_once($path.'engine/start.php');

	$ob = new KeetupFivestarEntity();
	$ob->value = 5;
	$ob->entity_guid = 56;

	$result = $ob->save();
	
	echo "<pre>";
var_dump($result);
echo "</pre>";

/**
 * @checked $ob->delete()
 * @checked $ob->save() // Create one
 * 
 * @todo $ob->save() // Update one
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