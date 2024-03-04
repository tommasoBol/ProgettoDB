<?php
    session_start();

    if (!isset($_SESSION["user"]) || (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"]!="studente"))) { 
        header("Location: ../sign/login.php");
    }

    include_once("get-test.php");
    include_once("get-table.php");
    include_once("foreign-key.php");
    include_once("../mongo/log.php");
    include_once("../connection/connection.php");

    function printTestAsOption($tests) {
        foreach($tests as $t) {
            $titolo = $t["titolo"];
            echo("<option value='$titolo'>$titolo</option>");
        }
    }

    function printTableAttributesAsTable($result) {
        echo("<table>");
        echo("<tr>");
        echo("<th>Attributo</th>");
        echo("<th>Nome Tabella</th>");
        echo("<th>Tipo</th>");
        echo("<th>is primary key</th>");
        echo("</tr>");
        foreach($result as $row) {
            echo("<tr>");
            foreach($row as $column) {
                echo("<td>".$column."</td>");
            }
            echo("</tr>");
        }
        echo("</table>");
    }

    function printForeignKeysForTable($result) {
        if (sizeof($result)>0) {
            echo("<h4>Vincoli di integrità</h4>");
            foreach($result as $row) {
                echo("<div>".$row[0]."->".$row[1]."(". $row[2] .")</div>");
            }
        }
    }


    function getCodiceStudenteFromEmail($email, $con) {
        $sql = "select codice from studente where email_utente=:email";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }


    function rispostaChiusa($codice_studente, $num_opzione, $num_quesito, $titolo_test, $con) {
        $sql = "insert into risposta_chiusa(codice_studente, numero_opzione, numero_quesito, titolo_test_quesito) values(:cod, :num_o, :num_q, :title)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->bindParam(":num_o", $num_opzione);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
    }


    function rispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con) {
        $sql = "insert into risposta_codice(codice_studente, titolo_test_quesito, numero_quesito, testo) values(:cod, :title, :num_q, :t)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":t", $testo);
        $stmt->execute();
    }


    function getSolutionsForQuesito($num_quesito, $titolo_test, $con) {
        $sql_to_find_solution = "select testo from soluzione where numero_quesito=:num_q and titolo_test_quesito=:title";
        $stmt = $con->prepare($sql_to_find_solution);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $all_solutions = $stmt->fetchAll();
        return $all_solutions;
    }


    function eseguiRispostaCodiceStudente($query_studente, $con) {
        $lower_query = strtolower($query_studente);
        if (str_contains($lower_query, "create table") || str_contains($lower_query, "update") || str_contains($lower_query, "insert into") || str_contains($lower_query, "delete") || str_contains($lower_query, "drop")) {
            return false;
        } else {
            $stmt = $con->prepare($query_studente);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $risultato_risposta_studente = $stmt->fetchAll();
            return $risultato_risposta_studente;
        }
    }

    function eseguiSoluzioneCodice($soluzione, $con)  {
        $stmt = $con->prepare($soluzione);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $risultato_solution_query = $stmt->fetchAll();
        return $risultato_solution_query;
    }

    function updateEsitoRisposta($codice_studente, $num_quesito, $titolo_test, $risposta_studente, $soluzione, $con) {
        $sql_to_update_esito = "";
        $flag = false;
        if ($soluzione==$risposta_studente) {
            $sql_to_update_esito = "update risposta_codice set esito=true where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod and id >= all (select id from risposta_codice where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod)";
            $flag = true;
        } else {
            $sql_to_update_esito = "update risposta_codice set esito=false where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod and id >= all (select id from risposta_codice where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod)";
        }
        $stmt = $con->prepare($sql_to_update_esito);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->execute();
        return $flag;
    }

    function calcolaEsitoRispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con) {
        $sql_to_find_solution = "select testo from soluzione where numero_quesito=:num_q and titolo_test_quesito=:title";
        $stmt = $con->prepare($sql_to_find_solution);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $all_solutions = $stmt->fetchAll();
        foreach ($all_solutions as $solution) {
            $solution_query = $solution["testo"];
            $stmt = $con->prepare($solution_query);
            $stmt->execute();
            $risultato_solution_query = $stmt->fetchAll();
            $stmt = $con->prepare($testo);
            $stmt->execute();
            $risultato_risposta_studente = $stmt->fetchAll();
            if ($risultato_solution_query==$risultato_risposta_studente) {
                $sql_to_update_esito = "update risposta_codice set esito=true where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod and id >= all (select id from risposta_codice where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod)";
                $stmt = $con->prepare($sql_to_update_esito);
                $stmt->bindParam(":num_q", $num_quesito);
                $stmt->bindParam(":title", $titolo_test);
                $stmt->bindParam(":cod", $codice_studente);
                $stmt->execute();
                return true;
            }
        }
        $sql_to_update_esito = "update risposta_codice set esito=false where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod and id >= all (select id from risposta_codice where numero_quesito=:num_q and titolo_test_quesito=:title and codice_studente=:cod)";
        $stmt = $con->prepare($sql_to_update_esito);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->execute();
        return false;
    }


    function checkIfTestIsOpen($titolo_test, $codice_studente, $con) {
        $sql = "select stato from completamento where titolo_test=:title and codice_studente=:cod";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll();
        if ($stmt->rowCount()>0) {
            if ($result[0]["stato"]=="Concluso") {
                return false;
            } else {
                return true;
            }
        } else {
            $sql = "select visualizza_risposte from test where titolo=:title";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(":title", $titolo_test);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $result = $stmt->fetchAll();
            if ($result[0]["visualizza_risposte"]==0) {
                return true;
            } else {
                return false;
            }

        }
    }


    function getRisposteCodice($codice_studente, $titolo_test, $num_quesito, $con) {
        $sql = "select * from risposta_codice where codice_studente=:cod and titolo_test_quesito=:title and numero_quesito=:num_q";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    function getRisposteChiuse($codice_studente, $titolo_test, $num_quesito, $con) {
        $sql = "select * from risposta_chiusa where codice_studente=:cod and titolo_test_quesito=:title and numero_quesito=:num_q";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":cod", $codice_studente);
        $stmt->bindParam(":title", $titolo_test);
        $stmt->bindParam(":num_q", $num_quesito);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }


    function getAllDocenti($con) {
        $sql = "select email_utente from docente";
        $result = $con->query($sql);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }

    function printDocentiAsOption($docenti) {
        foreach($docenti as $d) {
            $email = $d["email_utente"];
            echo("<option value='$email'>$email</option>");
        }
    }


    function printTestsAsOption($tests) {
        foreach($tests as $t) {
            $titolo = $t["titolo"];
            echo("<option value='$titolo'>$titolo</option>");
        }
    }


    function insertMessage($titolo, $mittente, $data, $testo, $test, $con) {
        $sql = "insert into messaggio(titolo, testo, data_inserimento, titolo_test, utente_mittente) values(:title, :text, :date, :t_title, :mit)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":title", $titolo);
        $stmt->bindParam(":text", $testo);
        $stmt->bindParam(":date", $data);
        $stmt->bindParam(":t_title", $test);
        $stmt->bindParam(":mit", $mittente);
        $stmt->execute();
        return $con->lastInsertId();
    }


    function insertDestinatario($id_messaggio, $docente_destinatario, $con) {
        $sql = "insert into destinatario(id_messaggio, utente_destinatario) values(:id, :dest)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":id", $id_messaggio);
        $stmt->bindParam(":dest", $docente_destinatario);
        $stmt->execute();
    }


