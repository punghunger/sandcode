<?php

/**
  +------------------------------
 * Oralce数据库操作类
 * @author  Li Zongheng
 * @date    2016-01-19
  +------------------------------
 */
class WindOralce {

    private static $mode = OCI_COMMIT_ON_SUCCESS;
    public static $db = null;
    public static $mulitDb = null;
    public static $lastSql = null;
    private static $ociParse = null;

    static function mulitConnect($index = 0, $username, $password, $db, $charset = 'utf8') {
        self::$mulitDb[$index] = oci_connect($username, $password, $db, $charset);
    }

    static function connect($username, $password, $db, $charset = 'utf8') {
        self::$db = oci_connect($username, $password, $db, $charset);
    }

    static function switchdb($index = 0) {
        self::$db = self::$mulitDb[$index];
    }

    static function insert($table, $data) {
        $table = strtoupper($table);
        $fileds = array_map("strtoupper", array_keys($data));
        $filed = implode(',', $fileds);
        $value = implode("','", array_values($data));
        $sql = "INSERT INTO {$table} ({$filed}) VALUES ('{$value}')";
        // 执行语句 
        $result = self::query($sql);
        self::free();
        return $result;
    }

    static function update($table, $data, $where = '') {
        $table = strtoupper($table);
        array_walk($data, 'WindOralce::joinSet');
        $filed = implode(',', $data);
        $sql = "UPDATE {$table} SET {$filed}";
        $where && $sql.= " WHERE {$where}";
        // 执行语句
        $result = self::query($sql);
        self::free();
        return $result;
    }

    static function query($sql) {
        self::$lastSql = $sql;
        //编译sql语句 
        self::$ociParse = oci_parse(self::$db, $sql);
        return oci_execute(self::$ociParse, self::$mode);
    }

    static function joinSet(&$value, $key) {
        $key = strtoupper($key);
        $value = "{$key}='{$value}'";
    }

    static function findAll($sql) {
        // 编译sql语句 
        self::query($sql);
        $list = array();
//        oci_fetch_all(self::$ociParse, $list, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
        while (($row = oci_fetch_array(self::$ociParse, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) != false) {  //取回结果 
            $list[] = array_change_key_case($row, CASE_LOWER);
        }
        self::free();
        return $list;
    }

    static function find($sql) {
        // 编译sql语句 
        self::query($sql);
        $assoc = oci_fetch_array(self::$ociParse, OCI_ASSOC + OCI_RETURN_LOBS);   // oci_fetch_array -> OCI_ASSOC+OCI_RETURN_NULLS
        $row = array_change_key_case($assoc, CASE_LOWER);
        self::free();
        return $row;
    }

    static public function parseLimit($limit) {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1)
                $limitStr = "(numrow>" . $limit[0] . ") AND (numrow<=" . ($limit[0] + $limit[1]) . ")";
            else
                $limitStr = "(numrow>0 AND numrow<=" . $limit[0] . ")";
        }
        return $limitStr ? ' WHERE ' . $limitStr : '';
    }

    static function free() {
        oci_free_statement(self::$ociParse);
    }

    static function close() {
        oci_close(self::$db);
    }

    static function uniqid() {
        return date('ymd') . rand(100000, 999999);
    }

}

?>
