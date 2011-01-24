#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'Kirin/TransactionManager.php';
require_once 'Utils.php';


function do_scope_commit ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (1, 'baz')");
    $txn->commit( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->is($row['id'], 1);
    $t->is($row['var'], 'baz');
}

function do_scope_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (2, 'boo')");
    $txn->rollback( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function do_scope_guard_for_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    _do_rollback_auto($pdo, $tm);

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function _do_rollback_auto ($pdo, $tm)
{
    $txn = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (3, 'bebe')");
}

function do_nested_scope_rollback_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn1 = $tm->txn_scope( );
    $txn2 = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (4, 'kumu')");
    $txn2->rollback( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (5, 'kaka')");
    $txn1->rollback( );

    $stmt = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function do_nested_scope_commit_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn1 = $tm->txn_scope( );
    $txn2 = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (6, 'miki')");
    $txn2->commit( );
    $stmt = $pdo->query('SELECT * FROM foo');
    $t->ok( $stmt->fetch(PDO::FETCH_ASSOC) );
    $stmt->closeCursor( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (7, 'mesi')");
    $txn1->rollback( );

    $stmt = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function do_nested_scope_rollback_commit ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn1 = $tm->txn_scope( );
    $txn2 = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (8, 'uso')");
    $txn2->rollback( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (9, 'nani')");
    try {
        $txn1->commit( );
    }
    catch (Exception $e) {
        $t->is($e->getMessage( ), 'tried to commit but already rollbacked in nested transaction.');
        $t->is($e->getCode( ), KirinTMException::NESTED_TRANSACTION_ERROR);
        $txn1 = null;  ## run __destroy( )
    }

    $stmt = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function do_nested_scope_commit_commit ($t)
{
    $pdo = Utils::setup( );
    $tm  = new KirinTransactionManager($pdo);

    $txn1 = $tm->txn_scope( );
    $txn2 = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (10, 'ahi')");
    $txn2->commit( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (11, 'uhe')");
    $txn1->commit( );

    $stmt = $pdo->query('SELECT * FROM foo');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $t->is(sizeof($rows), 2);
}

done_testing( );
