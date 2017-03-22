<?php

/**
 * Contains logging functionality for use with Easify Plugin Code
 */
class Easify_Logging {

    /**
     * A static logging method that you can use anywhere that includes this file
     * without having to instantiate the class.
     * 
     * Usage: Easify_Logging::Log("Hello, world!");
     * 
     * @param type $text - the text to be logged. If $text is an array it is 
     * rendered with print_r()
     * @return type void
     */
    public static function Log($text) {
        if (!EASIFY_LOGGING_BY_DB_FLAG && !EASIFY_LOGGING) {
            return;
        }

        // write to log file in the following format: 17-12-2012 10:15:10:000000 - $text \n
        $LogFile = fopen(dirname(dirname(__FILE__)) . '/logs/easify_log.txt', 'a');

        if (is_array($text)) {
            $text = print_r($text, true);
        }

        fwrite($LogFile, date('d-m-y H:i:s') . substr((string) microtime(), 1, 6) . ' - ' . $text . "\n");
        fclose($LogFile);
    }
}

?>
