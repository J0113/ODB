<?php
use J0113\ODB\PDODatabase;
use J0113\ODB\QueryBuilder;

// Connect to the database
PDODatabase::connect("server.localhost", "myusername", "mypassword", "mydatabase");
// Now we can use the database:


// Get some users and display info
$users = User::get(
    (new QueryBuilder())
        ->where("firstname", "Oliver")
        ->andWhere("lastname", "Brown")
        ->limit(5)
);

if (!empty($users)){
    foreach ($users as $user){
        echo "Hello " . $user->getFirstname() . "!\n";
    }
}


// Get one user and update the profile
$user = User::getOne(
    (new QueryBuilder())->where("username", "oliver12345")
);

if ($user){
    $user->setLastname("Jones");
    $user->save();
}


// Insert a new user
$user = new User();
$user->setFirstname("John");
$user->setLastname("Smith");
$user->setUsername("john_smith");

$user->save();

echo $user->getId();