<?php
namespace PHP_OOP_Database;
interface Engine
{
    /**
     * Returns an array of rows.
     *
     * @param QueryBuilder $query
     * @return self[]|null
     */
    static function get(QueryBuilder $query) : ?array;

    /**
     * Returns one object.
     *
     * @param QueryBuilder $query
     * @return self[]|null
     */
    static function getOne(QueryBuilder $query) : ?self;


    /**
     * Returns the amount of found results.
     *
     * @param QueryBuilder $query
     * @return int
     */
    static public function count(QueryBuilder $query) : int;


    /**
     * Insert to the database
     *
     * @return $this|null
     */
    public function insert() : ?self;


    /**
     * Update a row
     *
     * @return $this|null
     */
    public function update() : ?self;


    /**
     * Delete a row
     *
     * @return bool
     */
    public function delete() : bool;
}