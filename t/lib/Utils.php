<?php

class Utils
{
    static function setup ( )
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE foo (id INTEGER PRIMARY KEY, var TEXT)');
        return $pdo;
    }
}
