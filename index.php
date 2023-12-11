<?php

// must load classes before starting the session
// in order to deserialize classes correctly
// https://stackoverflow.com/questions/2010427/php-php-incomplete-class-object-with-my-session-data
require_once "classes/ChowChooserEngine.php";

// session is where login info persists
session_start();

$KEY_SALT = "This is a salt for our key generator";

$order = new ChowChooserEngine();
?>
