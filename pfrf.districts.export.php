<?php
/**
 * User: Arris
 * Date: 25.04.15, time: 6:45
 */

require_once('lib/kw.dbi.php');
require_once('lib/kw.kwt.php');
require_once('lib/kw.core.php');

function download_send_headers($filename, $filesize)
{
    header ("HTTP/1.1 200 OK");
    header ("X-Powered-By: PHP/" . phpversion());
    header ("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
    header ("Cache-Control: None");
    header ("Pragma: no-cache");
    header ("Accept-Ranges: bytes");
    header ("Content-Disposition: inline; filename=\"" . $filename . "\"");

    if (isset($_SERVER['HTTP_USER_AGENT']) and strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
        Header('Content-Type: application/force-download');
    else
        Header('Content-Type: application/octet-stream');
    header ("Content-Length: " . $filesize);
    header ("Age: 0");
    header ("Proxy-Connection: close");
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $filesize);

}

function array2csv(array &$array)
{
    if (count($array) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    fputcsv($df, array_keys(reset($array)) , ';', '"');
    foreach ($array as $row) {
        fputcsv($df, $row, ';');
    }
    fclose($df);
    return ob_get_clean();
}

$filename = isset($_GET['filename']) ? $_GET['filename'] : 'report.csv';
$like_region = isset($_GET['region']) ? " WHERE region_abbr LIKE '{$_GET['region']}'" : '';
$db_table = dbConfig::$db_table;

$q = "SELECT * FROM {$db_table} {$like_region}";

$db = new kwDBI();
$exported_data = $db->fetch_all($q);

$csv = array(
);

foreach ( $exported_data as $row )
{
    $csv[] = array(
        "Район"                                     =>  $row['district_title'],
        "ФСД Перерасчет"                            =>  $row["fsd_recalc"],
        "ФСД Прием на выплату"                      =>  $row["fsd_navyplatu"],
        "ДМО Индексация"                            =>  $row["dmo_indexing"],
        "ДМО Прием на выплату"                      =>  $row["dmo_navyplatu"],
        "Неоплата по основным и разовым выплатам"   =>  $row["neoplata"],
        "ФОВД Ведомость (почта)"                    =>  $row["fovd_vedomostpochta"],
        "ФОВД Сбербанк"                             =>  $row["fovd_sberbank"],
        "ФОВД Прочие кредитные организации"         =>  $row["fovd_otherkreditors"],
        "ФОВД Ведомость (почтоые через банк)"       =>  $row["fovd_vedomostbank"],
        "ФОВД Списки (спецучереждения)"             =>  $row["fovd_spiski"],
        "Формирование дост.документов по удержаниям"=>  $row["forming_dostavdocs"],
        "ФРД неоплата/возвраты"                     =>  $row["frd_evneoplata"],
        "ФРД 70 лет ВОВ"                            =>  $row["frd_70letgww"],
        "ФРД неоплата допмассива"                   =>  $row["frd_neoplatadopmass"],
        "Расчетные ведомости формирования"          =>  $row["raschet_forming"],
        "Расчетные ведомости отправка в отделение"  =>  $row["raschet_spravka"]
    );
}

/* Тут только одна проблема - документ в UTF-8 */

$filecontent = array2csv($csv);
$filecontent = iconv( "utf-8", "windows-1251", $filecontent);


download_send_headers($filename, strlen($filecontent));
echo $filecontent;

exit();