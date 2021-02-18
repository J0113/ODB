<?php
namespace PHP_OOP_Database;
class MySqlQueryEncoder extends QueryEncoder
{
    /**
     * Get the escaped sql query
     * @see parameters() - must be used with
     * @return string
     */
    public function sql() : string
    {

        // select from
        $columns = $this->columns_sql();
        $table = $this->str_escape($this->table);
        $query = "SELECT $columns FROM `$table` ";

        // Where
        if ($this->where !== null){
            $query .= $this->where_sql();
        }

        // Order by
        if ($this->order !== null){
            $query .= $this->orderby_sql();
        }

        // Limit
        if ($this->limit !== null){
            $query .= $this->limit_sql();
        }

        return $query;
    }

    /**
     * Get the parameters
     * @see sql() - must be used with
     * @return array
     */
    public function parameters() : array
    {
        $parameters = array();

        // ---
        // Where
        if ($this->where !== null){

            $i = 0;
            foreach ($this->where as $or_array){
                foreach ($or_array as $and_array){
                    if (empty($and_array["key"])) continue;
                    if (!in_array($and_array["operator"], ["=", ">", "<", ">=", "<=", "!=", "<>", "BETWEEN", "LIKE", "IN"])) continue;
                    $i++;
                    $parameters["vw{$i}"] = $and_array["value"];
                }
            }

        }

        return $parameters;
    }

    /**
     * Get the columns' sql (to use with SELECT).
     * @return string
     */
    private function columns_sql() : string
    {

        if ($this->columns === null){
            $columns = "*";
        } else {
            $columns = $this->columns;
            $columns = implode(",", array_map(function ($item){
                if (is_array($item)) return "";
                return '`' . $this->str_escape($item) . '`';
            }, $columns));
        }

        return $columns;
    }

    private function where_sql(){
        $i = 0;
        $or_strings = array();
        foreach ($this->where as $or_array){
            $and_strings = array();
            foreach ($or_array as $and_array){
                if (empty($and_array["key"])) continue;
                if (!in_array($and_array["operator"], ["=", ">", "<", ">=", "<=", "!=", "<>", "BETWEEN", "LIKE", "IN"])) continue;
                $i++;
                $and_strings[] = "`" . $this->str_escape($and_array["key"]) . "` " . $and_array["operator"] . " :vw{$i}";
            }
            $or_string = implode(" AND ", $and_strings);
            if (!empty($or_string)) $or_strings[] = $or_string;
        }

        if (!empty($or_strings)) $query = " WHERE (" . implode(" OR ", $or_strings) . ")";
        return isset($query) ? $query : "";
    }

    /**
     * Get the orderby SQL.
     * @return string
     */
    private function orderby_sql(): string
    {
        $orderbys = array();
        foreach ($this->order as $orderby){
            if (!empty($orderby["key"]) && !empty($orderby["direction"])){
                $orderbys[] = $this->str_escape($orderby["key"]) . " " . $this->str_escape($orderby["direction"]);
            }
        }
        return " ORDER BY " . implode(",", $orderbys);
    }

    private function limit_sql(){
        $limit = $this->limit;
        $offset = $this->offset === null ? 0 : $this->offset;
        return " LIMIT $offset, $limit";
    }

    /**
     * May only be used internally (and is therefore private). Basic escape, use the library provided tools where
     * possible!
     *
     * @param $to_escape
     * @return string
     */
    private function str_escape($to_escape) : string
    {
        if ($to_escape === null) return "";

        return str_replace(["`", "'", "\\", "/", "--", "#", "!", '"'], "", $to_escape);
    }

    /**
     * Generate an update query.
     *
     * @param array $data
     * @return string
     */
    public function update_sql(array $data) : string
    {
        if (empty($table)) return "";

        $table = $this->str_escape($this->table);
        $sql = "UPDATE `$table` ";

        $sql .= " SET ";

        $i = 0;
        $set_array = [];
        foreach ($data as $column => $value){
            $i++;
            $set_array[] = "`".$this->str_escape($column)."` = :upv$i ";
        }

        $sql .= implode(",", $set_array);

        $sql .= " " . $this->where_sql();

        return $sql;
    }

}