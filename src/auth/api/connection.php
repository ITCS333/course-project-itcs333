<?php

function getDBConnection(){
    
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "main";

    try{
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8",$username,$password);
        $pdo -> setAttribute(PDO::ATTR_ERRMODE,
                             PDO::ERRMODE_EXCEPTION);

    }
    catch(PDOException $e)
    {
        die("DB connection failed: " . $e->getMessage());
    }
  

    return $pdo;
}

?>