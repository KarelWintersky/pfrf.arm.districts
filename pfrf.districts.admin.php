<?php
require_once('lib/kw.core.php');
require_once('lib/kw.db.php');
require_once('lib/kw.kwt.php');
require_once('lib/pfrf.core.php');

// Конфигурационные значения

// Основная часть
$action = at($_GET, 'action', 'no-action');

$link = ConnectDB( dbConfig::getConfig() );
$reference = dbConfig::$db_table;
$return = '';

$incoming_data_abstract = array(
    'data_int'      =>  '',
    'data_str'      =>  '',
    'data_comment'  =>  ''
);

$incoming_data = array(
    'district_title'    =>  'Район (строка на русском)',
    'district_abbr'     =>  'Район (аббревиатура, латиницей)',
    'region_title'      =>  'Регион (строка на русском)',
    'region_abbr'       =>  'Регион (аббревиатура - автокод+сокращение региона)',
    'district_ipmask'   =>  'IP-маска района',
);

switch ($action) {
    case 'get-comments': // get comments
    {
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
    } // case 'get-comments'
    case 'insert':
    {
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
    } // case 'insert'
    case 'update':
    {
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
    } // case 'update
    case 'clearregion':
    {
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
    case 'remove':
    {
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
            $result['message'] = 'Password incorrect!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    } // case 'remove
    case 'load':
    {
        $id = intval($_GET['id']);
        $WHERE_CLAUSE = "WHERE id={$id}";

        // построим запрос только по изменяемым полям (смотри конфигурацию справочника)
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
            $result['message'] = 'Ошибка базы данных!';
        }
        $return = json_encode($result);
        break;
    } // case 'load'
    case 'list':
    {
        $fields = array();
        $content_rows = array();
        $header_row = array();

        $q_comments = "SELECT column_name, column_comment FROM information_schema.COLUMNS
WHERE TABLE_NAME = '{$reference}'";

        $header_fields = mysql_query($q_comments) or die($q_comments);
        while ($a_field = mysql_fetch_assoc($header_fields)) {
            $fields [ $a_field['column_name'] ] = $a_field['column_comment'];
        }
        $fields['control'] = 'control';
        foreach ($fields as $f_index=>$f_content ) {
            $header_row[ $f_index ] =
                ($f_content != '')
                    ? $f_content
                    : $f_index;
        }
        $return = '';

        // where clause
        $escaped_region = isset($_GET['region'])
            ? mysql_real_escape_string($_GET['region'])
            : "all_regions";

        $WHERE_CLAUSE = ($escaped_region === "all_regions")
            ? ""
            : " WHERE region_abbr='{$escaped_region}' ";

        //основной запрос (строим на основе РАБОЧЕГО НАБОРА)
        $query = "SELECT id, ";
        $query.= implode(" , ", array_keys($incoming_data));
        $query.= " FROM {$reference} ";
        $query.= $WHERE_CLAUSE;

        $content_data = mysql_query( $query ) or die(mysql_error($link). ' query = ' . $query);
        $i = 0;

        if (@mysql_num_rows($content_data) > 0)
        {
            while ($ref_record = mysql_fetch_assoc($content_data))
            {
                if ($i == 0)
                {
                    // first row -- insert header
                    foreach ($ref_record as $rr_key => $rr_data)
                    {
                        $content_rows[ 0 ][ $rr_key ] = $header_row[ $rr_key ];
                    }
                    $content_rows[ 0 ]['control'] = 'control';
                    $i++;
                }

                // next rows -- insert content
                foreach ($ref_record as $rr_key => $rr_data)
                {
                    $content_rows[ $i ][ $rr_key ] = $rr_data;
                }

                $content_rows[ $i ]['control'] = <<<caseListControlButtonDeclaration
<button class="action-edit button-edit" name="{$ref_record['id']}">Edit</button>
caseListControlButtonDeclaration;
                $i++;
            }
        }
        // визуализация
        $return .= <<<caseListOutputTableStart
<table border="1" width="100%">
caseListOutputTableStart;

        if (count($content_rows) > 1) {
            foreach ($content_rows as $n => $row)
            {
                $td_start   = ($n == 0) ? '<th>'    : "<td>\r\n";
                $td_end     = ($n == 0) ? '</th>'   : "</td>\r\n";

                $return .= "<tr>\r\n";

                foreach ($content_rows [ $n ] as $r_content) {

                    $return .= <<<caseListOutputTableRow
{$td_start} {$r_content} {$td_end}
caseListOutputTableRow;

                }

                $return .= "</tr>\r\n";
            }
        } else {
            $return .= '<tr><td colspan="' . count($content_rows[0]) . '"> Справочник пуст! '
                . ifDebug($query)
                .' </td></tr>';
        }

        $return .= "</table>\r\n";
        break;
    }
    case 'no-action': {
        $tpl = new kwt('tpl/pfrf.districts.admin.html');

        $tpl_override = array(
            'html_title'    =>  "Административный раздел: удаление и добавление районов!",
            'html_thisreference'    =>  '',
            'html_base_exit_path'   =>  ''
        );

        $tpl->override( $tpl_override );

        $return = $tpl->getcontent();
        break;
    }
} //switch
CloseDB($link);
print($return);