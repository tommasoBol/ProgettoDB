<?php session_start() ?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/registrazione.css">
    </head>
    <body>
        <div class="container">
            <h2>Entra in ESQL</h2>
        </div class="container">
        <div>
            <form action="login.php" method="post">
                <div>
                    <label for="email">Email</label>
                    <input type="text" name="email" />
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" name="password" />
                </div>
                <div>
                    <input type="radio" id="studente" name="tipo_utente" value="studente" />
                    <label for="studente">Studente</label>
                    <input type="radio" id="docente" name="tipo_utente" value="docente" />
                    <label for="docente">Docente</label>
                </div>
                <div>
                    <input type="submit" name="login" value="Entra" />
                </div>
            </form>
            <?php
                if (isset($_POST["login"])) {
                    try {
                        include("../connection/connection.php");
                        $con = connect();

                        $email = $_POST["email"];
                        $password = $_POST["password"];

                        if (!isset($_POST["tipo_utente"])) {
                            echo("<h4>Specifica il tipo utente</h4>");
                            return;
                        }
                        $tipo_utente = $_POST["tipo_utente"];
                        $sql = "call login_".$tipo_utente."(:email, :password)";
                        $stmt = $con->prepare($sql);
                        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                        $stmt->bindParam(":password", $password, PDO::PARAM_STR);

                        $stmt->execute();

                        if ($stmt->fetchColumn()>0) {
                            echo("Successo");
                            $_SESSION["user"] = $email;
                            
                            if ($tipo_utente=="docente") {
                                $_SESSION["user_type"] = "docente";
                                header("Location: ../home/home-docente.php");
                            }else {
                                $_SESSION["user_type"] = "studente";
                                header("Location: ../home/home-studente.php");
                            }
                            
                        } else {
                            echo("Fail");
                        }
                    }catch(PDOException $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
        </div>
    </body>
</html>