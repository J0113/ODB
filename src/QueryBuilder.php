<?php
namespace PHP_OOP_Database;
class QueryBuilder
{

    /**
     * Contains the columns to get, if it is null the engine should return all columns
     * @var array|null
     */
    protected ?array $columns = null;

    /**
     * What columns should be returned
     *
     * @param string|string[] ...$columns
     * @return $this
     */
    public function columns(...$columns) : self
    {
        if (isset($columns[0]) && is_array($columns[0])) $columns = $columns[0];

        if ($this->columns !== null) $this->warn("Columns are overwritten.");
        $this->columns = $columns;

        return $this;
    }

    /**
     * Same as columns but with one column (may be used multiple times).
     * @param $column
     * @return $this
     */
    public function column(string $column) : self
    {
        if ($this->columns === null) $this->columns = [];
        $this->columns[] = $column;
        return $this;
    }


    /**
     * Contains the table from where to retrieve the data
     * @var string|null
     */
    protected ?string $table = null;

    public function from($table) : self
    {
        if ($this->table !== null) $this->warn("Table is overwritten.");
        $this->table = $table;

        return $this;
    }


    /**
     * Contains an integer of the amount of rows
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * Contains an integer of the amount of the offset
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * Limit the amount of results. Both integers must be positive.
     *
     * @param int $per_page
     * @param int $offset
     * @return $this
     */
    public function limit(int $per_page, int $offset = 0) : self
    {
        if ($per_page >= 0){
            if ($this->limit !== null) $this->warn("Limit is overwritten.");
            $this->limit = $per_page;
        } else $this->warn("Negative posts per page");

        if ($offset >= 0){
            if ($this->offset !== null) $this->warn("Offset is overwritten.");
            $this->offset = $offset;
        } else $this->warn("Negative offset");

        return $this;
    }

    /**
     * Split the result in pages. Like limit but calculates the offset for you.
     *
     * @uses limit()
     *
     * @param int $per_page
     * @param int $page
     * @return $this
     */
    public function paginate(int $per_page, int $page = 1) : self
    {
        return $this->limit($per_page, $per_page * $page - $per_page);
    }

    /**
     * Contains the where arguments. Note the structure: (when implementing the query generator)
     'where' => array(
        array(
            array(
                "key" => "",
                "value" => "",
                "operator" => "=",
            ),
            // and
            array(...),
        ),
        // or
        array(
            array(...),
            // and
            array(...),
        )
    )
     * @var array|null
     */
    protected ?array $where = null;

    /**
     * Start the where query, this may be called one; then use andWhere() and orWhere().
     * @see andWhere()
     * @see orWhere()
     *
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @return $this
     */
    public function where(string $key, $value, string $operator = "=") : self
    {
        if ($this->where === null) {
            $this->where = array(
                array(
                    array(
                        "key" => $key,
                        "value" => $value,
                        "operator" => $operator,
                    )
                )
            );
        } else $this->warn("Where is already initiated.");

        return $this;
    }

    /**
     * Also, where an other key is a value. where() must have already been executed.
     * @see where()
     *
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @return $this
     */
    public function andWhere(string $key, $value, string $operator = "=") : self
    {
        if ($this->where !== null){
            $this->where[array_key_last($this->where)][] = array(
                "key" => $key,
                "value" => $value,
                "operator" => $operator
            );
        } else {
            $this->warn("Where has not been initiated yet.");
            $this->where($key, $value, $operator);
        }
        return $this;
    }

    /**
     * Or, where a key is a value. where() must have already been executed.
     * @see where()
     *
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @return $this
     */
    public function orWhere(string $key, $value, string $operator = "=") : self
    {
        if ($this->where !== null){
            $this->where[] = array(
                array(
                    "key" => $key,
                    "value" => $value,
                    "operator" => $operator,
                )
            );
        } else {
            $this->warn("Where has not been initiated yet.");
            $this->where($key, $value, $operator);
        }
        return $this;
    }

    /**
     * Contains an array of keys/ columns to order by
     * [['key' => '', 'direction' => 'ASC'], ...]
     * @var array|null
     */
    protected ?array $order = null;

    /**
     * Add an key to order by. Can be called multiple times.
     *
     * @param string $key
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $key, $direction = "ASC") : self
    {
        if ($this->order === null) $this->order = array();
        $this->order[] = array(
            "key" => $key,
            "direction" => $direction,
        );
        return $this;
    }

    /**
     * Used internally to show warnings.
     * @param string $msg
     */
    protected function warn($msg){
        trigger_error($msg, E_USER_WARNING);
    }

    /**
     * Convert the query to an array that the database engine can use.
     * @internal
     * @ignore
     * @return array
     */
    public function toArray() : array
    {
        return array(
            "columns" => $this->columns,
            "table" => $this->table,
            "limit" => $this->limit,
            "offset" => $this->offset,
            "where" => $this->where,
            "order" => $this->order,
        );
    }

    /**
     * May be used by encoders to construct from a query builder
     *
     * @param array $array
     * @return $this
     */
    protected function fromArray(array $array) : self
    {
        $this->columns = $array["columns"];
        $this->table = $array["table"];
        $this->limit = $array["limit"];
        $this->offset = $array["offset"];
        $this->where = $array["where"];
        $this->order = $array["order"];

        return $this;
    }

}