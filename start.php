<?php

define('KEETUP_FIVESTAR_PATH', dirname(__FILE__).'/');
define('KEETUP_FIVESTAR_TABLE', elgg_get_config('dbprefix').'fivestar');

include dirname(__FILE__) . "/lib/keetup_fivestar_lib.php";

if (!function_exists('str_get_html')) {
	include dirname(__FILE__) . "/lib/simple_html_dom.php";
}

elgg_register_event_handler('init', 'system', 'keetup_fivestar_init');

function keetup_fivestar_init() {

//	keetup_fivestar_settings();

	$css_rating = elgg_get_simplecache_url('css', 'basic');
	elgg_register_simplecache_view('css/basic');
	elgg_register_css('fivestar_css', $css_rating);

	$js_rating = elgg_get_simplecache_url('js', 'keetup_fivestar/ui.stars.min');
	elgg_register_simplecache_view('js/keetup_fivestar/ui.stars.min');
	elgg_register_js('fivestar', $js_rating);

	//Keetup disabled this at the moment, disable to add all views support
//    elgg_register_plugin_hook_handler('view', 'all', 'keetup_fivestar_view');

	elgg_register_admin_menu_item('administer', 'keetup_fivestar', 'administer_utilities');

	// Register actions
	$base_dir = elgg_get_plugins_path() . 'keetup_fivestar/actions';
	elgg_register_action("keetup_fivestar/rate", "{$base_dir}/rate.php", 'public');
	elgg_register_action("keetup_fivestar/settings", "{$base_dir}/settings.php", 'admin');
	elgg_register_action("keetup_fivestar/reset", "{$base_dir}/reset.php", 'admin');
	
	
}

/**
 * This method is called when the view plugin hook is triggered.
 * If a matching view config is found then the fivestar widget is
 * called.
 *
 * @param  integer  $hook The hook being called.
 * @param  integer  $type The type of entity you're being called on.
 * @param  string   $return The return value.
 * @param  array    $params An array of parameters for the current view
 * @return string   The html
 */
function keetup_fivestar_view($hook, $entity_type, $returnvalue, $params) {

	$lines = explode("\n", elgg_get_plugin_setting('keetup_fivestar_view'));
	foreach ($lines as $line) {
		$options = array();
		$parms = explode(",", $line);
		foreach ($parms as $parameter) {
			preg_match("/^(\S+)=(.*)$/", trim($parameter), $match);
			$options[$match[1]] = $match[2];
		}

		if ($options['keetup_fivestar_view'] == $params['view']) {
			list($status, $html) = keetup_fivestar_widget($returnvalue, $params, $options);
			if (!$status) {
				continue;
			} else {
				return($html);
			}
		}
	}
}

/**
 * Inserts the fivestar widget into the current view
 *
 * @param  string   $returnvalue  The original html
 * @param  array    $params  An array of parameters for the current view
 * @param  array    $guid  The fivestar view configuration
 * @return string   The original view or the view with the fivestar widget inserted
 */
function keetup_fivestar_widget($returnvalue, $params, $options) {

	$guid = $params['vars']['entity']->guid;

	if (!$guid) {
		return;
	}

	if (elgg_in_context('widgets')) {
		$widget = elgg_view("keetup_fivestar/voting", array('fivestar_guid' => $guid, 'min' => true));
	} else {
		$widget = elgg_view("keetup_fivestar/voting", array('fivestar_guid' => $guid));
	}

	// get the DOM
	$html = str_get_html($returnvalue);

	$match = 0;
	foreach ($html->find($options['tag']) as $element) {
		if ($element->$options['attribute'] == $options['attribute_value']) {
			$element->innertext .= $options['before_html'] . $widget . $options['after_html'];
			$match = 1;
			break;
		}
	}

	$returnvalue = $html;
	return(array($match, $returnvalue));
}

/**
 * Set default settings
 *
 */
function keetup_fivestar_settings() {
	// Set plugin defaults
	if (!(int) elgg_get_plugin_setting('stars', 'keetup_fivestar')) {
		elgg_set_plugin_setting('stars', '5', 'keetup_fivestar');
	}
	
	$change_vote = (int) elgg_get_plugin_setting('change_vote', 'keetup_fivestar');
	
	if ($change_vote == 0) {
		elgg_set_plugin_setting('change_cancel', 0, 'keetup_fivestar');
	} else {
		elgg_set_plugin_setting('change_cancel', 1, 'keetup_fivestar');
	}
}

function keetup_fivestar_defaults() {

	$keetup_fivestar_view = 'keetup_fivestar_view=object/blog, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=object/file, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=object/bookmarks, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=object/page_top, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=object/thewire, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=group/default, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br>
keetup_fivestar_view=object/groupforumtopic, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=icon/user/default, tag=div, attribute=class, attribute_value=elgg-avatar elgg-avatar-large, before_html=<br>
keetup_fivestar_view=object/album, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />
keetup_fivestar_view=object/image, tag=div, attribute=class, attribute_value=elgg-subtext, before_html=<br />';

	elgg_set_plugin_setting('keetup_fivestar_view', $keetup_fivestar_view);
}

/**
 * Creates the base table for fivestars
 * 
 * @return boolean
 */
function keetup_fivestar_create_db() {
	$schema_file = KEETUP_FIVESTAR_PATH . 'schema/fivestar.sql';
	
	$sql = 'show tables like "'.KEETUP_FIVESTAR_TABLE.'"';

	try {
		$data = get_data($sql);
	} catch (Exception $exc) {
		$data = FALSE;
	}

	$success = FALSE;
	if (empty($data)) {
		try {
			$success = run_sql_script($schema_file);
		} catch (Exception $exc) {}
	}

	return $success;
}