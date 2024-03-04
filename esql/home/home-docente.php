<?php
    session_start();

    if (!isset($_SESSION["user"]) || (!isset($_SESSION["user_type"]) || ($_SESSION["user_type"]!="docente"))) { 
        header("Location: ../sign/login.php");
    }

    include_once("../connection/connection.php");
    include_once("get-table.php");
    include_once("create-table.php");
    include_once("foreign-key.php");
    include_once("insert-data.php");
    include_once("../mongo/log.php");
    

    function createTable() {
            if (checkIfPrimaryKeyIsSet($_GET["attribute"])) {
                $con = connect();
                $strings = createStringAttributes($_GET["attribute"]);
                $pk = detect_primary_key($_GET["attribute"]);
                try {
                    createStringForCreatingTable($_GET["table_name"], $strings, $pk, $con);
                    createTriggerForTable($_GET["table_name"], $con);
                    try {
                        $con->beginTransaction();
                        insertTable($_GET["table_name"], $_SESSION["user"], $con);
                        insertAttributes($_GET["attribute"], $_GET["table_name"], $con);

                        $con->commit();
                        echo("Tabella creata correttamente");
                    } catch(PDOException $e) {
                        $con->rollBack();
                        echo($e->getMessage());
                    }
                }catch (PDOException $e) {
                    echo ($e->getMessage());
                }

            } else
                echo("Inserisci una chiave primaria");
    }


    function printTableFromDocenteAsOption($tables) {
        foreach ($tables as $row) {
            echo("<option value='".$row[0]."'>".$row[0]."</option>");
        }
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


    function printTableForForeignKey($nome_tabella_referenziante, $nome_tabella_referenziata) {
        $con = connect();
        $pk = getPrimaryKey($nome_tabella_referenziata, $con);
        $attributes = getTableAttributes($nome_tabella_referenziante, $con);
        echo("<table>");
        echo("<tr><th>Attributi referenzianti</th><th>Attributi referenziati</th></tr>");
        $i = 0;
        foreach ($pk as $row) {
            echo("<tr>");
            echo("<td><select name='referenziante[" . $i . "]' >");
            printAttributesAsOption($attributes);
            echo("</select></td>");
            echo("<td><select name='referenziato[" . $i . "]' >");
            printAttributesAsOption($pk);
            echo("</select></td>");
            echo("</tr>");
            $i++;
        }
        echo("</table>");
    }


    function printAttributesAsOption($attributes) {
        foreach($attributes as $a) {
            echo("<option value='".$a["nome"]."' >". $a["nome"] ."</option>");
        }
    }


    function printForeignKeysForTable($result) {
        if (sizeof($result)>0) {
            echo("<h4>Vincoli di integrità</h4>");
            foreach($result as $row) {
                echo("<div>".$row[0]."->".$row[1]."(". $row[2] .")</div>");
            }
        }
    }


    function printFormToInsertRow($table_name, $attributes) {
        $con = connect();
        //$attributes = getTableAttributes($table_name, $con);
        echo("<form action='home-docente.php' method='get'>");
        echo("<div>");
        echo("<table>");
        echo("<tr>");
        echo("<th>Attributo</th>");
        echo("<th>Tipo</th>");
        echo("<th>Valore</th>");
        echo("</tr>");
        foreach ($attributes as $attr) {
            $attr_name = $attr["nome"]; 
            $attr_type = $attr["tipo"]; 
            echo("<tr>");
            echo("<td>$attr_name</td>");
            echo("<td>$attr_type</td>");
            
            $fk = getForeignKeyForAttribute($attr["nome"], $table_name, $con);
            if (sizeof($fk)>0) {
                $data = getTableDataByAttributes($fk[0]["tabella_attributo_referenziato"], $fk[0]["attributo_referenziato"], $con);
                echo("<td>");
                echo("<select name='$attr_name' >");
                foreach($data as $d) {
                    echo("<option value='$d[0]'>$d[0]</option>");
                }
                echo("</select>");
                echo("</td>");
            } else {
                echo("<td><input type='text' name='$attr_name' /></td>");
            }
            echo("</tr>");
        }
        echo("</table>");
        echo("</div>");
        echo("<div>");
        echo("<input type='submit' name='conferma_inserimento' value='Inserisci' />");
        echo("</div>");
        echo("</form>");
    }


    function printTableData($table_name) {
        $con = connect();
        $result = getTableData($table_name, $con);
        $attributes = getTableAttributes($table_name, $con);
        echo("<table>");
        echo("<tr>");
        foreach($attributes as $attr) {
            $attr_name = $attr["nome"];
            echo("<th>$attr_name</th>");
        }
        echo("</tr>");
        foreach($result as $row) {
            echo("<tr>");
            foreach($attributes as $attr) {
                $column = $row[$attr["nome"]];
                echo("<td>$column</td>");
            }
            /*foreach($row as $column) {
                echo("<td>$column</td>");
            }*/
            echo("</tr>");
        }
        echo("</table>");
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../css/homedocente.css">
        <script src="./js/homeDocente.js"></script>
    </head>
    <body>
        <nav>
            <div><a href="home-docente.php">Tabelle</a></div>
            <div><a href="test-docente.php">Test</a></div>
            <div><a href="statistiche.php">Statistiche</a></div>
            <div><a href="messaggi-docente.php">Messaggi</a></div>
        </nav>
        <section>
            <h3>Crea una tabella</h3>
            <form action="home-docente.php" method="get">
                <div>
                    <div>
                        <label for="table_name">Nome tabella</label>
                        <input type="text" name="table_name" id="table_name" required />
                    </div>
                    <div>
                        
                    </div>
                </div>
                <div>
                    <table id="table_for_table">
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Lunghezza<span style="border:1px solid black; cursor:pointer; margin-left: 5px;" onclick="alert('Per le enumerazioni, inserire i valori tra virgolette, separati da virgola')">?</span></th>
                            <th>Null</th>
                            <th>Primary Key</th>
                            <th>A_I</th>
                        </tr>
                        <tr>
                            <td><input type="text" name="attribute[0][name]" required /></td>
                            <td>
                                <select name="attribute[0][type]">
                                    <option value="int">INT</option>
                                    <option value="varchar">VARCHAR</option>
                                    <option value="char">CHAR</option>
                                    <option value="date">DATE</option>
                                    <option value="double">DOUBLE</option>
                                    <option value="boolean">BOOLEAN</option>
                                    <option value="blob">BLOB</option>
                                    <option value="enum">ENUM</option>
                                </select>
                            </td>
                            <td><input type="text" name="attribute[0][length]" /></td>
                            <td><input type="checkbox" name="attribute[0][null]" value="null" /></td>
                            <td>
                                <input type="checkbox" name="attribute[0][primary]" value="primary" />
                            </td>
                            <td><input type="checkbox" name="attribute[0][ai]" value="ai" /></td>
                        </tr>
                    </table>
                </div>
                <div>
                    <input type="submit" name="create_table" value="Crea" />
                </div>
            </form>
            <button onclick="addRowForAttribute()">Aggiungi attributo</button>
            <button onclick="removeRowForAttribute()">Rimuovi attributo</button>
            <div>
                <h4>
                    <?php
                        if (isset($_GET["create_table"])) {
                            try {
                            createTable();
                            insertLog("Creata tabella " .  $_GET['table_name'] . " da " . $_SESSION["user"]);
                            }catch(PDOException $e) {
                                echo($e->getMessage());
                            }
                        }
                    ?>
                </h4>
            </div>
        </section>

        <section>
            <h3>Crea vincoli di integrità referenziale</h3>
            <form action="home-docente.php" method="get">
                <div>
                    <table>
                        <tr>
                            <th>Tabella referenziante</th>
                            <th>Tabella referenziata</th>
                        </tr>
                        <tr>
                            <td>
                                <select name="tabella_referenziante" required>
                                    <?php 
                                    $tabelle_docente = getTableFromDocente($_SESSION["user"], connect());
                                    printTableFromDocenteAsOption($tabelle_docente); ?>
                                </select>
                            </td>
                            <td>
                                <select name="tabella_referenziata" required>
                                    <?php printTableFromDocenteAsOption($tabelle_docente); ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <div>
                    <input type="submit" name="get_tables_attributes" value="Vai" />
                </div>
            </form>
            <form action="home-docente.php" method="get">
            <?php 
                if (isset($_GET["get_tables_attributes"])) {
                    echo("<div>");
                    printTableForForeignKey($_GET["tabella_referenziante"], $_GET["tabella_referenziata"]);
                    echo("</div>");
                    $_SESSION["tabella_referenziata"] = $_GET["tabella_referenziata"];
                    $_SESSION["tabella_referenziante"] = $_GET["tabella_referenziante"];
                    echo("<div>");
                    echo("<input type='submit' name='create_foreign_key' />");
                    echo("</div>");
                }
                if (isset($_GET["create_foreign_key"])) {
                    try {
                        insertForeignKey($_GET["referenziante"], $_GET["referenziato"], $_SESSION["tabella_referenziante"], $_SESSION["tabella_referenziata"], connect());
                        unset($_SESSION["tabella_referenziante"]);
                        unset($_SESSION["tabella_referenziata"]);
                        insertLog("Creato vincolo di integrità referenziale da " . $_SESSION["user"]);
                    } catch(PDOException $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
            </form>
        </section>

        <section>
            <h3>Visualizza la struttura delle tabelle</h3>
            <form action="home-docente.php" method="get">
                <select name="struttura_tabelle" required>
                    <?php printTableFromDocenteAsOption($tabelle_docente); ?>
                </select>
                <input type="submit" name="get_struttura_tabelle" value="Cerca" required />
            </form>
            <?php 
                if (isset($_GET["get_struttura_tabelle"])) {
                    try {
                    printTableAttributesAsTable($_GET["struttura_tabelle"]);
                    printForeignKeysForTable(getForeignKeysForTable($_GET["struttura_tabelle"], connect()));
                    }catch (PDOException $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
        </section>

        <section>
            <h3>Inserisci righe nelle tabelle create</h3>
            <form action="home-docente.php" method="get">
                <select name="tab_doc" required>
                    <?php printTableFromDocenteAsOption($tabelle_docente); ?>
                </select>
                <input type="submit" name="inserisci_righe" value="Vai" />
            </form>
            <?php 
                if (isset($_GET["inserisci_righe"])) {
                    try {
                        $con = connect();
                        $_SESSION["tab_doc"] = $_GET["tab_doc"];
                        $attributes = getTableAttributes($_SESSION["tab_doc"], $con);
                        printFormToInsertRow($_GET["tab_doc"], $attributes);
                    }catch (Exception $e) {
                        echo($e->getMessage());
                    }
                }
                if (isset($_GET["conferma_inserimento"])) {
                    try {
                        $con = connect();
                        $attributes = getTableAttributes($_SESSION["tab_doc"], $con);
                        insertData($_SESSION["tab_doc"], $attributes, $_GET, $con);
                        insertLog("Inserita riga nella tabella " . $_SESSION["tab_doc"] . " da parte del docente " . $_SESSION["user"]);
                        echo("Inserimento avvenuto");
                    }catch(Exception $e) {
                        echo($e->getMessage());
                    }
                }
            ?>
        </section>

        <section>
            <h3>Visualizza il contenuto delle tabelle</h3>
            <form action="home-docente.php" method="get">
                <select name="contenuto_tabelle" required>
                    <?php printTableFromDocenteAsOption($tabelle_docente); ?>
                </select>
                <input type="submit" name="table_data" value="Vai"/>
            </form>
            <?php 
                if (isset($_GET["table_data"])) {
                    printTableData($_GET["contenuto_tabelle"]);
                }
            ?>
        </section>
        
    </body>
</html>