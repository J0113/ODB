<?php
namespace PHP_OOP_Database;
abstract class QueryEncoder extends QueryBuilder
{
    /**
     * QueryEncoder constructor.
     * The QueryEncoder class extends the QueryBuilder use the general purpose QueryBuilder to create your query, than
     * when you're about to run a query call the language specific encoder.
     * @param QueryBuilder $query
     */
    public function __construct(QueryBuilder $query)
    {
        $this->fromArray($query->toArray());
    }
}