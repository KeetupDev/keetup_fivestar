<?php

/**
 * Get a fivestar entity by id
 * 
 * @param type $id
 * 
 * @return KeetupFivestarEntity | NULL
 */
function keetup_fivestar_get_from_id($id) {

	if (!is_numeric($id) || empty($id)) {
		return NULL;
	}

	$fivestar_table = KEETUP_FIVESTAR_TABLE;
	
	return get_data_row("SELECT * FROM {$fivestar_table} WHERE id = {$id}", 'row_to_fivestar');
}

/**
 * Convert a database row to a new ElggAnnotation
 *
 * @param stdClass $row Db row result object
 *
 * @return ElggAnnotation
 * @access private
 */
function row_to_fivestar($row) {
	if (!($row instanceof stdClass)) {
		// @todo should throw in this case?
		return $row;
	}

	return new KeetupFivestarEntity($row);
}


/**
 * Deletes a keetup fivestar using its ID.
 *
 * @param int $id The annotation ID to delete.
 * @return bool
 */
function keetup_fivestar_delete_by_id($id) {
	$annotation = keetup_fivestar_get_from_id($id);
	if (!$annotation) {
		return FALSE;
	}
	return $annotation->delete();
}


/**
 * Creates a fivestar entity
 * 
 * @param integer $entity_guid
 * @param integer $value
 * @param integer $ip the ip2long 
 * @param integer $owner_guid the owner ID, optional
 * 
 * @return integer | boolean if success return the entity ID
 * 
 * @throws Exception
 */
function keetup_fivestar_create_entity($entity_guid, $value, $ip, $owner_guid) {
	$result = FALSE;
	
	$entity_guid = (int) $entity_guid;
	$value = (float) $value;
	
	$ip_long = (double) $ip;
	if (FALSE == $ip_long) {
		$ip_long = ip2long($ip);
	}

	$owner_guid = (int) $owner_guid;
	if ($owner_guid == 0) {
		$owner_guid = elgg_get_logged_in_user_guid();
	}
	
	$time = time();
	
	$entity = get_entity($entity_guid);
	
	if (!($entity instanceof ElggEntity)) {
		throw New Exception('The entity does not exists');
	}
	
	if (empty($value)) {
		throw New Exception('You cant vote empty');
	}
	
	/**
	 * @TODO:
	 *	Add validations when the user is trying to vote and there is an IP but not an owner_id (anonymous user)
	 */
	
	$fivestar_table = KEETUP_FIVESTAR_TABLE;
	
	if (elgg_trigger_event('keetup_fivestar', $entity->type, $entity)) {
		// If ok then add it
		$result = insert_data("INSERT into {$fivestar_table}
			(entity_guid, owner_guid, ip, value, time_created, time_updated) VALUES
			($entity_guid,$owner_guid, $ip_long, $value, $time, $time)");

		if ($result !== false) {
			$obj = keetup_fivestar_get_from_id($result);
			if (elgg_trigger_event('create', 'keetup_fivestar', $obj)) {
				return $result;
			} else {
				// plugin returned false to reject annotation
				keetup_fivestar_delete_by_id($result);
				return FALSE;
			}
		}
	}	
	
	return $result;
}

function keetup_fivestar_update_entity($id, $value, $ip, $owner_guid) {
	die("TODO: THIS FUNCTION");
}