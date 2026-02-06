<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** 
 * 
 *  Set error and exception handlers for BotBlocker
 *                  
*/
function bbcs_errorHandlerSet() {
/*
    $log_to_debug = defined('BBCS_LOG_TO_DEBUG') && BBCS_LOG_TO_DEBUG;
    
    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($log_to_debug) {
        if (!(error_reporting() & $errno)) {
            return;
        }

        if ($log_to_debug) {
           error_log("BotBlocker Error [$errno]: $errstr in $errfile on line $errline");
        }
        
         echo "<b>BotBlocker </b> v." . esc_html(BOTBLOCKER_VERSION) . '<br><br>';
         echo "<pre style='background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
         echo '<b>Error:</b> ['.esc_html($errno).'] '. esc_html($errstr). '<br><br>';
         echo 'Error on line <b>'. esc_html($errline) .'</b> in file'. esc_html($errfile). '<br><br>';
         debug_print_backtrace();
         echo "</pre>";
        if (defined('BBCS_ERROR_EXIT') && BBCS_ERROR_EXIT) {
            bbcs_errorHandlerRestore();
            exit(1);
        }
    });

    set_exception_handler(function ($exception) use ($log_to_debug) {

        if ($log_to_debug) {
           error_log("BotBlocker Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
           error_log($exception->getTraceAsString());
        }
         echo "<b>BotBlocker </b> v." . esc_html(BOTBLOCKER_VERSION) . '<br><br>';
         echo "<pre style='background-color: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
         echo "<b>Exception:</b> " . esc_html($exception->getMessage()) . "<br>";
         echo "Error on line <b>" . esc_html($exception->getLine() ). "</b> in file " . esc_html($exception->getFile()) . "<br><br>";
         echo "<pre>" . esc_html($exception->getTraceAsString()) . "</pre>";
         echo "</pre>";
        if (defined('BBCS_ERROR_EXIT') && BBCS_ERROR_EXIT) {
            bbcs_errorHandlerRestore();
            exit(1);
        }
    });
*/
}

/** 
 * 
 *  Restore error and exception handlers
 *                  
*/
function bbcs_errorHandlerRestore(){
    restore_error_handler();
    restore_exception_handler();
}
