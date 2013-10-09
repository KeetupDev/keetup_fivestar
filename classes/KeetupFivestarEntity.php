<?php

/**
 * This class handle the votes entity
 *
 * @author Bortoli German

 * @property string $type         fivestar or metadata (read-only after save)
 * 
 * @property int    $id           The unique identifier (read-only)
 * @property int    $entity_guid  The GUID of the entity that this extender describes
 * @property int    $owner_guid	  The GUID of the registered user that perform action
 * @property float  $value        The value of the extender (int or string)
 * @property int	$ip			  The IP of the client user
 * @property int    $time_created A UNIX timestamp of when the extender was created (read-only, set on first save)
 * @property int    $time_updated A UNIX timestamp of when the extender was updated (read-only, set on first save)
 */
class KeetupFivestarEntity extends ElggExtender {

	/**
	 * Initializez the default attributes
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['type'] = 'fivestar';
		
		$this->attributes['time_updated'] = NULL;
		$this->attributes['owner_guid'] = 0;
		$this->attributes['ip'] = $this->getClientIp2Long();
	}

	/**
	 * Construct a new keetup fivestar object
	 *
	 * @param mixed $id The fivestar ID or a database row as stdClass object
	 */
	function __construct($id = NULL) {

		$this->initializeAttributes();

		if (!empty($id)) {
			// Create from db row
			if ($id instanceof stdClass) {
				$fivestar = $id;

				$objarray = (array) $fivestar;
				foreach ($objarray as $key => $value) {
					$this->attributes[$key] = $value;
				}
			} else {
				// get an KeetupFivestarEntity object and copy its attributes
				$fivestar = $this->getObjectFromID($id);

				$this->attributes = $fivestar->attributes;
			}
		}
	}

	/**
	 * Delete the fivestar
	 */
	
	public function delete() {
		$fivestar_table = KEETUP_FIVESTAR_TABLE;
		
		$id = $this->id;
		if ($id && $this->canEdit()) {
			$query = "DELETE FROM {$fivestar_table} WHERE id = {$id}";
			return delete_data($query);
		}
		
		return FALSE;
	}
	
	
	public function canEdit($user_guid = 0, $current_ip = 0) {
		
		return TRUE;
		
		/**
		 * @TODO: VALIDATES IF THE USER IP IS THE SAME AS THE CURRENT ONE
		 * @TODO: VALIDATES IF THE USER HAS OWNER GUID AND IS THE SAME AS THE CURRENT ONE
		 */
	}

	/**
	 * For a given ID, return the object associated with it.
	 * This is used by the river functionality primarily.
	 * This is useful for checking access permissions etc on objects.
	 *
	 * @param int $id GUID of an entity
	 *
	 * @return ElggEntity
	 */
	
	public function getObjectFromID($id) {
		return keetup_fivestar_get_from_id($id);
	}

	
	/**
	 * Get a URL for this object
	 *
	 * @return string
	 */
	
	public function getURL() {
		return NULL;
	}

	/**
	 * Save the fivestar entity
	 * @return integer
	 * 
	 * @throws IOException
	 */
	public function save() {
		
		if ($this->id > 0) {
			return keetup_fivestar_update_entity($this->id, $this->value, $this->ip, $this->owner_guid);
		} else {
			$this->id = keetup_fivestar_create_entity($this->entity_guid, $this->value, $this->ip, $this->owner_guid);

			if (!$this->id) {
				throw new IOException(elgg_echo('IOException:UnableToSaveNew', array(get_class())));
			}
			
			return $this->id;
		}
	}

	/**
	 * Get the real client IP
	 * 
	 * @return string
	 */
	public function getClientIp() {
		return keetup_fivestar_get_client_ip();
	}
	
	/**
	 * Get the client IP 2 Long
	 * 
	 * @return integer
	 */
	
	public function getClientIp2Long() {
		return ip2long($this->getClientIp());
	}

	
	/**
	 * Returns an attribute
	 *
	 * @param string $name Name
	 *
	 * @return mixed
	 */
	protected function get($name) {
		if (array_key_exists($name, $this->attributes)) {
			// Sanitise value if necessary
			
			switch($name) {
				case 'value':
					return (float) $this->attributes['value'];
				break;
			}

			return $this->attributes[$name];
		}
		return null;
	}
	
	
	/**
	 * Returns the IP as string
	 * 
	 * @return string
	 */
	public function getFriendlyIP() {
		$ip = $this->ip;
		return long2ip($ip);
	}
}