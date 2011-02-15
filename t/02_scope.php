#!/usr/bin/env php
<?php
require_once dirname(__FILE__).'/lib/setup.php';
require_once 'TransactionManager.php';
require_once 'Utils.php';


function do_scope_commit ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

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
    $tm  = new TransactionManager($pdo);

    $txn = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (2, 'boo')");
    $txn->rollback( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function do_scope_guard_for_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

    $txn = $tm->txn_scope( );
    $pdo->query("INSERT INTO foo (id, var) VALUES (3, 'bebe')");

    set_error_handler('_nop_handler');
    $txn = null;
    restore_error_handler( );

    $stmt = $row = $pdo->query('SELECT * FROM foo');
    $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );
}

function _nop_handler ($errno, $errstr) { return true; }

function do_nested_scope_rollback_rollback ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

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
    $tm  = new TransactionManager($pdo);

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
    $tm  = new TransactionManager($pdo);

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
        $t->is($e->getCode( ), TransactionManagerException::NESTED_TRANSACTION_ERROR);

        set_error_handler('_nop_handler');
        $txn1 = null;  ## run __destroy( )
        restore_error_handler( );

        $stmt = $pdo->query('SELECT * FROM foo');
        $t->ok( !$stmt->fetch(PDO::FETCH_ASSOC) );

        return;
    }

    $t->fail( );
}

function do_nested_scope_commit_commit ($t)
{
    $pdo = Utils::setup( );
    $tm  = new TransactionManager($pdo);

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

function do_automatic_rollback ($t)
{
    $pdo  = Utils::setup( );
    $tm   = new TransactionManager($pdo);

    $txn = $tm->txn_scope( );

    set_error_handler('_error_handler');
    $txn = null;
    restore_error_handler( );
}

function _error_handler ($errno, $errstr)
{
    global $t;

    $t->like(
        $errstr,
        '/Transaction was aborted without calling an explicit commit or rollback at .*02_scope.php line 144. \(Guard created at .*02_scope.php line 141\)/'
    );

    return true;
}

function pass_arbitrary_caller_info ($t)
{
    $pdo    = Utils::setup( );
    $tm     = new TransactionManager($pdo);
    $caller = array('file' => 'hoge.php', 'line' => 1);

    $txn = $tm->txn_scope($caller);

    set_error_handler('_error_handler2');
    $txn = null;
    restore_error_handler( );
}

function _error_handler2 ($errno, $errstr)
{
    global $t;

    $t->like(
        $errstr,
        '/Transaction was aborted without calling an explicit commit or rollback at .*02_scope.php line 169. \(Guard created at hoge.php line 1\)/'
    );

    return true;
}

done_testing( );
