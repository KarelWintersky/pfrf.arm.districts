<?php

/**
 *
 */
class dbConfig
{
    /**
     * @var array
     */
    public static $CFG = array(
        'hostname' => array(
            'local'     => 'localhost',
            'sweb'      => '',
            'pfrf'      =>  '',
            'remote'    =>  ''
        ),
        'username' => array(
            'local'     => '',
            'sweb'      => '',
            'pfrf'      =>  '',
            'remote'    =>  ''
        ),
        'password' => array(
            'local'     => '',
            'sweb'      => '',
            'pfrf'      =>  '',
            'remote'    =>  ''
        ),
        'database' => array(
            'local'     => '',
            'sweb'      => '',
            'pfrf'      =>  '',
            'remote'    =>  ''
        ),
        'basepath' => array(
            'local'     => '/pfrf',
            'sweb'	    => '/pfrf',
            'pfrf'      =>  '',
            'remote'    =>  ''
        ),
        'table_prefix'  => array(
            'local'     => '',
            'sweb'	    => '',
            'pfrf'      =>  '',
            'remote'    =>  ''
        )
    );
    public static $db_table         = 'pfrf_reports_table';
    public static $default_region   = '78-spb';
    /**
     * @var string
     */
    public static $remote_hosting_keyname   = '';
    public static $master_password          = '';

    public static function get( $key )
    {
        return self::$CFG[ $key ] [ self::$remote_hosting_keyname ];
    }

    public static function getConfig()
    {
        $current_config = array();
        foreach (self::$CFG as $key => $optionset)
        {
            $current_config [ $key ] = ($_SERVER['REMOTE_ADDR']==="127.0.0.1")
                ? self::$CFG[ $key ]['local']
                : self::$CFG[ $key ][ self::$remote_hosting_keyname ];
        }
        return $current_config;
    }

}
/*
 * Рекомендация по созданию регионов:
 * http://www.nemka.ru/kod_rus.htm
 Регионы (автомобильный код - аббревиатура на случай если код совпадет):
78-spb  -- Санкт-Петербург
47-lo   -- Ленинградская область
*/