<?php
require_once('lib/kw.core.php');
require_once('lib/kw.db.php');
require_once('lib/kw.kwt.php');

/* $SID = session_id();
if(empty($SID)) session_start();
if (!isLogged()) {
    redirectToLogin();
} */

// Конфигурационные значения

// Основная часть
$action = isset($_GET['action']) ? $_GET['action'] : 'no-action';

$link = ConnectDB( dbConfig::getConfig() );
$reference = dbConfig::$db_table;
$return = '';

$incoming_data_abstract = array(
    'data_int'      =>  '',
    'data_str'      =>  '',
    'data_comment'  =>  ''
);

// надо её переписать, чтобы это были не значения массива, а ключи. А в значениях будет комментарий к полю редактирования!
$incoming_data = array(
    'district_title'    =>  'Район (строка на русском)',
    'district_abbr'     =>  'Район (аббревиатура, латиницей)',
    'region_title'      =>  'Регион (строка на русском)',
    'region_abbr'       =>  'Регион (аббревиатура - автокод+сокращение региона)',
    'district_ipmask'   =>  'IP-маска района',
);

/**
 * @param $income
 * @param $template
 * @return array
 */
function makeDataSet( $income, $template )
{
    $return = array();
    foreach ($template as $key=>$value)
    {
        $return[ $key ] = $income [ $key ]; // $value - если массив конфига не содержит комментариев к полям
    }
    return $return;
}
function makeRequest()
{

}

switch ($action) {
    case 'get-comments': // get comments
    {
        $result = array();
        $q_comments = "SELECT column_name, column_comment FROM information_schema.COLUMNS " .
            " WHERE TABLE_NAME = '{$reference}' AND column_name IN ( '". implode("' , '" , array_keys($incoming_data)) ."' )" ;

        // вообще-то нам достаточно вернуть в ответ массив $incoming_data :)
        // но только если он содержит ключи, точнее ЕСЛИ соотв. строка (комментарий) в базе ПУСТА,
        // берем его значение из конфига
        // так достигается нужная работа с АБСТРАКТНЫМ справочником, а не предопределенным
        // то есть значение в базе приоритетнее конфига

        $r = mysql_query($q_comments);
        $rn = @mysql_num_rows($r);
        if ($rn > 0) {
            while ($row = mysql_fetch_assoc($r)) {
                $result['data'][ $row['column_name'] ] = $row['column_comment'];
            }
            $result['state'] = 'ok';
            $result['message'] = $q_comments;
        } else {
            $result['state'] = 'error';
            $result['message'] = $q_comments;
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

            $result['message'] = $qstr;
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

            $result['message'] = $qstr;
            $result['error'] = 0;
        } else {
            $result['message'] = 'Password incorrect!';
            $result['error'] = 1;
        }
        $return = json_encode($result);
        break;
    } // case 'update
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

        $query = "SELECT * FROM $reference WHERE id=$id";
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

        //@todo: а вот это основной запрос (вероятно его тоже нужно строить на основе РАБОЧЕГО НАБОРА)
        // подумать
        $query = "SELECT id, district_title, district_abbr, region_title,
        region_abbr, district_ipmask FROM {$reference}";

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
                $content_rows[ $i ]['control'] = <<<xxx
<button class="action-edit button-edit" name="{$ref_record['id']}">Edit</button>
xxx;
                $i++;
            }
        }
        // визуализация
        $return .= <<<ADV_TABLE_START
<table border="1" width="100%">
ADV_TABLE_START;
        if (count($content_rows) > 1) {
            foreach ($content_rows as $n => $row)
            {
                $td_start = ($n == 0) ? '<th>' : "<td>\r\n";
                $td_end = ($n == 0) ? '</th>' : "</td>\r\n";
                $return .= "<tr>\r\n";

                foreach ($content_rows [ $n ] as $r_content) {
                    $return .= <<<ADV_TABLE_TR
{$td_start} {$r_content} {$td_end}
ADV_TABLE_TR;
                }

                $return .= "</tr>\r\n";
            }
        } else {
            $return .= '<tr><td colspan="' . count($content_rows[0]) . '"> Справочник пуст! '. $query .' </td></tr>';
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