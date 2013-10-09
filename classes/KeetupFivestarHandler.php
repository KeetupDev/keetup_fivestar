<?php

/**
 * Handle all the fivestar votes
 *
 * @author Bortoli German
 */
class KeetupFivestarHandler {

	protected $settings = array();

	public function __construct() {
		$plugin = elgg_get_plugin_from_id('keetup_fivestar');

		$settings = array(
			'change_cancel' => (int) $plugin->change_vote,
			'change_vote' => $plugin->change_vote,
			'stars' => (int) $plugin->stars,
			'keetup_fivestar_view' => $plugin->keetup_fivestar_view,
		);

		$this->settings = $settings;
	}

	/* Handles voting on an entity
	 *
	 * @param  integer  $guid  The entity guid being voted on
	 * @param  integer  $vote The vote
	 * @return string   A status message to be returned to the client
	 * 
	 */

	public function doVote($guid, $vote) {
		$entity = get_entity($guid);

		if (!($entity instanceof ElggEntity)) {
			throw new Exception('Is not an entity');
		}

		$fivestar_owner = elgg_get_logged_in_user_guid();

		$ip = keetup_fivestar_get_client_ip();
		$ip_long = ip2long($ip);

		$msg = NULL;

		$filter_options = array(
			'owner_guid' => $fivestar_owner,
			'ip' => $ip_long,
			'limit' => 1,
			'entity_guid' => $entity->getGUID(),
			'get_row' => TRUE,
		);

		$fivestar = keetup_fivestar_get_entities($filter_options);

		if ($fivestar) {
			if ($vote == 0 && $this->isChangeCancel()) {
				if (!elgg_trigger_plugin_hook('keetup_fivestar:cancel', 'all', array('entity' => $entity), FALSE)) {

					$fivestar->delete();

					$msg = elgg_echo('keetup_fivestar:deleted');
				}
			} else if ($this->isChangeCancel()) {

				$fivestar->value = $vote;
				$fivestar->save();

				$msg = elgg_echo('keetup_fivestar:updated');
			} else {
				$msg = elgg_echo('keetup_fivestar:nodups');
			}
		} else if ($vote > 0) {
			if (!elgg_trigger_plugin_hook('keetup_fivestar:vote', 'all', array('entity' => $entity), false)) {
				keetup_fivestar_create_entity($entity->getGUID(), $vote, $ip_long, $fivestar_owner);
			}
		} else {
			$msg = elgg_echo('keetup_fivestar:novote');
		}

		$this->setRatingToEntity($entity);

		return($msg);
	}

	public function getRating($guid) {
		$rating = array('rating' => 0, 'votes' => 0);
		$entity = get_entity($guid);

		if (!($entity instanceof ElggEntity)) {
			return FALSE;
		}

		$filter_options = array(
			'entity_guid' => $entity->getGUID(),
			'limit' => 0,
		);

		$votes_count = keetup_fivestar_get_entities(array_merge($filter_options, array('count' => TRUE)));
		$votes_avg = keetup_fivestar_get_entities(array_merge($filter_options, array('data_calculation' => 'AVG')));

		if ($votes_count) {
			$rating['votes'] = $votes_count;
			$modifier = $this->getModifier();
			$rating['rating'] = round($votes_avg / $modifier, 1);
		}

		return($rating);
	}

	public function isChangeCancel() {
		$change_cancel = $this->settings['change_cancel'];

		return $change_cancel;
	}

	public function getStarsSetting() {
		$stars = $this->settings['stars'];

		return $stars;
	}

	public function getModifier() {
		$stars = $this->getStarsSetting();

		if ($stars == 0) {
			return 20;
		}

		$modifier = 100 / $stars;

		return $modifier;
	}

	/**
	 * Set the current rating for an entity
	 *
	 * @param  object   $entity  The entity to set the rating on
	 * @return array    Includes the current rating and number of votes
	 */
	public function setRatingToEntity($entity) {
		
		if (!($entity instanceof ElggEntity)) {
			return FALSE;
		}
		
		$access = elgg_set_ignore_access(TRUE);

		$rating = $this->getRating($entity->guid);
		$value = $rating['rating'];

		$metadata = elgg_get_metadata(array(
				'guid' => $entity->getGUID(),
				'metadata_name' => 'keetup_fivestar_rating',
				'limit' => 1,
			));
		
		$rating_value = FALSE;
		if ($metadata) {
			$metadata = $metadata[0];
			$rating_value = $metadata->value;
		}

		if ($metadata) {
			//update
			update_metadata($metadata->id, 'keetup_fivestar_rating', $value, 'text', $entity->getOwnerGUID(), ACCESS_PUBLIC);
		} else {
			//create
			create_metadata($entity->getGUID(), 'keetup_fivestar_rating', $value, 'text', $entity->getOwnerGUID(), ACCESS_PUBLIC, FALSE);
		}

		elgg_set_ignore_access($access);

		return;
	}

	public function hasVoted($entity_guid) {
		$entity = get_entity($entity_guid);

		if (!($entity instanceof ElggEntity)) {
			throw new Exception('Is not an entity');
		}

		$fivestar_owner = elgg_get_logged_in_user_guid();

		$ip = keetup_fivestar_get_client_ip();
		$ip_long = ip2long($ip);

		$filter_options = array(
			'owner_guid' => $fivestar_owner,
			'ip' => $ip_long,
			'entity_guid' => $entity->getGUID(),
			'count' => TRUE,
		);

		$voted = keetup_fivestar_get_entities($filter_options);

		return (bool) $voted;
	}

}

