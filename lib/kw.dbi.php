<?php
require_once('config.php');

/**
 * @param $array
 * @param $link
 * @return array
 */
function DB_EscapeArray( $array , $link )
{
    $result = array();
    foreach ($array as $key => $keyvalue) {
        switch (gettype( $keyvalue )) {
            case 'string': {
                $result [ $key ] = trim(mysqli_real_escape_string($link, $keyvalue ));
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
    $query = "INSERT INTO $table ";

    $keys = "(";
    $vals = "(";
    foreach ($arr as $key => $val) {
        $keys .= "`" . $key . "`" . ",";
        $vals .= "'".$val."',";
    }
    $query .= trim($keys,",") . ") VALUES " . trim($vals,",") . ") ".$where;
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
    $query = "UPDATE $table SET ";
    foreach ($arr as $key=>$val)
    {
        $query.= "`".$key."` = '".$val."', ";
    };
    $query = rtrim( $query , ", ");
    $query.= " ".$where;
    return $query;
}


/**
 *
 */
class kwDBI extends dbConfig
{
    /**
     * @var mysqli
     */
    private $link;
    /**
     * @var string
     */
    private $target_table = '';
    /**
     * @var
     */
    private $t_prefix;

    /**
     *
     */
    public function __construct()
    {
        $hostname = ($_SERVER['REMOTE_ADDR']==="127.0.0.1") ? self::$CFG['hostname']['local']     : self::$CFG['hostname'][ self::$remote_hosting_keyname ];
        $username = ($_SERVER['REMOTE_ADDR']==="127.0.0.1") ? self::$CFG['username']['local']     : self::$CFG['username'][ self::$remote_hosting_keyname ];
        $password = ($_SERVER['REMOTE_ADDR']==="127.0.0.1") ? self::$CFG['password']['local']     : self::$CFG['password'][ self::$remote_hosting_keyname ];
        $database = ($_SERVER['REMOTE_ADDR']==="127.0.0.1") ? self::$CFG['database']['local']     : self::$CFG['database'][ self::$remote_hosting_keyname ];
        $this->t_prefix = ($_SERVER['REMOTE_ADDR']==="127.0.0.1") ? self::$CFG['table_prefix']['local'] : self::$CFG['table_prefix'][ self::$remote_hosting_keyname ];

        $link = new mysqli($hostname, $username, $password, $database);
        if ($link->connect_error) {
            die('Connection error (' . $link->connect_errno . ') '
                . $link->connect_error);
        }
        $link->query("SET NAMES utf8");
        $this->link = $link;
    }

    /**
     *
     */
    public function __close()
    {
        $this->link->close();
    }

    /**
     * @return mixed
     */
    public function last_insert_id()
    {
        return $this->link->insert_id;
    }

    /**
     * @param $query
     * @return bool|mysqli_result
     */
    public function query( $query )
    {
        return $this->link->query( $query );
    }

    /**
     * @param $table
     */
    public function setTable( $table )
    {
        $this->target_table = $this->t_prefix . $table;
    }

    /**
     * @param $array
     * @param string $table
     * @return bool|int|mixed
     */
    public function insert( $array, $table='' )
    {
        if ($this->link === null) return false;

        $curr_table = ($this->target_table != '') ? $this->target_table : $table;

        $query = MakeInsert($this->link,  $array, $curr_table );
        $this->link->query( $query ) or die($query);

        if ($this->link->connect_errno === 0) {
            return $this->link->insert_id;
        } else {
            return (0 - $this->link->connect_errno);
        }
    }

    /**
     * @param $array
     * @param string $condition
     * @param string $table
     * @return bool|string
     */
    public function update( $array, $condition = '' , $table = '')
    {
        if ($this->link === null) return false;

        $curr_table = ($this->target_table != '') ? $this->target_table : $table;

        $query = MakeUpdate($this->link, $array, $curr_table, $condition);

        $this->link->query( $query ) or die($query);

        return $this->link->connect_errno;
    }

    public function delete( $id , $table = '')
    {
        if ($this->link === null ) return false;
        if (!isset($id)) return false;

        $curr_table = ($this->target_table != '') ? $this->target_table : $table;

        $query = "DELETE FROM {$curr_table} WHERE (id={$id})";

        $this->link->query( $query ) or die($query);

        return $this->link->connect_errno;
    }

    /**
     * @param $query
     * @return array|bool
     */
    public function fetch_all( $query )
    {
        if ($this->link === null) return false;
        $return = array();

        if ($result = $this->link->query($query)) {

            /* выборка данных и помещение их в массив */
            while ($row = $result->fetch_assoc()) {
                $return[] = $row;
            }

            /* очищаем результирующий набор */
            $result->close();
        } else {
            $return = false;
        }

        return $return;
    }

    public function fetch_row( $query )
    {
        if ($this->link === null) return false;

        if ($result = $this->link->query( $query )) {
            $return = $result->fetch_assoc();
            $result->close();
        } else {
            $return = false;
        }

        return $return;
    }


}
