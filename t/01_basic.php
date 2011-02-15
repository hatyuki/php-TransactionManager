#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'TransactionManager.php';
require_once 'Utils.php';


function do_basic_transaction ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

    $tm->txn_begin( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (1, 'baz')");
    $tm->txn_commit( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->is($row['id'], 1);
    $t->is($row['var'], 'baz');
}

function do_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

    $tm->txn_begin( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (2, 'bal')");
    $tm->txn_rollback( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function in_transactin ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

    $t->ok( !$tm->in_transaction( ) );

    $tm->txn_begin( );
    $t->ok( $tm->in_transaction( ) );
    $tm->txn_commit( );

    $t->ok( !$tm->in_transaction( ) );
}

done_testing( );
