<?php

$guid = (int)get_input('id');
$vote = (int)get_input('vote');

if (!$vote && $rate = (int)get_input('rate_avg')) {
    $vote = $rate;
}

$msg = keetup_fivestar_vote($guid, $vote);

// Get the new rating
$rating = keetup_fivestar_getRating($guid);

$rating['msg'] = $msg;

if (!(int)get_input('vote') && (int)get_input('rate_avg')) {
    system_message(elgg_echo("keetup_fivestar:rating_saved"));
    forward(REFERER);
} else {
    header('Content-type: application/json');
    echo json_encode($rating);
    exit();
}
exit();