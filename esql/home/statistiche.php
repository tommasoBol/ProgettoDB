<?php

    session_start();

    if (!isset($_SESSION["user"]) || !isset($_SESSION["user_type"])) { 
        header("Location: ../sign/login.php");
    }

    include_once("../connection/connection.php");
    $con = connect();

?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/statistiche.css">
    </head>
    <body>
        <?php 
            if ($_SESSION["user_type"]=="docente") {
                echo("
                <nav>
                    <div><a href='home-docente.php'>Tabelle</a></div>
                    <div><a href='test-docente.php'>Test</a></div>
                    <div><a href='statistiche.php'>Statistiche</a></div>
                    <div><a href='messaggi-docente.php'>Messaggi</a></div>
                </nav>
                ");
            } else {
                echo("
                <nav>
                    <div><a href='home-studente.php'>Home</a></div>
                    <div><a href='statistiche.php'>Statistiche</a></div>
                </nav>
                ");
            }
        ?>
            <h3>Statistiche</h3>
            <section>
                <section>
                    <?php
                        $sql = "select * from statistica1";
                        $result = $con->query($sql);
                        $result->setFetchMode(PDO::FETCH_ASSOC);
                        $stats = $result->fetchAll();
                        echo("<table>");
                            echo("<tr>");
                                echo("<th>Codice studente</th>");
                                echo("<th>Test completati</th>");
                            echo("</tr>");
                            foreach($stats as $s) {
                                $cod = $s["codice_studente"];
                                $num = $s["conteggio"];
                                echo("<tr>");
                                echo("<td>$cod</td>");
                                echo("<td>$num</td>");
                                echo("</tr>");
                            }
                        echo("</table>");

                    ?>
                </section>
                <section>
                    <?php 
                        $sql = "select * from statistica2 where punteggio is not null";
                        $result = $con->query($sql);
                        $result->setFetchMode(PDO::FETCH_ASSOC);
                        $stats = $result->fetchAll();
                        echo("<table>");
                        echo("<tr>");
                            echo("<th>Codice studente</th>");
                            echo("<th>% risposte corrette</th>");
                        echo("</tr>");
                        foreach($stats as $s) {
                            $cod = $s["codice"];
                            $num = $s["punteggio"];
                            $num_int = intval($num) * 100;
                            echo("<tr>");
                            echo("<td>$cod</td>");
                            echo("<td>$num_int</td>");
                            echo("</tr>");
                        }
                        echo("</table>");
                    ?>
                </section>
                <section>
                <?php 
                        $sql = "select * from statistica3";
                        $result = $con->query($sql);
                        $result->setFetchMode(PDO::FETCH_ASSOC);
                        $stats = $result->fetchAll();
                        echo("<table>");
                        echo("<tr>");
                            echo("<th>Numero quesito</th>");
                            echo("<th>Titolo test</th>");
                            echo("<th>Risposte</th>");
                        echo("</tr>");
                        foreach($stats as $s) {
                            $num_q = $s["numero"];
                            $titolo = $s["titolo_test"];
                            $num_r = $s["num_risposte"];
                            echo("<tr>");
                            echo("<td>$num_q</td>");
                            echo("<td>$titolo</td>");
                            echo("<td>$num_r</td>");
                            echo("</tr>");
                        }
                        echo("</table>");
                    ?>
                </section>
            </section>
    </body>
</html>