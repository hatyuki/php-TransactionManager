<?php

class TransactionManager
{
    const VERSION = '0.001';

    protected $dbh = null;
    protected $active_transaction = 0;
    protected $rollbacked_in_nested_transaction = false;

    function __construct ($dbh)
    {
        $this->dbh = $dbh;
    }

    function txn_scope ( )
    {
        return new TransactionManagerScopeGurd($this);
    }

    function txn_begin ( )
    {
        if (++$this->active_transaction > 1) {
            return null;
        }

        $this->dbh->beginTransaction( );
    }

    function txn_rollback ( )
    {
        if ($this->active_transaction == 1) {
            $this->dbh->rollBack( );
            $this->txn_end( );
        }
        else if ($this->active_transaction > 1) {
            $this->active_transaction--;
            $this->rollbacked_in_nested_transaction = true;
        }
    }

    function txn_commit ( )
    {
        if ( !$this->active_transaction ) {
            return null;
        }

        if ($this->rollbacked_in_nested_transaction) {
            throw new TransactionManagerException(
                'tried to commit but already rollbacked in nested transaction.',
                TransactionManagerException::NESTED_TRANSACTION_ERROR
            );
        }
        else if ($this->active_transaction > 1) {
            $this->active_transaction--;
            return null;
        }

        $this->dbh->commit( );
        $this->txn_end( );
    }

    function txn_end ( )
    {
        $this->active_transaction = 0;
        $this->rollbacked_in_nested_transaction = false;
    }

    function in_transaction ( )
    {
        return $this->active_transaction ? true : false;
    }
}


class TransactionManagerScopeGurd
{
    protected $dismiss = false;
    protected $object  = null;

    function __construct ($obj)
    {
        $obj->txn_begin( );
        $this->object = $obj;
    }

    function rollback ( )
    {
        if ($this->dismiss) { return null; }

        $this->object->txn_rollback( );
        $this->dismiss = true;
    }

    function commit ( )
    {
        if ($this->dismiss) { return null; }

        $this->object->txn_commit( );
        $this->dismiss = true;
    }

    function __destruct ( )
    {
        if ($this->dismiss) {
            $this->object = null;
            return null;
        }

        try {
            $this->object->txn_rollback( );
        }
        catch (Exception $e) {
            throw new TransactionManagerException(
                "Rollback failed: {$e->getMessage( )}",
                TransactionManagerException::ROLLBACK_FAILED
            );
        }
    }
}


class TransactionManagerException extends Exception
{
    const KIRIN_TM_EXCEPTION       = 200;
    const NESTED_TRANSACTION_ERROR = 201;
    const ROLLBACK_FAILED          = 202;
}
