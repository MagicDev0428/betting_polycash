<?php
$uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode("/", $uri);

if ($uri_parts[1] == "reset_password") {
	include("reset_password.php");
}
else if ($uri_parts[1] == "wallet") {
	include("wallet.php");
}

?>