<?php

    session_start();

    if (!isset($_SESSION["user"]) || (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"]!="docente"))) { 
        header("Location: ../sign/login.php");
    }

    include_once("../connection/connection.php");
    include_once("get-test.php");
    include_once("../mongo/log.php");

    function printTestAsOption($tests) {
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

    function insertDestinatario($id_messaggio, $destinatario, $con) {
        $sql = "insert into destinatario(id_messaggio, utente_destinatario) values(:id, :dest)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":id", $id_messaggio);
        $stmt->bindParam(":dest", $destinatario);
        $stmt->execute();
    }

    function getAllStudents($con) {
        $sql = "select email_utente from studente";
        $result = $con->query($sql);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }

?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/homedocente.css">
    </head>
    <body>
        <nav>
            <div><a href="home-docente.php">Tabelle</a></div>
            <div><a href="test-docente.php">Test</a></div>
            <div><a href="">Statistiche</a></div>
            <div><a href="messaggi-docente.php">Messaggi</a></div>
        </nav>
        <section>
            <h3>Visualizza i messaggi ricevuti</h3>
            <div>
                <?php 
                    try {
                        $con = connect();
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
                        $result->closeCursor();
                    }catch(PDOException $e) {
                        echo($e->getMessage());
                    }
                ?>
            </div>
        </section>
        <section>
            <h3>Scrivi un messaggio a tutti gli studenti</h3>
            <div>
                <form action="messaggi-docente.php" method="get">
                    <div>
                        <div>A quale test si riferisce il messaggio?</div>
                        <div>
                            <select name="test-messaggio" required>
                                <?php
                                    $tests = getTest($_SESSION["user"], $con); 
                                    printTestAsOption($tests);
                                ?>
                            </select>
                        </div>
                        <div>
                            <label for="titolo-messaggio">Titolo del messaggio</label>
                            <input type="text" name="titolo-messaggio" required/>
                        </div>
                        <div>
                            <textarea name="corpo-messaggio" required></textarea>
                        </div>
                    </div>
                    <div>
                        <div>
                            <input type="submit" name="invia-messaggio" vaule="Invia" />
                        </div>
                    </div>
                </form>
                <?php
                    if (isset($_GET["invia-messaggio"])) {
                        try {
                            $con->beginTransaction();
                            $titolo = $_GET["titolo-messaggio"];
                            $corpo = $_GET["corpo-messaggio"];
                            $mittente = $_SESSION["user"];
                            $riferimento_test = $_GET["test-messaggio"];
                            $data = date("Y-m-d");
                            $id_messaggio = insertMessage($titolo, $mittente, $data, $corpo, $riferimento_test, $con);
                            $studenti = getAllStudents($con);
                            foreach($studenti as $s) {
                                insertDestinatario($id_messaggio, $s["email_utente"], $con);
                            }
                            $con->commit();
                            echo("Messaggio inviato correttamente");
                            insertLog($_SESSION["user"] . " ha inviato un messaggio");
                        }catch(PDOException $e) {
                            echo($e->getMessage());
                            $con->rollBack();
                        }
                    }
                ?>
            </div>
        </section>
    </body>
</html>