#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
$dummy = null;

$t->include_ok('Kirin/TransactionManager.php');
$t->ok(new KirinTransactionManager($dummy));
