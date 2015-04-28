<?php
/**
 * User: Arris
 * Date: 25.04.15, time: 10:49
 */

$action = isset($_POST['action']) ? $_POST['action'] : 'null';

require_once('lib/kw.dbi.php');
require_once('lib/kw.kwt.php');
require_once('lib/kw.core.php');

$db = new kwDBI();
$db_table = dbConfig::$db_table;
$db->setTable( $db_table );

$output_data = '';

switch ($action) {
    case 'getregions' : {
        $regions_data = $db->fetch_all("SELECT DISTINCT region_title, region_abbr FROM {$db_table} ");

        $data = array();
        foreach ($regions_data as $region)
        {
            $data ['data'][] = array(
                'type'      =>  'option',
                'value'     =>  $region['region_abbr'],
                'text'      =>  $region['region_title']
            );
        }
        $data['error'] = 0;
        $output_data = json_encode($data);
        break;
    }
    case 'loadregion' : {
        $region = isset($_POST['region']) ? $_POST['region'] : dbConfig::$default_region;
        $districts_data = $db->fetch_all( "SELECT * FROM {$db_table} WHERE region_abbr LIKE '{$region}' ORDER BY district_title"  );

        $table_html = '';
        $row_html = '';

        foreach ($districts_data as $i => $data)
        {
            /* проверяем доступность: входит ли НАШ IP в диапазон допустимых адресов у района */
            $chk_range = range_parser( $data['district_ipmask'] );
            $can_edit = chk_ips( explode(".", $_SERVER['REMOTE_ADDR']) ,  $chk_range[0] , $chk_range[1] );
            /* => true, если наш IP в допустимом диапазоне адресов для района и false в противном случае */

            $row_tpl = new kwt('tpl/table.row.html');
            $row_override = array(
                'is_editable'       =>  ($can_edit) ? 'is_editable' : '',
                'this_district'     =>  $data['district_abbr'],
                'this_region'       =>  $data['region_abbr']
            );

            foreach ( $data as $field_name => $field_value ) {
                $row_override [ $field_name ] = $field_value;
            }
            $row_tpl->override( $row_override );
            $row_html .= $row_tpl->getcontent();
        }

        $tpl = new kwt('tpl/table.template.html');
        $tpl_mainoverride = array(
            'districts_row' =>  $row_html,
            'main_ip_addr'  =>  $_SERVER['REMOTE_ADDR'],
            'this_region'   =>  $region
        );
        $tpl->override( $tpl_mainoverride );

        $output_data = $tpl->getcontent();

        break;
    }
    case 'updatefields' : {
        $UPDATES = $_POST['data'];
        $successfull_update = true;

        foreach ( $UPDATES as $fieldname => $value )
        {
            list( $xname, $region, $district, $field ) = explode(':' , $fieldname) ;
            $request = "
                UPDATE {$db_table} SET
                {$field} = '{$value}'
                WHERE
                region_abbr LIKE '{$region}'
                AND
                district_abbr LIKE '{$district}'";

            $successfull_update = $successfull_update && $db->query( $request );
            if (!$successfull_update) break;
        }
        $output_data = $request;
        break;
    }
} // switch
print($output_data);
die();