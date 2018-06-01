<?php

if (current_user_can('administrator')) {
	echo phpinfo();
	die();
}
?>

<h1> Please Do login as Admin</h1>