?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/homedocente.css">
    </head>
    <body>
        <nav>
            <div><a href='home-studente.php'>Home</a></div>
            <div><a href='statistiche.php'>Statistiche</a></div>
        </nav>
        <section>
            <h3>Completa un test</h3>
            <form action="home-studente.php" method="get">
                <div>
                    <select name="tutti_test" required>
                        <?php 
                        $con = connect();
                        printTestAsOption(getAllTests($con)); 
                        ?>
                    </select>
                </div>
                <div>
                    <input type="submit" name="scegli_test_da_completare" />
                </div>
            </form>
            <div>
                <?php
                    if (isset($_GET["scegli_test_da_completare"])) {
                        try {
                            $titolo_test = $_GET["tutti_test"];
                            $codice_studente = getCodiceStudenteFromEmail($_SESSION["user"], $con)[0]["codice"];
                            if (checkIfTestIsOpen($titolo_test, $codice_studente, $con)) {
                            $quesiti = getQuesiti($titolo_test, $con);
                            $opzioni = getOpzioniRispostaByTest($titolo_test, $con);
                            $soluzioni = getSoluzioniByTest($titolo_test, $con);
                            foreach($quesiti as $quesito) {
                                echo("<div class='quesito'>");
                                echo("<div>");
                                echo("<h4>Date le seguenti tabelle</h4>");
                                $nomi_tabelle_riferimento = getTabelleRiferimento($quesito["numero"], $titolo_test, $con);
                                foreach($nomi_tabelle_riferimento as $nome_tabella) {
                                    $nome_t = $nome_tabella["nome_tabella"];
                                    printTableAttributesAsTable(getTableAttributes($nome_t, $con));
                                    $fk = getForeignKeysForTable($nome_tabella["nome_tabella"], $con);
                                    printForeignKeysForTable($fk);
                                }
                                echo("</div>");
                                echo("<div>");
                                $num_quesito = $quesito["numero"];
                                $desc = $quesito['descrizione'];
                                echo("<h5>$num_quesito $desc</h5>");
                                echo("</div>");
                                echo("<div>");
                                echo("<form action='home-studente.php' method='get'>");
                                $risposta_chiusa_flag = false;
                                $tipo_quesito = "codice";
                                foreach($opzioni as $o) {
                                    if ($o["numero_quesito"]==$quesito["numero"]) {
                                        $risposta_chiusa_flag = true;
                                        $tipo_quesito = "chiuso";
                                        echo("<div>");
                                        $num_opzione = $o["numero"];
                                        echo("<input type='radio' name='scelta' value='$num_opzione' />" . $o["numero"] . ": ");
                                        echo($o["testo"]);
                                        echo("</div>");
                                    }
                                }
                                if (!$risposta_chiusa_flag) {
                                    echo("<div><textarea name='risposta_chiusa' required></textarea></div>");
                                }
                                echo("<input type='hidden' name='nome_tabella' value='$nome_t' />");
                                echo("<input type='hidden' name='titolo_test' value='$titolo_test' />");
                                echo("<input type='hidden' name='tipo_quesito' value='$tipo_quesito' />");
                                echo("<input type='hidden' name='numero_quesito' value='$num_quesito' />");
                                echo("<input type='submit' name='conferma_risposta' />");
                                echo("</form>");
                                echo("</div>");
                                echo("</div>");
                            }
                        } else {
                            echo("Non è più possibile rispondere ai quesiti di questo test");
                        }
                        }catch(PDOException $e) {
                            echo($e->getMessage());
                        }
                    }
                ?>
                <?php 
                if (isset($_GET["conferma_risposta"])) {
                    $titolo_test = $_GET["titolo_test"];
                    $tipo_quesito = $_GET["tipo_quesito"];
                    $num_quesito = $_GET["numero_quesito"];
                    $codice_studente = getCodiceStudenteFromEmail($_SESSION["user"], $con)[0]["codice"];
                    if ($tipo_quesito=="codice") {
                        $testo = $_GET["risposta_chiusa"];
                        try {
                            rispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con); // inserisce la risposta nel DB
                            //calcolaEsitoRispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con);
                            echo("Risposta inserita");
                            insertLog($_SESSION["user"] . " ha inserito una risposta di codice");
                        }catch(PDOException $e) {
                            echo($e->getMessage());
                        }
                        $all_solutions = getSolutionsForQuesito($num_quesito, $titolo_test, $con); // per ogni soluzione proposta, verifica se la risposta dello studente è valida
                        foreach ($all_solutions as $solution) {
                            $solution_query = $solution["testo"];
                            try {  // esecuzione della risposta dello studente potrebbe lanciare un'eccezione PDO
                                $risultato_soluzione = eseguiSoluzioneCodice($solution_query, $con);
                                $risultato_risposta_studente = eseguiRispostaCodiceStudente($testo, $con);
                                $flag = updateEsitoRisposta($codice_studente, $num_quesito, $titolo_test, $risultato_risposta_studente, $risultato_soluzione, $con);
                                if ($flag) return; // se la risposta proposta dello studente coincide con la soluzione del docente, esci
                            } catch(PDOException $e) {
                                // viene lanciata l'eccezione. Ricalcoliamo la soluzione del docente.
                                $risultato_soluzione = eseguiSoluzioneCodice($solution_query, $con); 
                                $flag = updateEsitoRisposta($codice_studente, $num_quesito, $titolo_test, [], $risultato_soluzione, $con);
                            }
                            
                        }
                    } else {
                        try {
                            if (isset($_GET["scelta"])) {
                                $num_opzione = $_GET["scelta"];
                                rispostaChiusa($codice_studente, $num_opzione, $num_quesito, $titolo_test, $con);
                                echo("Risposta inserita");
                                insertLog($_SESSION["user"] . " ha inserito una risposta ad un quesito a scelta multipla");
                            } else {
                                echo("Inserisci la risposta");
                            }
                        }catch(PDOException $e) {
                            echo($e->getMessage());
                        }
                    }

                }
                ?>
            </div>
        </section>
        <section>
            <h3>Visualizza l'esito dei test</h3>
            <div>
                <form action="home-studente.php" method="get">
                    <div>
                        <select name="test_visibili" required>
                            <?php printTestAsOption(getAllVisibleTests($con)); ?>
                        </select>
                    </div>
                    <div>
                        <input type="submit" name="visualizza_risposte" value="Visualizza risposte" />
                    </div>
                </form>
                <?php 
                    if (isset($_GET["visualizza_risposte"])) {
                        $test_scelto = $_GET["test_visibili"];
                        $test = getTestByTitle($test_scelto, $con);
                        $foto = $test[0]["foto"];
                        $docente = $test[0]["email_docente"];
                        $quesiti = getQuesiti($test_scelto, $con);
                        $opzioni = getOpzioniRispostaByTest($test_scelto, $con);
                        $soluzioni = getSoluzioniByTest($test_scelto, $con);
                        $codice_studente = getCodiceStudenteFromEmail($_SESSION["user"], $con)[0]["codice"];
                        echo("<h4>$test_scelto</h4>");
                        echo("<h4>Creato da: $docente</h4>");
                        echo('<img src="data:image/jpeg;base64,'.base64_encode( $foto ).'"/>');
                        foreach($quesiti as $quesito) {  
                            echo("<div class='quesito'>");
                            echo("<div>");
                            $num_quesito = $quesito["numero"];
                            $desc = $quesito['descrizione'];
                            echo("<h5>$num_quesito $desc</h5>");
                            echo("</div>");
                            echo("<div>");
                            $risposta_chiusa_flag = false;
                            foreach($opzioni as $o) {    // se quesito_risposta_chiusa
                                if ($o["numero_quesito"]==$quesito["numero"]) {
                                    $risposta_chiusa_flag = true;
                                    echo("<div>");
                                    echo("Opzione " . $o["numero"] . ": ");
                                    echo($o["testo"]);
                                    if ($o["is_correct"] == true) echo("  (opzione corretta)");
                                    echo("</div>");
                                }
                            }
                            foreach ($soluzioni as $s) { // se quesito_codice
                                if ($s["numero_quesito"]==$quesito["numero"]) {
                                    echo("<div>");
                                    echo("Soluzione " . $s["numero"] . ": ");
                                    echo($s["testo"]);
                                    echo("</div>");
                                    echo("<div>");
                                    echo("Risultato della soluzione");
                                    $res = eseguiSoluzioneCodice($s["testo"], $con);   // rendiamo visibile il risultato della query soluzione
                                    echo("<table>");
                                    foreach($res as $row) {
                                        echo("<tr>");
                                        foreach ($row as $column) {
                                            echo("<td>$column</td>");
                                        }
                                        echo("</tr>");
                                    }
                                    echo("</table>");
                                    echo("</div>");
                                }
                            }
                            echo("</div>");
                            echo("<div>");
                            echo("<h4>Le tue risposte</h4>");
                            if ($risposta_chiusa_flag) {    // se in precedenza abbiamo attraversato solo il risposta_chiusa loop
                                $allAnswers = getRisposteChiuse($codice_studente, $test_scelto, $num_quesito, $con);
                                foreach($allAnswers as $answer) {
                                    echo("<div>");
                                    echo("<h5>Risposta " . $answer["id"] . ": </h5>");
                                    echo("Hai scelto l'opzione " . $answer["numero_opzione"]);
                                    $esito = ($answer["esito"]) ? " ed era l'opzione corretta" : " ma era l'opzione sbagliata";
                                    echo($esito);
                                    echo("</div>");
                                }
                            } else {  // se abbiamo attraversato risposta_codice loop
                                $allAnswers = getRisposteCodice($codice_studente, $test_scelto, $num_quesito, $con);
                                foreach($allAnswers as $answer) {
                                    echo("<div>");
                                    echo("<h5>Risposta " . $answer["id"] . ": </h5>");
                                    echo($answer["testo"]);
                                    echo("</div>");
                                    echo("<div>");
                                    try {
                                    $res = eseguiRispostaCodiceStudente($answer["testo"], $con);   // rendiamo visibile il risultato della query risposta_codice
                                    if ($res) {
                                        echo("<table>");
                                        foreach($res as $row) {
                                            echo("<tr>");
                                            foreach ($row as $column) {
                                                echo("<td>$column</td>");
                                            }
                                            echo("</tr>");
                                        }
                                        echo("</table>");
                                        $esito=($answer["esito"]) ? "Hai risposto correttamente" : "Hai risposto in maniera errata"; 
                                        echo($esito);
                                    } else {
                                        echo("La query inserita non è valida");
                                    }                    
                                    } catch(PDOException $e) {
                                        echo($e->getMessage());
                                    }
                                    echo("</div>");
                                }
                            }
                            echo("</div>");
                            echo("</div>");
                        }
                    }
                ?>
            </div>
        </section>
        <section>
            <h3>Scrivi un messaggio ad un docente</h3>
            <div>
                <form action="home-studente.php" method="get">
                    <div>
                        <select name="email_docente" required>
                            <?php printDocentiAsOption(getAllDocenti($con)) ?>
                        </select>
                    </div>
                    <div>
                        <input type="submit" name="scegli_docente" value="Vai" />
                    </div>
                </form>
            </div>
            <div>
                <form action="home-studente.php" method="get">
                    <?php 
                        if (isset($_GET["scegli_docente"])) {
                            $_SESSION["docente_selezionato"] = $_GET["email_docente"];
                            $tests = getTest($_SESSION["docente_selezionato"], $con);
                            echo("<div>");
                            echo("<select name='test_scelto' required>");
                            printTestAsOption($tests);
                            echo("</select>");
                            echo("</div>");

                            echo("<div>");
                            echo("<label for='titolo_messaggio'>Titolo del messaggio</label>");
                            echo("<input type='text' name='titolo_messaggio' required />");
                            echo("</div>");

                            echo("<div>");
                            echo("<textarea name='testo_messaggio' required></textarea>");
                            echo("</div>");

                            echo("<div>");
                            echo("<input type='submit' name='send_message' value='Invia messaggio' />");
                            echo("</div>");
                        }
                    ?>
                </form>
            </div>
            <div>   
                <?php 
                    if (isset($_GET["send_message"])) {
                        try {
                            $con->beginTransaction();
                            $docente_destinatario = $_SESSION["docente_selezionato"];
                            unset($_SESSION["docente_selezionato"]);
                            $titolo = $_GET["titolo_messaggio"];
                            $testo = $_GET["testo_messaggio"];
                            $mittente = $_SESSION["user"];
                            $test = $_GET["test_scelto"];
                            $data = date("Y-m-d");
                            $id_messaggio = insertMessage($titolo, $mittente, $data, $testo, $test, $con);
                            insertDestinatario($id_messaggio, $docente_destinatario, $con);
                            $con->commit();
                            echo("Messaggio inviato");
                            insertLog("Messaggio inviato da " . $_SESSION["user"] . " a " . $docente_destinatario);
                        } catch(PDOException $e) {
                            $con->rollBack();
                            echo("Errore nell'invio del messaggio");
                        }

                    }
                ?>
            </div>
        </section>
        <section>
            <h3>Visualizza messaggi ricevuti</h3>
            <div>
                <?php 
                    try {
                        $email = $_SESSION["user"];
                        $sql = "call get_messaggi_from_destinatario('$email')";
                        $result = $con->query($sql);
                        $result->setFetchMode(PDO::FETCH_ASSOC);
                        foreach($result->fetchAll() as $m) {
                            echo("<div style='border:1px solid black;'>");
                            echo("Titolo messaggio: " . $m["titolo"]);
                            echo("<br>");
                            echo("Mittente: " . $m["utente_mittente"]);
                            echo("<br>");
                            echo("Inviato in data: " . $m["data_inserimento"]);
                            echo("<br>");
                            echo("In riferimento al test: " . $m["titolo_test"]);
                            echo("<br>");
                            echo("Corpo del messaggio: " . $m["testo"]);
                            echo("</div>");
                        }
                    }catch(PDOException $e) {
                        echo($e->getMessage());
                    }
                ?>
            </div>
        </section>
    </body>
</html>