<?php
require_once('config.php');

function ConnectDB( $config )
{
    $link = mysql_connect(
        $config['hostname'],
        $config['username'],
        $config['password']) or die("can't connect to DB");

    mysql_select_db($config['database'], $link) or die("can't set active DB ");
    mysql_query("SET NAMES utf8", $link);
    return $link;
}

function CloseDB($link) // useless
{
    mysql_close($link) or Die("Не удается закрыть соединение с базой данных.");
}

function DB_EscapeArray( $array , $link )
{
    $result = array();
    foreach ($array as $key => $keyvalue) {
        switch (gettype( $keyvalue )) {
            case 'string': {
                $result [ $key ] = trim(mysql_real_escape_string($keyvalue , $link ));
                break;
            }
            case 'array': {
                $result [ $key ] = DB_EscapeArray( $keyvalue , $link );
                break;
            }
            default: {
            $result [ $key ] = $keyvalue;
            }
        }
    }
    return $result;
}

/**
 * @param $link
 * @param $array
 * @param $table
 * @param string $where
 * @return string
 */
function MakeInsert($link, $array, $table, $where = '' )
{
    $arr = DB_EscapeArray( $array , $link);

    // build query...
    $query  = "INSERT INTO {$table}";

    // implode keys of $array...
    $query .= " (`".implode("`, `", array_keys($arr))."`)";

    // implode values of $array...
    $query .= " VALUES ('".implode("', '", $arr)."') ";

    $query .= $where;

    return $query;
}

/**
 * @param $link
 * @param $array
 * @param $table
 * @param string $where
 * @return string
 */
function MakeUpdate($link, $array, $table, $where = "")
{
    $arr = DB_EscapeArray( $array , $link );
    $query = "UPDATE {$table} SET ";
    foreach ($arr as $key=>$val)
    {
        $query.= "`".$key."` = '".$val."', ";
    };
    $query = rtrim( $query , ", ");
    $query.= " ".$where;
    return $query;
}

function DBIsTableExists($table)
{
    $real_table = getTablePrefix() . $table;
    return (mysql_query("SELECT 1 FROM {$real_table} WHERE 0")) ? true : false;
}

function throw_ex($er){
    throw new Exception($er);
}

/* backup the db OR just a table */
function get_backup_tables($host, $user, $pass, $name, $tables = '*')
{

    $link = mysql_connect($host, $user, $pass);
    mysql_select_db($name,$link);

    //get all of the tables
    if($tables == '*')
    {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        while($row = mysql_fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',',$tables);
    }
    $return = '';

    //cycle through
    foreach($tables as $table)
    {
        $result = mysql_query('SELECT * FROM '.$table);
        $num_fields = mysql_num_fields($result);

        $return .= 'DROP TABLE '.$table.';';
        $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
        $return .= "\n\n".$row2[1].";\n\n";

        for ($i = 0; $i < $num_fields; $i++)
        {
            while($row = mysql_fetch_row($result))
            {
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++)
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }
    return $return;

    //save file
    /*
    $filename = 'db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
    $handle = fopen($filename, 'w+');
    fwrite($handle,$return);
    fclose($handle);
    return $filename;
    */
}

