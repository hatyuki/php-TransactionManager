#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/../lib/setup.php';
require_once 'TransactionManager.php';
require_once 'Utils.php';

if ( !isset($_SERVER['PG_DSN']) || !isset($_SERVER['PG_USER']) ) {
    $t->skip( );
    return;
}

$pdo = new PDO($_SERVER['PG_DSN'], $_SERVER['PG_USER'], @$_SERVER['PG_PASS']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->query("
    CREATE TABLE job (
        id   SERIAL PRIMARY KEY,
        func TEXT NOT NULL
    )
");

function pg ($t)
{
    global $pdo;
    $tm   = new TransactionManager($pdo);
    $txn1 = $tm->txn_scope( );
    $pdo->query("INSERT INTO job (func) VALUES ('baz')");
    $txn2 = $tm->txn_scope( );
    $pdo->query("INSERT INTO job (func) VALUES ('bab')");
    $txn2->commit( );
    $txn1->commit( );

    $stmt = $pdo->query('SELECT * FROM job');
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->is(sizeof($rows), 2);
    $stmt->closeCursor( );
}

function destroy ( )
{
    global $pdo;
    $pdo->query('DROP TABLE job');
}

done_testing( );
