<?php

    function getTest($email, $con) {
        $sql = "select * from test where email_docente=:email";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }


    function getAllTests($con) {
        $sql = "select * from test";
        $result = $con->query($sql);
        return $result->fetchAll();
    }



    function getTestByTitle($titolo, $con) {
        $sql = "select * from test where titolo=:titolo";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":titolo", $titolo);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }


    function getQuesiti($titolo_test, $con) {
        $sql = "select * from quesito where titolo_test=:title order by numero";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getOpzioniRispostaByTest($titolo_test, $con) {
        $sql = "select * from opzione_risposta where titolo_test_quesito=:title";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getSoluzioniByTest($titolo_test, $con) {
        $sql = "select * from soluzione where titolo_test_quesito=:title";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        return $result;
    }


    function getTabelleRiferimento($num_quesito, $titolo_test, $con) {
        $sql = "select nome_tabella from riferimento where numero_quesito=:num_q and titolo_test=:title";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

?>