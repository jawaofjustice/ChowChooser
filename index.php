<?php

// session is where login info persists
session_start();

$KEY_SALT = "This is a salt for our key generator";

require_once "classes/ChowChooserEngine.php";

$order = new ChowChooserEngine();
?>
