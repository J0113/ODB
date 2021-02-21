<?php
namespace J0113\ODB;
use Exception;
use PDO;
use PDOException;

/**
 * @author      Jolle
 * @copyright   Copyright (c), 2021 Jolle
 * @license     Apache License 2.0
 */
class PDODatabase
{
    /**
     * Run a query
     *
     * @param string $sql - The query it self, PLEASE use parameters (':id') to escape variables.
     * @param array $params - Insert an array here with parameters to escape SQL. [":id" => "123"]
     * @param string $return - 'rows', 'count', 'bool', 'none' - What it should return.
     * @param bool $return_false_on_exception - If it should prevent crashing on a broken query (default except for bool)
     * @return mixed
     * @throws Exception
     */
    public static function sql_query(string $sql, $params = array(), $return = "rows", $return_false_on_exception = null){
        try {
            $query=self::get_database()->prepare($sql);
            $query->execute($params);

            switch ($return){
                case "rows":
                    return $query->fetchAll();
                case "count":
                    return $query->rowCount();
                case 'none':
                case 'null':
                default:
                    return true;
            }
        } catch (PDOException $e){
            if ($return == "bool" && $return_false_on_exception !== false || $return_false_on_exception){
                return false;
            }
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Same as sql_query() but with the QueryEncoder build-in.
     *
     * @uses sql_query()
     * @param QueryBuilder $query
     * @param string $return
     * @param null $return_false_on_exception
     * @return bool|mixed
     * @throws Exception
     */
    public static function query(QueryBuilder $query, $return = "rows", $return_false_on_exception = null){

        $encoder = new MySqlQueryEncoder($query);
        $sql = $encoder->sql();
        $parameters = $encoder->parameters();

        return self::sql_query($sql, $parameters, $return, $return_false_on_exception);
    }

    /**
     * Insert data into a table, returns the ID if it succeeded.
     *
     * @param array $data
     * @param string $table
     * @return int|null
     */
    public static function insert(array $data, string $table) : ?int
    {
        $parameters = array();
        $sql = "INSERT INTO `".self::str_escape($table)."`";

        $columns = [];
        foreach (array_keys($data) as $column)
            $columns[] = "`".self::str_escape($column)."` ";
        $sql .= "(".implode(",", $columns).") ";

        $values = [];
        $i = 0;
        foreach ($data as $value){
            $i++;
            $values[] = ":val$i";
            $parameters["val$i"] = $value;
        }
        $sql .= "VALUES (".implode(",", $values).")";

        return self::sql_query($sql, $parameters, "bool") ? self::get_database()->lastInsertId() : null;
    }

    /**
     * Will update a row, use the querybuilder to define a where query and table.
     * @param QueryBuilder $query
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function update(QueryBuilder $query, array $data) : bool
    {
        if (empty($data)) return false;

        $encoder = new MySqlQueryEncoder($query);
        $sql = $encoder->update_sql($data);
        $parameters = $encoder->parameters();

        $i = 0;
        foreach ($data as $column => $value){
            $i++;
            $parameters["upv$i"] = $value;
        }

        return self::sql_query($sql, $parameters, "bool");
    }

    /**
     * Get the database connection
     * @return PDO
     * @throws Exception
     */
    protected static function get_database() : PDO {
        if (self::$database === null)
            throw new Exception("No connection available");
        return self::$database;
    }

    /**
     * Use one database connection.
     * @var PDO|null
     */
    private static ?PDO $database = null;

    /**
     * Connect to a database.
     *
     * @param $server
     * @param $username
     * @param $password
     * @param $database
     * @throws Exception
     */
    public static function connect($server, $username, $password, $database){
        try {
            $dsn = 'mysql:dbname='.$database.';host=' . $server;
            self::$database = new PDO($dsn, $username, $password);
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed");
        }
    }

    /**
     * May only be used internally (and is therefore private). Basic escape, use the library provided tools where
     * possible!
     *
     * @param $to_escape
     * @return string
     */
    private static function str_escape($to_escape) : string
    {
        if ($to_escape === null) return "";

        return str_replace(["`", "'", "\\", "/", "--", "#", "!", '"'], "", $to_escape);
    }
}
