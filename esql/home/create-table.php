<?php

function insertTable($nome, $email, $con) {
    $sql = "insert into tabella(nome, data_creazione, email_docente) values(:name, :date, :email)";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(":name", $nome);
    $date = date("Y-m-d");
    $stmt->bindParam(":date", $date);
    $stmt->bindParam(":email", $email);

    $stmt->execute();
}


function createTriggerForTable($nome_tabella, $con) {
    $sql = "create trigger after_insert_$nome_tabella after insert on $nome_tabella for each row update tabella set num_righe=num_righe+1 where nome='$nome_tabella';";
    $stmt = $con->prepare($sql);
    $stmt->execute();
}

function insertAttributes($attributes, $nome_tabella, $con) {
    foreach($attributes as $attribute) {
        $sql = "insert into attributo(nome, nome_tabella, tipo, is_primary_key) values(:name, :table_name, :type, :ispk)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":name", $attribute["name"]);
        $stmt->bindParam(":table_name", $nome_tabella);
        $realType = control_type($attribute);
        $stmt->bindParam(":type", $realType);
        $ispk = false;
        if (isset($attribute["primary"])) {
            $ispk = true;
        }
        $stmt->bindParam(":ispk", $ispk);
        $stmt->execute();
    }
}

function createStringAttributes($attributes) {
    $strings = [];
    $i = 0;
    foreach($attributes as $attribute) {
        $string_attribute = $attribute["name"] . " ". control_type($attribute);
        if (isset($attribute["ai"])) $string_attribute = $string_attribute . " auto_increment";
        if (!isset($attribute["null"])) $string_attribute = $string_attribute . " not null";
        $strings[$i] = $string_attribute;
        $i++;
    }
    return $strings;
}


function createStringForCreatingTable($table_name, $string_attributes, $pk, $con) {
    $sql = "create table " . $table_name . "(";
    for ($k=0;$k<count($string_attributes);$k++) {
        $sql = $sql . $string_attributes[$k]. ",";
    }
    $sql = $sql . "primary key(" . implode(",", $pk) . "));";

    $stmt = $con->prepare($sql);
    $stmt->execute();
}


function checkIfPrimaryKeyIsSet($attributes) {
    $flag = false;
    foreach($attributes as $attribute) {
        if (isset($attribute["primary"]))
            $flag = true;
    }
    return $flag;
}


function detect_primary_key($attributes) {
    $pk = [];
    $i = 0;
    foreach($attributes as $attribute) {
        if (isset($attribute["primary"])) {
            $pk[$i] = $attribute["name"];
            $i++;
        }
    }
    return $pk;
}



function control_type($attribute) {
    $realType = $attribute["type"];
    if ($realType=="char" || $realType=="varchar") {
        $length = ($attribute["length"]=="") ? 100 : $attribute["length"];
        $realType = $realType . "(".$length.")";
    }
    if ($realType=="enum") {
        $realType = $realType . "(" . $attribute["length"] . ")";
    }
    return $realType; 
}

?>