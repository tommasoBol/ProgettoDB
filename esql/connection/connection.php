<?php

    function connect() {
        $hostname="localhost";
        $db="esqldb";
        $username="root";
        $password="";
        $pdo = new PDO("mysql:host=" . $hostname . ";dbname=" . $db, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

?>