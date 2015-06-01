<?php
/**
 * User: Arris
 * Date: 01.06.15, time: 9:04
 */


/**
 * @param $income
 * @param $template
 * @return array
 */
function makeDataSet( $income, $template )
{
    $return = array();
    foreach ($template as $key => $value)
    {
        $return [ $key ] = at($income , $key, '');
    }
    return $return;
}

/**
 * Returns message if DEBUG_MODE, overwise return default message (more securable)
 * @param $message
 * @param string $default_message
 * @return string
 */
function ifDebug($message, $default_message = '')
{
    if (dbConfig::$debug_mode) {
        return $message;
    } else {
        return $default_message;
    }
}