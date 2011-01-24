<?php
error_reporting(E_ALL | E_STRICT);
restore_include_path( );
$ROOT = dirname(__FILE__).'/../../';
set_include_path(get_include_path( ).":{$ROOT}/lib:{$ROOT}/t/lib");
require_once 'lime.php';


$t = new lime_test( );


function done_testing ( )
{
    global $t;

    $funcs   = get_defined_functions( );
    $initialize = false;
    $finalize   = false;


    if ( in_array('initialize', $funcs['user']) ) {
        $initialize = true;
    }

    if ( in_array('finalize', $funcs['user']) ) {
        $finalize = true;
    }

    if ( in_array('build', $funcs['user']) ) {
        build($t);
    }

    foreach ($funcs['user'] as $func) {
        # 予約された function
        if ( in_array($func, array('build', 'destroy', 'initialize', 'finalize', 'done_testing')) ) {
            continue;
        }
        # _function( ) は skip
        if ( preg_match('/^_/', $func) ) {
            continue;
        }

        if ($initialize) initialize($t);

        try {
            $t->diag("in function '$func'");
            $func($t);
        }
        catch (Exception $e) {
            $t->diag($e->getMessage( )." at $func( )");
            $t->fail( );
        }

        if ($finalize) finalize($t);
    }

    if ( in_array('destroy', $funcs['user']) ) {
        destroy($t);
    }
}
