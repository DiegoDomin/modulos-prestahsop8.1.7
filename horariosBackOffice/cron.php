<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
include(dirname(__FILE__) . '/horariosBackOffice.php');

$module = new horariosBackOffice();
$module->processReminders();
