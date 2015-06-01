<?php
/**
 * User: Arris
 * Date: 01.06.15, time: 8:01
 */

require_once('lib/kw.core.php');
require_once('lib/kw.db.php');
require_once('lib/kw.kwt.php');
require_once('lib/pfrf.core.php');

$action = at($_GET, 'action', 'no-action');

$incoming_data = array(
    'district_title'    =>  'Район (строка на русском)',
    'district_abbr'     =>  'Район (аббревиатура, латиницей)',
    'region_title'      =>  'Регион (строка на русском)',
    'region_abbr'       =>  'Регион (аббревиатура - автокод+сокращение региона)',
    'district_ipmask'   =>  'IP-маска района',
);

switch ($action) {
    case 'get-comments' : {
        $result = array();
        $q_comments = "SELECT column_name, column_comment FROM information_schema.COLUMNS " .
            " WHERE TABLE_NAME = '{$reference}' AND column_name IN ( '". implode("' , '" , array_keys($incoming_data)) ."' )" ;

        $r = mysql_query($q_comments);
        $rn = @mysql_num_rows($r);
        if ($rn > 0) {
            while ($row = mysql_fetch_assoc($r)) {
                // если комментарий к полю в базе пуст - возвращаем описание поля из локального конфига
                $result['data'][ $row['column_name'] ]
                    = ($row['column_comment'] != '')
                    ? $row['column_comment']
                    : $incoming_data[ $row['column_name'] ];
            }
            $result['state'] = 'ok';
            $result['message'] = ifDebug($q_comments);
        } else {
            $result['state'] = 'error';
            $result['message'] = ifDebug($q_comments);
        }

        $return = json_encode($result);
        break;
    }

    case 'insert' : {
        if ($_GET['password'] == dbConfig::$master_password) {
            $q = makeDataSet( $_GET , $incoming_data);
            $qstr = MakeInsert($link, $q, $reference);

            $res = mysql_query($qstr, $link) or Die("Unable to insert data to DB! ".$qstr);

            $new_id = mysql_insert_id() or Die("Unable to get last insert id! Last request is [$qstr]");

            $result['message'] = ifDebug($qstr);
            $result['error'] = 0;
        } else {
            $result['message'] = 'Password incorrect!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    }

    case 'update' : {
        if ($_GET['password'] == dbConfig::$master_password) {
            $id = intval($_GET['id']);
            $q = makeDataSet( $_GET , $incoming_data);
            $qstr = MakeUpdate($link, $q, $reference, "WHERE id=$id");

            $res = mysql_query($qstr, $link) or Die("Unable update data : ".$qstr);

            $result['message'] = ifDebug($qstr);
            $result['error'] = 0;
        } else {
            $result['message'] = 'Password incorrect!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    }

    case 'clearregion' : {
        if ($_GET['password'] == dbConfig::$master_password) {
            $escaped_region = isset($_GET['region'])
                ? mysql_real_escape_string($_GET['region'])
                : "all_regions";

            $where_regions = ($escaped_region === "all_regions")
                ? ""
                : "WHERE region_abbr='{$escaped_region}'";

            $q = array(
                'fsd_recalc'        =>  '',
                'fsd_navyplatu'     =>  '',
                'dmo_indexing'      =>  '',
                'dmo_navyplatu'     =>  '',
                'neoplata'          =>  '',
                'fovd_vedomostpochta'=>  '',
                'fovd_sberbank'     =>  '',
                'fovd_otherkreditors'=>  '',
                'fovd_vedomostbank' =>  '',
                'fovd_spiski'       =>  '',
                'forming_dostavdocs'=>  '',
                'frd_evneoplata'    =>  '',
                'frd_70letgww'      =>  '',
                'frd_neoplatadopmass'   =>  '',
                'raschet_forming'   =>  '',
                'raschet_spravka'   =>  ''
            );

            $qstr = MakeUpdate($link, $q, $reference, $where_regions);

            $res = mysql_query($qstr, $link) or Die("Unable update data : ".$qstr);

            $result['message'] = ifDebug($qstr);
            $result['error'] = 0;
        } else {
            $result['message'] = 'Password incorrect!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    }

    case 'remove' : {
        if ($_GET['password'] == dbConfig::$master_password) {
            $id = intval($_GET['id']);

            $q = "DELETE FROM {$reference} WHERE (id={$id})";
            if ($r = mysql_query($q)) {
                // запрос удаления успешен
                $result["error"] = 0;
                $result['message'] = 'Удаление успешно';

            } else {
                // DB error again
                $result["error"] = 1;
                $result['message'] = 'Ошибка удаления!';
            }
        } else {
            $result['message'] = 'Неправильный пароль!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    }

    case 'load' : {
        $id = intval($_GET['id']);

        $WHERE_CLAUSE = "WHERE id={$id}";

        $query = "SELECT ";
        $query.= implode(" , ", array_keys($incoming_data));
        $query.= " FROM {$reference} ";
        $query.= $WHERE_CLAUSE;

        $res = mysql_query($query) or die("Невозможно получить содержимое справочника! ".$query);
        $ref_numrows = mysql_num_rows($res);

        if ($ref_numrows != 0) {
            $result['data'] = mysql_fetch_assoc($res);
            $result['error'] = 0;
            $result['message'] = '';
        } else {
            $result['error'] = 1;
            $result['message'] = 'Ошибка доступа к базе данных!';
        }
        $return = json_encode($result);
        break;
    }

    case 'list' : {
        break;
    }

    case '...' : {
        break;
    }

    case '...' : {
        break;
    }
}



 
 
