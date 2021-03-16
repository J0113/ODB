<?php
namespace J0113\ODB;
use Exception;
use ReflectionClass;
use ReflectionProperty;

/**
 * @author      Jolle
 * @copyright   Copyright (c), 2021 Jolle
 * @license     Apache License 2.0
 */
class PDODatabaseObject implements Engine
{

    /**
     * The table to store an get from. Defaults to get_called_class()
     * @see get_called_class()
     * @var ?string
     */
    protected const TABLE = null;

    /**
     * The PDO database will use 'id' as the primary identifier
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * @return int|null
     */
    public function getId(): ?int { return $this->id; }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void { $this->id = $id; }

    /**
     * @inheritDoc
     * @return $this[]|null
     */
    static function get(QueryBuilder $query): ?array
    {
        $result = self::run($query, "rows", true);
        return $result ? $result : [];
    }

    /**
     * @inheritDoc
     * @return $this|null
     */
    static function getOne(QueryBuilder $query): ?self
    {
        $query->limit(1);
        $result = self::run($query, "rows", true);
        return $result && isset($result[0]) ? $result[0] : null;
    }

    /**
     * @inheritDoc
     */
    static public function count(QueryBuilder $query): int
    {
        $result = self::run($query, "count");
        return $result ? $result : 0;
    }

    /**
     * Run an query
     *
     * @param QueryBuilder $query
     * @param string $return
     * @param string $return_false_on_exception
     * @return bool|mixed
     * @throws Exception
     * @uses PDODatabase::sql_query()
     *
     */
    static protected function run(QueryBuilder $query, $return = "rows", $return_false_on_exception = 'null')
    {
        $query->from(self::get_table());

        $result = PDODatabase::query($query, $return, $return_false_on_exception);

        if ($return == "rows" && is_array($result)){
            $objects = array();
            foreach ($result as $row){
                if (!empty($row)){
                    $objects[] = self::row2object($row);
                }
            }
            $result = $objects;
        }

        return $result;
    }

    /**
     * ONLY use if needed! You will lose all validation and escaping (but the models are generated as objects)
     * @param string $sql - the SQL query.
     * @param array $data - array of escaped parameters, can be empty
     * @param string $return - rows, count, none
     * @param string $return_false_on_exception - If it just throw an exeption or return false
     * @return array|bool|mixed
     * @throws Exception
     * @uses PDODatabase::sql_query <-- see here
     * @deprecated WARNING THIS METHODE IS NOT SAVE. PLEASE USE WITH CAUTION, DO NOT USE USER INPUT AND MAKE SURE THE TABLES MATCH (or bad things will happen).
     */
    static public function getSql(string $sql, $data = [], $return = "rows", $return_false_on_exception = 'null'){

        $result = PDODatabase::sql_query($sql, $data, $return, $return_false_on_exception);

        if ($return == "rows" && is_array($result)){
            $objects = array();
            foreach ($result as $row){
                if (!empty($row)){
                    $objects[] = self::row2object($row);
                }
            }
            $result = $objects;
        }

        return $result;
    }

    /**
     * Convert an ass array to this object
     *
     * @param array $row
     * @return $this
     */
    static protected function row2object(array $row) : self
    {
        $class = get_called_class();
        $obj = new $class();

        $columns = self::get_columns();

        foreach ($columns as $column){
            if (isset($row[$column])){
                $obj->$column = SerializeHelper::maybe_unserialize($row[$column]);
            }
        }

        return $obj;
    }

    /**
     * @inheritDoc
     * @return $this|null
     */
    public function insert(): ?self
    {
        $id = PDODatabase::insert($this->get_data(), self::get_table());

        if ($id){
            $this->id = $id;
            return $this;
        } else return null;
    }

    /**
     * @inheritDoc
     * @return $this|null
     */
    public function update(): ?self
    {
        return PDODatabase::update(
            (new QueryBuilder())
                ->where("id", $this->id)
                ->limit(1)
                ->from(self::get_table()),
            $this->get_data()) ? $this : null;
    }

    /**
     * Automatically insert or update based on the ID.
     * @return $this|null
     */
    public function save(): ?self
    {
        return $this->id === null ? $this->insert() : $this->update();
    }

    /**
     * @inheritDoc
     */
    public function delete(): bool
    {
        $table = self::get_table();
        $query = "DELETE FROM `$table` WHERE `id` = :id";

        return PDODatabase::sql_query($query, ["id" => $this->id], "bool");
    }

