<?php

    require_once 'vendor/autoload.php'; // include Composer's autoloader

function insertLog($stringa) {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->esql->log;
    $d = date("Y-m-d H:i:s");
    $collection->insertOne([$d=>$stringa]);
}

?>