<?php
    session_start();

    if (!isset($_SESSION["user"]) && !$_SESSION["user_type"]=="studente") { 
        header("Location: ../sign/login.php");
    }

    include_once("get-test.php");
    include_once("get-table.php");
    include_once("foreign-key.php");
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


?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/homedocente.css">
    </head>
    <body>
        <section>
            <h3>Completa un test</h3>
            <form action="home-studente.php" method="get">
                <div>
                    <select name="tutti_test">
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
                            rispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con);
                            //calcolaEsitoRispostaCodice($codice_studente, $testo, $num_quesito, $titolo_test, $con);
                            echo("Risposta inserita");
                        }catch(PDOException $e) {
                            echo($e->getMessage());
                        }
                        $all_solutions = getSolutionsForQuesito($num_quesito, $titolo_test, $con);
                        foreach ($all_solutions as $solution) {
                            $solution_query = $solution["testo"];
                            try {
                                $risultato_soluzione = eseguiSoluzioneCodice($solution_query, $con);
                                $risultato_risposta_studente = eseguiRispostaCodiceStudente($testo, $con);
                                $flag = updateEsitoRisposta($codice_studente, $num_quesito, $titolo_test, $risultato_risposta_studente, $risultato_soluzione, $con);
                                if ($flag) return;
                            } catch(PDOException $e) {
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
                        <select name="test_visibili">
                            <?php printTestAsOption(getAllTests($con)); ?>
                        </select>
                    </div>
                    <div>
                        <input type="submit" name="visualizza_risposte" value="Visualizza risposte" />
                    </div>
                </form>
                <?php 
                    if (isset($_GET["visualizza_risposte"])) {
                        $test_scelto = $_GET["test_visibili"];
                        $quesiti = getQuesiti($test_scelto, $con);
                        $opzioni = getOpzioniRispostaByTest($test_scelto, $con);
                        $soluzioni = getSoluzioniByTest($test_scelto, $con);
                        $codice_studente = getCodiceStudenteFromEmail($_SESSION["user"], $con)[0]["codice"];
                        foreach($quesiti as $quesito) {
                            echo("<div class='quesito'>");
                            echo("<div>");
                            $num_quesito = $quesito["numero"];
                            $desc = $quesito['descrizione'];
                            echo("<h5>$num_quesito $desc</h5>");
                            echo("</div>");
                            echo("<div>");
                            $risposta_chiusa_flag = false;
                            foreach($opzioni as $o) {
                                if ($o["numero_quesito"]==$quesito["numero"]) {
                                    $risposta_chiusa_flag = true;
                                    echo("<div>");
                                    echo("Opzione " . $o["numero"] . ": ");
                                    echo($o["testo"]);
                                    if ($o["is_correct"] == true) echo("  (opzione corretta)");
                                    echo("</div>");
                                }
                            }
                            foreach ($soluzioni as $s) {
                                if ($s["numero_quesito"]==$quesito["numero"]) {
                                    echo("<div>");
                                    echo("Soluzione " . $s["numero"] . ": ");
                                    echo($s["testo"]);
                                    echo("</div>");
                                    echo("<div>");
                                    echo("Risultato della soluzione");
                                    $res = eseguiSoluzioneCodice($s["testo"], $con);
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
                            if ($risposta_chiusa_flag) {
                                $allAnswers = getRisposteChiuse($codice_studente, $test_scelto, $num_quesito, $con);
                                foreach($allAnswers as $answer) {
                                    echo("<div>");
                                    echo("<h5>Risposta " . $answer["id"] . ": </h5>");
                                    echo("Hai scelto l'opzione " . $answer["numero_opzione"]);
                                    $esito = ($answer["esito"]) ? " ed era l'opzione corretta" : " ma era l'opzione sbagliata";
                                    echo($esito);
                                    echo("</div>");
                                }
                            } else {
                                $allAnswers = getRisposteCodice($codice_studente, $test_scelto, $num_quesito, $con);
                                foreach($allAnswers as $answer) {
                                    echo("<div>");
                                    echo("<h5>Risposta " . $answer["id"] . ": </h5>");
                                    echo($answer["testo"]);
                                    echo("</div>");
                                    echo("<div>");
                                    try {
                                    $res = eseguiRispostaCodiceStudente($answer["testo"], $con); 
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
    </body>
</html>