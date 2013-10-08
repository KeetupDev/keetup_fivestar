<?php

if (!function_exists('str_get_html')) {
    include dirname(__FILE__) . "/lib/simple_html_dom.php";
}

elgg_register_event_handler('init','system','keetup_fivestar_init');

function keetup_fivestar_init() {

    keetup_fivestar_settings();

    $css_rating = elgg_get_simplecache_url('css', 'basic');
    elgg_register_simplecache_view('css/basic');
    elgg_register_css('fivestar_css', $css_rating);

    $js_rating = elgg_get_simplecache_url('js', 'keetup_fivestar/ui.stars.min');
    elgg_register_simplecache_view('js/keetup_fivestar/ui.stars.min');
    elgg_register_js('fivestar', $js_rating);

//    elgg_register_plugin_hook_handler('view', 'all', 'keetup_fivestar_view');

    elgg_register_admin_menu_item('administer', 'keetup_fivestar', 'administer_utilities');

    // Register actions
    $base_dir = elgg_get_plugins_path() . 'keetup_fivestar/actions';
    elgg_register_action("keetup_fivestar/rate", "$base_dir/rate.php", 'logged_in');
    elgg_register_action("keetup_fivestar/settings", "$base_dir/settings.php", 'admin');
    elgg_register_action("keetup_fivestar/reset", "$base_dir/reset.php", 'admin');
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
 * Handles voting on an entity
 *
 * @param  integer  $guid  The entity guid being voted on
 * @param  integer  $vote The vote
 * @return string   A status message to be returned to the client
 */
function keetup_fivestar_vote($guid, $vote) {

    $entity = get_entity($guid);
    $annotation_owner = elgg_get_logged_in_user_guid();

    $msg = null;

    if ($annotation = elgg_get_annotations(array('guid' => $entity->guid,
                                                 'type' => $entity->type,
                                                 'annotation_name' => 'fivestar',
                                                 'annotation_owner_guid' => $annotation_owner,
                                                 'limit' => 1))) {
        if ($vote == 0 && (int)elgg_get_plugin_setting('change_cancel')) {
            if (!elgg_trigger_plugin_hook('keetup_fivestar:cancel', 'all', array('entity' => $entity), false)) {
                elgg_delete_annotations(array('annotation_id' => $annotation[0]->id));
                $msg = elgg_echo('keetup_fivestar:deleted');
            }
        } else if (elgg_get_plugin_setting('change_cancel')) {
            update_annotation($annotation[0]->id, 'fivestar', $vote, 'integer', $annotation_owner, 2);
            $msg = elgg_echo('keetup_fivestar:updated');
        } else {
            $msg = elgg_echo('keetup_fivestar:nodups');
        }
    } else if ($vote > 0) {
        if (!elgg_trigger_plugin_hook('keetup_fivestar:vote', 'all', array('entity' => $entity), false)) {
            $entity->annotate('fivestar', $vote, 2, $annotation_owner);
        }
    } else {
        $msg = elgg_echo('keetup_fivestar:novote');
    }

    keetup_fivestar_setRating($entity);

    return($msg);
}

/**
 * Set the current rating for an entity
 *
 * @param  object   $entity  The entity to set the rating on
 * @return array    Includes the current rating and number of votes
 */
function keetup_fivestar_setRating($entity) {

    $access = elgg_set_ignore_access(true);

    $rating = keetup_fivestar_getRating($entity->guid);
    $entity->keetup_fivestar_rating = $rating['rating'];

    elgg_set_ignore_access($access);

    return;
}

/**
 * Get an the current rating for an entity
 *
 * @param  integer  $guid  The entity guid being voted on
 * @return array    Includes the current rating and number of votes
 */
function keetup_fivestar_getRating($guid) {

    $rating = array('rating' => 0, 'votes' => 0);
    $entity = get_entity($guid);

    if (count($entity->getAnnotations('fivestar', false))) {
        $rating['rating'] = $entity->getAnnotationsAvg('fivestar');
        $rating['votes'] = count($entity->getAnnotations('fivestar', false));

        $modifier = 100 / (int)elgg_get_plugin_setting('stars');
        $rating['rating'] = round($rating['rating'] / $modifier, 1);
    }

    return($rating);
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
 * Checks to see if the current user has already voted on the entity
 *
 * @param  guid   The entity guid
 * @return bool   Returns true/false
 */
function keetup_fivestar_hasVoted($guid) {

    $entity = get_entity($guid);
    $annotation_owner = elgg_get_logged_in_user_guid();

    $annotation = elgg_get_annotations(array('guids' => $entity->guid,
                                             'types' => $entity->type,
                                             'annotation_name' => 'fivestar',
                                             'annotation_owner_guid' => $annotation_owner,
                                             'limit' => 1));

    if (is_object($annotation[0])) {
        return(true);
    }

    return(false);
}

/**
 * Set default settings
 *
 */
function keetup_fivestar_settings() {
    // Set plugin defaults
    if (!(int)elgg_get_plugin_setting('stars', 'keetup_fivestar')) {
        elgg_set_plugin_setting('stars', '5', 'keetup_fivestar');
    }
    $change_vote = (int)elgg_get_plugin_setting('change_vote', 'keetup_fivestar');
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
