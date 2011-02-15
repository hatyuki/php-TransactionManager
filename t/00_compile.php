#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
$dummy = null;

$t->include_ok('TransactionManager.php');
$t->ok(new TransactionManager($dummy));
