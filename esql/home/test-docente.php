<?php 
    session_start();

    if (!isset($_SESSION["user"]) || (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"]!="docente"))) { 
        header("Location: ../sign/login.php");
    }

    include_once("create-test.php");
    include_once("get-test.php");
    include_once("get-table.php");
    include_once("foreign-key.php");
    include_once("../connection/connection.php");
    include_once("../mongo/log.php");

    function printTestAsOption($tests) {
        foreach($tests as $t) {
            $titolo = $t["titolo"];
            echo("<option value='$titolo'>$titolo</option>");
        }
    }

    function printTableAsCheckBox($name) {
        echo("<label>$name</label>");
        echo("<input type='checkbox' name='nome_tabella[]' value='$name'/>");
    }

    function printTableAttributesAsTable($table_name) {
        $con = connect();
        $result = getTableAttributes($table_name, $con);
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


?>

<!DOCTYPE html>
<html>
    <head>
    <link rel="stylesheet" href="../css/homedocente.css">
    <script src="./js/testDocente.js"></script>
    </head>
    <body>
        <nav>
            <div><a href="home-docente.php">Tabelle</a></div>
            <div><a href="test-docente.php">Test</a></div>
            <div><a href="">Statistiche</a></div>
            <div><a href="messaggi-docente.php">Messaggi</a></div>
        </nav>
        <section>
            <h3>Crea un nuovo test</h3>
            <form action="test-docente.php" method="post" enctype="multipart/form-data">
                <div>
                    <label>Titolo del test</label>
                    <input type="text" name="titolo_test" required />
                </div>
                <div>
                <label>Immagine del test</label>
                    <input type="file" name="immagine_test" />
                </div>
                <div>
                    <input type="submit" name="crea_test" value="Crea test" />
                </div>
            </form>
            <?php 
                if (isset($_POST["crea_test"])) {
                    try {
                        $foto = "null";
                        if (isset($_FILES["immagine_test"]) && $_FILES["immagine_test"]["error"]==0) {
                            $foto = file_get_contents($_FILES["immagine_test"]["tmp_name"]);
                        }
                        creaTest($_POST["titolo_test"], $foto, $_SESSION["user"], connect());
                        echo("Test caricato con successo");
                        insertLog("Creato test da " . $_SESSION["user"]);
                    }catch(Exception $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
        </section>

        <section>
            <h3>Aggiungi un quesito ad un test</h3>
            <form action="test-docente.php" method="get">
                <div>
                    <h4>Test al quale aggiungere il quesito</h4>
                    <select name="tutti_test" required>
                        <?php 
                            try {
                            printTestAsOption(getTest($_SESSION["user"], connect()));
                            }catch(Exception $e) {
                                echo($e->getMessage());
                            }
                        ?>
                    </select> 
                </div>
                <div>
                    <h4>Che tipo di quesito vuoi creare?</h4>
                    <label for="chiuso">Risposta multipla</label>
                    <input type="radio" id="chiuso" name="tipo_quesito" value="chiuso" />
                    <label for="codice">Quesito di codice</label>
                    <input type="radio" id="codice" name="tipo_quesito" value="codice" /> 
                </div>
                <div>
                    <h4>A quali tabelle fa riferimento il quesito?</h4>
                    <?php 
                        $result = getTableFromDocente($_SESSION["user"], connect());
                        foreach($result as $row) {
                            printTableAsCheckBox($row[0]);
                            echo("<br>");
                        }
                    ?>
                </div>
                <div>
                    <input type="submit" name="scegli_quesito" value="Vai" />
                </div>
            </form>
            <form>
                <div>
                    <?php 
                        if (isset($_GET["scegli_quesito"])) {
                            if (isset($_GET["tipo_quesito"]) && isset($_GET["nome_tabella"])) {
                                $array_nome_tabella = [];
                                foreach($_GET["nome_tabella"] as $t) {
                                    array_push($array_nome_tabella, $t);
                                    echo("<div>");
                                    printTableAttributesAsTable($t);
                                    printForeignKeysForTable(getForeignKeysForTable($t, connect()));
                                    echo("</div>");
                                }
                                $_SESSION["nome_tabella"] = $array_nome_tabella;
                                $_SESSION["test"] = $_GET["tutti_test"];
                                $_SESSION["tipo_quesito"] = $_GET["tipo_quesito"];
                                echo("<div>");
                                echo("Descrizione del quesito");
                                echo("<br>");
                                echo("<textarea name='descrizione_quesito' required></textarea>");
                                echo("<br>");
                                echo("</div>");
                                if ($_GET["tipo_quesito"]=="chiuso") {
                                    echo("<div id='opzioni_di_risposta'>");
                                    echo("Opzioni di risposta. Selezionare il checkbox delle risposte corrette");
                                    echo("<div id='opzione[0]'>1.<input type='text' name='opzione[0]' required/><input type='checkbox' value='0' name='corretta[0]' /></div>");
                                    echo("<div id='opzione[1]'>2.<input type='text' name='opzione[1]' required/><input type='checkbox' value='1' name='corretta[1]' /></div>");
                                    echo("</div>");
                                    echo("<div>");
                                    echo("<input type='button' onclick='addOpzione()' value='Aggiungi opzione' />");
                                    echo("<input type='button' onclick='removeOpzione()' value='Rimuovi opzione' />");
                                    echo("</div>");
                                } else if ($_GET["tipo_quesito"]=="codice") {
                                    echo("<div>");
                                    echo("Soluzioni del quesito (se ci sono più soluzioni, separarle con punto e virgola)");
                                    echo("<br>"); 
                                    echo("<textarea name='soluzione_codice' required></textarea>");
                                    echo("</div>");
                                }
                                echo("<div>");
                                echo("<h4>Livello di difficoltà</h4>");
                                echo("<label for='basso'>Basso</label>");
                                echo("<input type='radio' id='basso' name='dif_quesito' value='basso' />");
                                echo("<label for='medio'>Medio</label>");
                                echo("<input type='radio' id='medio' name='dif_quesito' value='medio' />");
                                echo("<label for='alto'>Alto</label>");
                                echo("<input type='radio' id='alto' name='dif_quesito' value='alto' />");
                                echo("</div>");
                                echo("<input type='submit' name='crea_quesito' value='Crea quesito' />");
                                
                            }else {
                                echo("<h4>Scegli il tipo di quesito e le tabelle di riferimento</h4>");
                            }
                            
                        }
                    ?>
                </div>
            </form>
            <?php 
                if (isset($_GET["crea_quesito"])) {
                    if (isset($_GET["dif_quesito"])) {
                        $titolo_test = $_SESSION["test"];
                        $difficolta = $_GET["dif_quesito"];
                        $descrizione = $_GET["descrizione_quesito"];
                        $tipo_quesito = $_SESSION["tipo_quesito"];
                        $tabelle_riferimento = $_SESSION["nome_tabella"];
                        if ($tipo_quesito=="chiuso" && isset($_GET["corretta"])) {
                            $opzioni = $_GET["opzione"];
                            $corrette = $_GET["corretta"];
                            try {
                                $con = connect();
                                $con->beginTransaction();
                                insertQuesitoRispostaChiusa($titolo_test, $difficolta, $descrizione, $opzioni, $corrette, $tabelle_riferimento, $con);
                                $con->commit();
                                echo("<h4>Quesito inserito correttmente</h4>");
                                insertLog("Quesito creato da " . $_SESSION["user"]);
                            }catch(PDOException $e) {
                                $con->rollBack();
                                echo($e->getMessage());
                            }
                        }else if($tipo_quesito=="codice") {
                            $soluzioni = $_GET["soluzione_codice"];
                            $soluzioni_array = explode(";", $soluzioni);
                            try {
                                $con = connect();
                                $con->beginTransaction();
                                insertQuesitoCodice($titolo_test, $difficolta, $descrizione, $soluzioni_array, $tabelle_riferimento, $con);
                                $con->commit();
                                echo("<h4>Quesito inserito correttmente</h4>");
                                insertLog("Quesito creato da " . $_SESSION["user"]);
                            }catch (PDOException $e) {
                                $con->rollBack();
                                echo($e->getMessage());
                            }
                        } else if ($tipo_quesito=="chiuso" && !isset($_GET["corretta"])) {
                            echo("Almeno una delle opzioni deve essere corretta");
                        }
                    }else {
                        echo("Inserisci il livello di difficoltà del quesito");
                    }
                }
            ?>
        </section>

        <section>
            <h3>Visualizza i test e i relativi quesiti</h3>
            <form action="test-docente.php" method="get">
                <div>
                    <select name="tutti_test" required>
                        <?php 
                            try {
                            printTestAsOption(getTest($_SESSION["user"], connect()));
                            }catch(Exception $e) {
                                echo($e->getMessage());
                            }
                        ?>
                    </select>
                </div>
                <div>
                    <input type="submit" name="get_test" value="Cerca" />
                </div>
            </form>
            <?php 
                if (isset($_GET["get_test"])) {
                    $con = connect();
                    $quesiti = getQuesiti($_GET["tutti_test"], $con);
                    $opzioni = getOpzioniRispostaByTest($_GET["tutti_test"], $con);
                    $soluzioni = getSoluzioniByTest($_GET["tutti_test"], $con);
                    $test = getTestByTitle($_GET["tutti_test"], $con);
                    $title = $test[0]["titolo"];
                    $data = $test[0]["data_creazione"];
                    $foto = $test[0]["foto"];
                    $visualizza_risposte = $test[0]["visualizza_risposte"];
                    $visualizza_risposte_string = ($visualizza_risposte==1) ? "true" : "false";
                    echo("<h4>Titolo del test: $title</h4>");
                    echo("<h4>Data creazione: $data</h4>");
                    echo("<h4>Visualizza risposte: $visualizza_risposte_string</h4>");
                    echo('<img src="data:image/jpeg;base64,'.base64_encode( $foto ).'"/>');

                    foreach($quesiti as $q) {
                        echo("<div>");
                        echo($q["numero"].".");
                        echo($q["descrizione"]);
                        echo("<br>");
                        echo("Difficolta: " . $q["difficolta"]);
                        echo("<div>");
                        foreach($opzioni as $o) {
                            if ($o["numero_quesito"]==$q["numero"]) {
                                echo("<div>");
                                echo("Opzione " . $o["numero"] . ": ");
                                echo($o["testo"]);
                                if ($o["is_correct"]=="1") {
                                    echo("     (Corretta)");
                                }
                                echo("</div>");
                            }
                        }
                        foreach($soluzioni as $s) {
                            echo("<div>");
                            if ($s["numero_quesito"]==$q["numero"]) {
                                echo("Soluzione " . $s["numero"] . ": ");
                                echo($s["testo"]);
                            }
                            echo("</div>");
                        }
                        echo("</div>");
                        echo("<br><br>");
                        echo("</div>");
                    }

                }
            ?>
        </section>
        <section>
            <h3>Rendi visibili le soluzioni dei test</h3>
            <form action="test-docente.php" method="get">
                <div>
                    <div>
                        <select name="test_visibile" required>
                            <?php printTestAsOption(getTest($_SESSION["user"], connect())); ?>
                        </select>
                    </div>
                    <div>
                        <input type="submit" name="visualizza_risposte" value="Rendi visibile" />
                    </div>
                </div>
            </form>
            <?php 
                if (isset($_GET["visualizza_risposte"])) {
                    try {
                        $con = connect();
                        $test = $_GET["test_visibile"];
                        $sql = "update test set visualizza_risposte=true where titolo=:title";
                        $stmt = $con->prepare($sql);
                        $stmt->bindParam(":title", $test);
                        $stmt->execute();
                        echo("Le soluzioni sono ora visibili agli studenti");
                        insertLog($_SESSION["user"] . " ha reso visibili le risposte ad un test");
                    }catch (PDOException $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
        </section>
    </body>
</html>