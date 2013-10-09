<?php

/**
 * Save Keetup Fivestar settings
 *
 */

// Params array (text boxes and drop downs)
$params = get_input('params', array());

foreach ($params as $k => $v) {	
    if (!elgg_set_plugin_setting($k, $v, 'keetup_fivestar')) {
        register_error(sprintf(elgg_echo('plugins:settings:save:fail'), 'keetup_fivestar'));
        forward(REFERER);
    }
}

//$change_vote = (int)get_input('change_vote');
//if ($change_vote == 0) {
//    elgg_set_plugin_setting('change_cancel', 0, 'keetup_fivestar');
//} else {
//    elgg_set_plugin_setting('change_cancel', 1, 'keetup_fivestar');
//}

$keetup_fivestar_view = '';

$values = get_input('keetup_fivestar_views');

if (is_array($values)) {
    foreach ($values as $value) {
       $keetup_fivestar_view .= $value . "\n";
    }
}

elgg_set_plugin_setting('keetup_fivestar_view', $keetup_fivestar_view, 'keetup_fivestar');

system_message(elgg_echo('keetup_fivestar:settings:save:ok'));

forward(REFERER);
