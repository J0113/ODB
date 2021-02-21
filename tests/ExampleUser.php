<?php

use J0113\ODB\PDODatabaseObject;

/**
 * Class User
 * @property User profile
 * @property User[] friends
 */
class User extends PDODatabaseObject
{

    // These are the fields
    protected ?string $username = null;
    protected ?string $firstname = null;
    protected ?string $lastname = null;

    // The relations, see the docs on the usage
    protected const RELATIONS = [
        "profile" => ["toOne", "User", "username", "username"],
        "friends" => ["toMany", "User"]
    ];

    // the table (defaults to static::class)
    protected const TABLE = "users";


    // Regular getters and setters

    /**
     * @return User
     */
    public function getProfile(): User
    {
        return $this->profile;
    }

    /**
     * @return User[]
     */
    public function getFriends(): array
    {
        return $this->friends;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     */
    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     */
    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

}