    /**
     * Get all columns for this class
     * @return string[]
     */
    public static function get_columns() : array
    {
        $reserved = array_keys(self::get_relations());
        $reserved[] = "id";

        $columns = array("id");
        foreach (array_keys(get_class_vars(static::class)) as $column){
            if (!in_array($column, $reserved)){
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * This will just return all the data.
     * @return array
     */
    protected function get_data() : array
    {
        $data = [];
        foreach (self::get_columns() as $column){
            if ($this->$column !== null){
                $data[$column] = SerializeHelper::maybe_serialize($this->$column);
            }
        }
        return $data;
    }

    /**
     * Stores all the relations, these are read only and results in more than one query IF the relation is queried.
     * Must be an sub array with "key" => ["type", "class", "column", "property"].
     * - Key: is under what $obj->key the relation can be accessed.
     * - Type: can be toOne (this-1) or toMany (this-multiple)
     * - Class: the class it should be connected to (must be an child of PDODatabaseObject)
     * - Column: the column it should search in
     * - Property: the value to match against, is a property (or variable) of the current object
     *
     * SQL will generated by:
     * - LIMIT: Type
     * - FROM: Class
     * - WHERE: column = this->property
     *
     * protected const RELATIONS = ["customer" => ["toOne", "Model\User", "id", "user_id"] ];
     * $order->customer = Model\User(...)
     *
     * Tip: Use the PHPDoc '@ property' to let the IDE know about the relation.
     *
     * @var array
     */
    protected const RELATIONS = array();

    /**
     * Handles relations
     * @uses RELATIONS - How to add a relation
     * @param $name
     * @return mixed|void
     */
    public function __get($name)
    {
        $relations = self::get_relations();
        if (in_array($name, array_keys($relations))){
            $relation = $relations[$name];

            if (isset($relation[0])) {
                $type = $relation[0];
                $class = isset($relation[1]) ? $relation[1] : null;
                $column = isset($relation[2]) ? $relation[2] : null;
                $property = isset($relation[3]) ? $relation[3] : null;
                $property = isset($this->$property) ? $this->$property : $property;

                if (class_exists($class)) {

                    $query = (new QueryBuilder());
                    if (!empty($column)) $query->where($column, $property);
                    else $query->limit(10);

                    if ($type == "toOne") {
                        return $class::getOne($query);
                    } elseif ($type == "toMany") {
                        return $class::get($query);
                    }

                }
            }
        }
    }

    /**
     * Retrieves the relations array.
     * @return array
     */
    protected static function get_relations() : array
    {
        return get_called_class()::RELATIONS;
    }

    /**
     * Escapes the table name
     * @uses get_called_class()
     * @return string
     */
    public static function get_table() : string
    {
        $table = get_called_class()::TABLE;
        if ($table === null) $table = get_called_class();

        $replace = [
            "\\" => "_",
            "/" => "_",
        ];
        return $res = preg_replace("/[^a-zA-Z0-9_-]/", "", str_replace(array_keys($replace), array_values($replace), $table));

    }

    /**
     * Handles serialization (we will only store the ID, on unserialization we will locate the item in the database)
     * @return array
     */
    public function __serialize(): array
    {
        if ($this->id == null){
            $this->save();
        }

        return [$this->id];
    }

    /**
     * Will locate the object by the ID.
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        /** @var PDODatabaseObject $class */
        $class = get_called_class();

        foreach ($class::getOne((new QueryBuilder())->where("id", $data[0]))->get_data() as $key => $value){
            $this->$key = SerializeHelper::maybe_unserialize($value);
        }
    }


    /**
     * Will return the SQL query to create the table for this model.
     * @var bool $include_drop_table - If it should include a drop statement.
     * @return string
     */
    public static function sqlTable(bool $include_drop_table = true) : string
    {
        $table = self::get_table();
        $sql = "CREATE TABLE `$table` (";
        $columns = [];

        $reflection = new ReflectionClass(get_called_class());
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $prop){

            // Name
            $column_name = $prop->getName();
            $column = "`$column_name`";

            // Type
            $type = $prop->getType();
            if ($type){
                switch ($type->getName()){
                    case "int":
                        $sql_type = "BIGINT(255)";
                        break;
                    case "double":
                    case "float":
                        $sql_type = "float";
                        break;
                    case "bool":
                    case "boolean":
                        $sql_type = "TINYINT(1)";
                        break;
                    case "string":
                    default:
                        $sql_type = "MEDIUMTEXT";
                        break;
                }
            } else $sql_type = "MEDIUMTEXT";

            $column .= " $sql_type";

            // ID
            if ($column_name == "id") $column .= " NOT NULL AUTO_INCREMENT";

            // Nullable
            elseif (!$type->allowsNull()) $column .= " NOT NULL";

            // To the array
            $columns[] = $column;
        }

        // Set the primary key
        $columns[] = "PRIMARY KEY (`id`)";

        // Combine
        $sql .= implode(",", $columns) . ");";

        // if $include_drop_table than we will prepend a drop if exists query.
        if ($include_drop_table) $sql = "DROP TABLE IF EXISTS `$table`; $sql";

        // Return
        return $sql;
    }

    /**
     * Will generate a table from this model.
     * @var bool $drop_table - Will also remove the old table if it exists.
     * @return bool
     */
    public static function createTable(bool $drop_table = false) : bool
    {
        return self::getSql(self::sqlTable($drop_table), [], "bool");
    }

}