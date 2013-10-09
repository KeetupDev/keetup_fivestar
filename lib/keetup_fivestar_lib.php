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
	 * 	Add validations when the user is trying to vote and there is an IP but not an owner_id (anonymous user)
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

/**
 * Updates a keetup fivestar 
 * 
 * @param integer $fivestar_id
 * @param float $value
 * @param integer $ip
 * @param integer $owner_guid
 * @return boolean
 */
function keetup_fivestar_update_entity($fivestar_id, $value, $ip, $owner_guid) {
	$fivestar_id = (int) $fivestar_id;
	$value = (float) $value;

	$ip_long = (double) $ip;
	if (FALSE == $ip_long) {
		$ip_long = ip2long($ip);
	}

	$owner_guid = (int) $owner_guid;
	if ($owner_guid == 0) {
		$owner_guid = elgg_get_logged_in_user_guid();
	}


	$fivestar_table = KEETUP_FIVESTAR_TABLE;
	$time = time();

	// If ok then add it
	$result = update_data("UPDATE {$fivestar_table} set
		value='{$value}', ip='{$ip}', owner_guid='{$owner_guid}', time_updated='{$time}'
		where id={$fivestar_id} ");

	if ($result !== FALSE) {
		$obj = keetup_fivestar_get_from_id($fivestar_id);
		elgg_trigger_event('update', 'keetup_fivestar', $obj);
	}

	return $result;
}

/**
 * Returns an array of fivestar with optional filtering.
 *
 *
 * @tip Plural arguments can be written as singular if only specifying a
 * single element.  ('id' => integer vs 'ids' => array(integer1, integer2)).
 *
 * @param array $options Array in format:
 * 'ids' => ELGG_ENTITIES_ANY_VALUE,
 * 'ips' => ELGG_ENTITIES_ANY_VALUE,
 * 'owner_guids' => ELGG_ENTITIES_ANY_VALUE,
 * 'values' => ELGG_ENTITIES_ANY_VALUE,
 * 'entity_guids' => ELGG_ENTITIES_ANY_VALUE,
 * 	order_by => NULL (time_created desc)|STR SQL order by clause
 *
 *  reverse_order_by => BOOL Reverse the default order by clause
 *
 * 	limit => NULL (10)|INT SQL limit clause (0 means no limit)
 *
 * 	offset => NULL (0)|INT SQL offset clause
 *
 * 	created_time_lower => NULL|INT Created time lower boundary in epoch time
 *
 * 	created_time_upper => NULL|INT Created time upper boundary in epoch time
 *
 * 	modified_time_lower => NULL|INT Modified time lower boundary in epoch time
 *
 * 	modified_time_upper => NULL|INT Modified time upper boundary in epoch time
 *
 * 	count => TRUE|FALSE return a count instead of entities
 *
 * 	wheres => array() Additional where clauses to AND together
 *
 * 	joins => array() Additional joins
 *
 * 	callback => string A callback function to pass each row through
 *
 * @return mixed If count, int. If not count, array. false on errors.
 * @since 1.7.0
 * @see elgg_get_entities_from_metadata()
 * @see elgg_get_entities_from_relationship()
 * @see elgg_get_entities_from_access_id()
 * @see elgg_get_entities_from_annotations()
 * @see elgg_list_entities()
 * @link http://docs.elgg.org/DataModel/Entities/Getters
 */
function keetup_fivestar_get_entities(array $options = array()) {

	$fivestar_table = KEETUP_FIVESTAR_TABLE;
	$tbl_prefix = 'fv';

	$defaults = array(
		'created_time_lower' => ELGG_ENTITIES_ANY_VALUE,
		'created_time_upper' => ELGG_ENTITIES_ANY_VALUE,
		'modified_time_upper' => ELGG_ENTITIES_ANY_VALUE,
		'modified_time_lower' => ELGG_ENTITIES_ANY_VALUE,
		'order_by' => 'fv.time_created DESC',
		'group_by' => ELGG_ENTITIES_ANY_VALUE,
		'limit' => 0,
		'offset' => 0,
		'count' => FALSE,
		'selects' => array(),
		'wheres' => array(),
		'joins' => array(),
		'callback' => 'row_to_fivestar',
		'return_query' => FALSE,
		'data_calculation' => ELGG_ENTITIES_NO_VALUE,
		'get_row' => FALSE,
		'ids' => ELGG_ENTITIES_ANY_VALUE,
		'ips' => ELGG_ENTITIES_ANY_VALUE,
		'owner_guids' => ELGG_ENTITIES_ANY_VALUE,
		'values' => ELGG_ENTITIES_ANY_VALUE,
		'entity_guids' => ELGG_ENTITIES_ANY_VALUE,
	);

	$options = array_merge($defaults, $options);

	$singulars = array(
		'id', 'select', 'where', 'join', 'ip', 'owner_guid', 'value', 'entity_guid',
	);

	$options = elgg_normalise_plural_options_array($options, $singulars);

	if (!is_array($options['wheres'])) {
		$options['wheres'] = array($options['wheres']);
	}

	$wheres = $options['wheres'];

	$wheres[] = elgg_get_guid_based_where_sql('fv.id', $options['ids']);
	$wheres[] = elgg_get_guid_based_where_sql('fv.ip', $options['ips']);
	$wheres[] = elgg_get_guid_based_where_sql('fv.owner_guid', $options['owner_guids']);
	$wheres[] = elgg_get_guid_based_where_sql('fv.value', $options['values']);
	$wheres[] = elgg_get_guid_based_where_sql('fv.entity_guid', $options['entity_guids']);

	$wheres[] = elgg_get_entity_time_where_sql('fv', $options['created_time_upper'], $options['created_time_lower'], $options['modified_time_upper'], $options['modified_time_lower']);


	// see if any functions failed
	// remove empty strings on successful functions
	foreach ($wheres as $i => $where) {
		if ($where === FALSE) {
			return FALSE;
		} elseif (empty($where)) {
			unset($wheres[$i]);
		}
	}

	// remove identical where clauses
	$wheres = array_unique($wheres);

	// evaluate join clauses
	if (!is_array($options['joins'])) {
		$options['joins'] = array($options['joins']);
	}

	// remove identical join clauses
	$joins = array_unique($options['joins']);

	foreach ($joins as $i => $join) {
		if ($join === FALSE) {
			return FALSE;
		} elseif (empty($join)) {
			unset($joins[$i]);
		}
	}


	if (!$options['count']) {
		if ($options['data_calculation'] === ELGG_ENTITIES_NO_VALUE) {
			//Normal query
			if ($options['selects']) {
				$selects = implode(',', $options['selects']);
			} else {
				$selects = '';
			}

			$query = "SELECT DISTINCT fv.*{$selects} FROM {$fivestar_table} fv ";
		} else {
			$query = "SELECT {$options['data_calculation']}(fv.value) as calculation FROM {$fivestar_table} fv ";
		}
	} else {
		$query = "SELECT count(DISTINCT e.guid) as total FROM {$fivestar_table} fv ";
	}

	// add joins
	foreach ($joins as $j) {
		$query .= " $j ";
	}
	
	// add wheres
	$query .= ' WHERE ';

	$query .= implode(' AND', $wheres);
//	foreach ($wheres as $w) {
//		$query .= " $w AND ";
//	}
// reverse order by
	if ($options['reverse_order_by']) {
		$options['order_by'] = elgg_sql_reverse_order_by_clause($options['order_by']);
	}


	if (!$options['count'] && $options['data_calculation'] === ELGG_ENTITIES_NO_VALUE) {
		if ($options['group_by']) {
			$query .= " GROUP BY {$options['group_by']}";
		}

		if ($options['order_by']) {
			$query .= " ORDER BY {$options['order_by']}";
		}

		if ($options['limit']) {
			$limit = sanitise_int($options['limit'], false);
			$offset = sanitise_int($options['offset'], false);
			$query .= " LIMIT $offset, $limit";
		}
		
		if ($options['get_row']) {
			$dt = get_data_row($query, $options['callback']);
		} else {
			$dt = get_data($query, $options['callback']);
		}
		return $dt;
	} else {
		$result = get_data_row($query);

		if ($options['data_calculation'] === ELGG_ENTITIES_NO_VALUE) {
			return (int) $result->total;
		} else {
			return $result->calculation;
		}
	}
}