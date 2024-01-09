<?php

    function getTableFromDocente($email, $con) {
        $sql = "call get_table_from_docente(:email)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute(); 
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getTableAttributes($table_name, $con) {
        $sql = "call get_attributes_from_table(:nome_tabella)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":nome_tabella", $table_name);
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getPrimaryKey($table_name, $con) {
        $sql = "call get_primary_key_attributes(:nome_tabella)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":nome_tabella", $table_name);
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getTableDataByAttributes($table_name, $attr_name, $con) {
        $sql = "select $attr_name from $table_name";
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $result = $stmt->fetchAll();
        return $result;
    }

    function getTableData($table_name, $con) {
        $sql = "select * from $table_name";
        $result = $con->query($sql);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }

?>