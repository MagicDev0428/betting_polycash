<?php
include("../includes/connect.php");
include("../includes/get_session.php");

$game = new Game($app, intval($_REQUEST['game_id']));

$from_round_id = intval($_REQUEST['from_round_id']);

$last_block_id = $game->last_block_id();
$current_round = $game->block_to_round($last_block_id+1);

if ($from_round_id > 0 && $from_round_id < $current_round) {
	$rounds_complete = $game->rounds_complete_html($from_round_id, 20);
	echo json_encode($rounds_complete);
}
else {
	$output_obj[0] = 0;
	$output_obj[1] = "";
	echo json_encode($output_obj);
}
?>