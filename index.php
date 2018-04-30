<?php

require_once 'LogDisplayer.php';

$logDisplayer = new LogDisplayer($_GET);
$logDisplayer->preProcesso();
$logDisplayer->processaLog();
$logDisplayer->display();
