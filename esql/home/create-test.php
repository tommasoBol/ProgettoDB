<?php

    function creaTest($titolo_test, $immagine_test, $email, $con) {
        $sql = "insert into test(titolo,data_creazione,foto,email_docente) values(:titolo, :data, :foto, :email)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":titolo", $titolo_test);
        $date = date("Y-m-d");
        $stmt->bindParam(":data", $date);
        $stmt->bindParam(":foto", $immagine_test);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
    }


    function insertQuesitoRispostaChiusa($titolo_test, $difficolta, $descrizione, $opzioni, $opzioni_corrette, $tabelle_riferimento, $con) {
        $sql = "call create_quesito_risposta_chiusa(:numero_q, :titolo_t, :dif, :descriz)";
        $stmt = $con->prepare($sql);
        $q_number = getNumberForQuesito($titolo_test, $con);
        $stmt->bindParam(":numero_q", $q_number);
        $stmt->bindParam(":titolo_t", $titolo_test);
        $stmt->bindParam(":dif", $difficolta);
        $stmt->bindParam(":descriz", $descrizione);
        $stmt->execute();

        insertRiferimentoQuesitoTabelle($tabelle_riferimento, $q_number, $titolo_test, $con);

        for ($i=0;$i<sizeof($opzioni);$i++) {
            $sql_opzioni = "insert into opzione_risposta(numero, testo, is_correct, numero_quesito, titolo_test_quesito) values(:num, :text, :isc, :num_q, :title)";
            $stmt_opzioni = $con->prepare($sql_opzioni);
            $stmt_opzioni->bindParam(":num", $i);
            $stmt_opzioni->bindParam(":text", $opzioni[$i]);
            $is_corretta = false;
            if (in_array($i, $opzioni_corrette)) $is_corretta = true;
            $stmt_opzioni->bindParam(":isc", $is_corretta);
            $stmt_opzioni->bindParam(":num_q", $q_number);
            $stmt_opzioni->bindParam(":title", $titolo_test);
            $stmt_opzioni->execute();
        }
    }


    function insertQuesitoCodice($titolo_test, $difficolta, $descrizione, $soluzioni, $tabelle_riferimento, $con) {
        $sql = "call create_quesito_codice(:numero_q, :titolo_t, :dif, :descriz)";
        $stmt = $con->prepare($sql);
        $q_number = getNumberForQuesito($titolo_test, $con);
        $stmt->bindParam(":numero_q", $q_number);
        $stmt->bindParam(":titolo_t", $titolo_test);
        $stmt->bindParam(":dif", $difficolta);
        $stmt->bindParam(":descriz", $descrizione);
        $stmt->execute();

        insertRiferimentoQuesitoTabelle($tabelle_riferimento, $q_number, $titolo_test, $con);

        for ($i=0;$i<sizeof($soluzioni);$i++) {
            $sql_soluzioni = "insert into soluzione(numero, testo, numero_quesito, titolo_test_quesito) values(:num, :testo, :num_q, :title)";
            $stmt_soluzioni = $con->prepare($sql_soluzioni);
            $stmt_soluzioni->bindParam(":num", $i);
            $stmt_soluzioni->bindParam(":num_q", $q_number);
            $stmt_soluzioni->bindParam(":testo", $soluzioni[$i]);
            $stmt_soluzioni->bindParam(":title", $titolo_test);
            $stmt_soluzioni->execute();

        }
    }


    function getNumberForQuesito($titolo_test, $con) {
        $sql1 = "select count(*) from quesito where titolo_test=:title";
        $stmt1 = $con->prepare($sql1);
        $stmt1->bindParam(":title", $titolo_test);
        $stmt1->execute();
        $result = $stmt1->fetchColumn();
        return $result;
    }


    function insertRiferimentoQuesitoTabelle($tabelle_riferimento, $numero_quesito, $titolo_test, $con) {
        foreach($tabelle_riferimento as $tabella) {
            $sql_riferimento = "insert into riferimento(numero_quesito, titolo_test, nome_tabella) values(:num_q, :titolo_t, :nome_t)";
            $stmt_riferimento = $con->prepare($sql_riferimento);
            $stmt_riferimento->bindParam(":num_q", $numero_quesito);
            $stmt_riferimento->bindParam(":titolo_t", $titolo_test);
            $stmt_riferimento->bindParam(":nome_t", $tabella);
            $stmt_riferimento->execute();
        }
    }

?>