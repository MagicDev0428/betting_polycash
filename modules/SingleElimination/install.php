<?php
include(dirname(dirname(dirname(__FILE__)))."/includes/connect.php");
include_once(dirname(__FILE__)."/SingleEliminationGameDefinition.php");

if (!empty($argv)) {
	$cmd_vars = $app->argv_to_array($argv);
	if (!empty($cmd_vars['key'])) $_REQUEST['key'] = $cmd_vars['key'];
	else if (!empty($cmd_vars[0])) $_REQUEST['key'] = $cmd_vars[0];
}

if (empty($GLOBALS['cron_key_string']) || $_REQUEST['key'] == $GLOBALS['cron_key_string']) {
	$module = $app->check_set_module("SingleElimination");

	$db_game = false;
	
	$q = "SELECT * FROM games WHERE module=".$app->quote_escape($module['module_name']).";";
	$r = $app->run_query($q);
	
	if ($r->rowCount() > 0) $db_game = $r->fetch();
	
	$game_def = new SingleEliminationGameDefinition($app);
	$new_game_def_txt = $app->game_def_to_text($game_def->game_def);
	
	$error_message = false;
	$new_game = $app->create_game_from_definition($new_game_def_txt, $thisuser, "SingleElimination", $error_message, $db_game);
	
	if ($error_message) echo $error_message."<br/>\n";
	echo "Next please <a href=\"/scripts/reset_game.php?key=".$GLOBALS['cron_key_string']."&game_id=".$new_game->db_game['game_id']."\">reset this game</a><br/>\n";
	
	echo "Done!!<br/>\n";
}
else echo "Please supply the correct key.<br/>\n";
?>