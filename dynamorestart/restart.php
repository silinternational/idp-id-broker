<?php
require_once('DynamoRestart.php');

$dynInit = new DynamoRestart();
$dynInit->init();
$dynInit->createTables();
$dynInit->initApiKeys();
$dynInit->initWebauthnEntries();
$dynInit->verifyData();