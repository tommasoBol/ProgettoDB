<?php 

    /*function insertForeignKey($attributi_referenzianti, $attributi_referenziati, $tabella_referenziante, $tabella_referenziata, $nome_fk, $con) {

        $sql = "alter table $tabella_referenziante add constraint $nome_fk foreign key (". implode(",", $attributi_referenzianti) . ") references $tabella_referenziata(" . implode(",", $attributi_referenziati) . ");";
        //$sql = "alter table :nome_t add constraint :nome foreign key (". implode(",", $attributi_referenzianti) . ") references :tabella_referenziata(" . implode(",", $attributi_referenziati) . ");";
        //$mod_sql = str_replace(array(":nome_t", ":nome", ":tabella_referenziata"), array($tabella_referenziante, $nome_fk, $tabella_referenziata), $sql);
        //echo($mod_sql);
        $stmt = $con->prepare($sql);
        /*$stmt->bindParam(":nome_t", $tabella_referenziante);
        $stmt->bindParam(":nome", $nome_fk);
        $stmt->bindParam(":tabella_referenziata", $tabella_referenziata);
        

        if ($stmt->execute()) {
            try {
                $con->beginTransaction();
                //$sql = "insert into chiave_esterna(nome) values(:nome)";
                $sql = "insert into chiave_esterna(attributo_referenziante, tabella_attributo_referenziante, attributo_referenziato, tabella_attributo_referenziato) values(:a_referenziante, :t_referenziante, :a_referenziato, t_referenziato)";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(":nome", $nome_fk);
                $stmt->execute();
                $sql = "insert into referenziante(nome_attributo, nome_tabella_attributo, nome_chiave_esterna) values(:nome_a, :nome_t, :fk_name)";
                foreach($attributi_referenzianti as $attributo) {
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(":nome_a", $attributo);
                    $stmt->bindParam(":nome_t", $tabella_referenziante);
                    $stmt->bindParam(":fk_name", $nome_fk);
                    $stmt->execute();
                }

                $sql = "insert into referenziato(nome_attributo, nome_tabella_attributo, nome_chiave_esterna) values(:nome_a, :nome_t, :fk_name)";
                foreach($attributi_referenziati as $attributo) {
                    $stmt = $con->prepare($sql);
                    $stmt->bindParam(":nome_a", $attributo);
                    $stmt->bindParam(":nome_t", $tabella_referenziata);
                    $stmt->bindParam(":fk_name", $nome_fk);
                    $stmt->execute();
                }

                $con->commit();
            }catch (PDOException $e) {
                echo($e->getMessage());
                $con->rollBack();
            }
        }
        
    }*/



    function insertForeignKey($attributi_referenzianti, $attributi_referenziati, $tabella_referenziante, $tabella_referenziata, $con) {
        $sql = "alter table $tabella_referenziante add foreign key (". implode(",", $attributi_referenzianti) . ") references $tabella_referenziata(" . implode(",", $attributi_referenziati) . ");";
        $stmt = $con->prepare($sql);
        if ($stmt->execute()) {
            try {
                $con->beginTransaction();
                $sql = "insert into chiave_esterna(attributo_referenziante, tabella_attributo_referenziante, attributo_referenziato, tabella_attributo_referenziato) values(:a_referenziante, :t_referenziante, :a_referenziato, :t_referenziato)";
                $stmt = $con->prepare($sql);
                for ($i=0;$i<sizeof($attributi_referenzianti);$i++) {
                    $stmt->bindParam(":a_referenziante", $attributi_referenzianti[$i]);
                    $stmt->bindParam(":t_referenziante", $tabella_referenziante);
                    $stmt->bindParam(":a_referenziato", $attributi_referenziati[$i]);
                    $stmt->bindParam(":t_referenziato", $tabella_referenziata);
                    $stmt->execute();
                }
                $con->commit();
                echo("Vincolo inserito correttamente");
            }catch (PDOException $e) {
                $con->rollBack();
                echo($e->getMessage());
                echo("Problemi nell'inserimento del vincolo");
            }
        }
    }






    function getForeignKeysForTable ($nome_tabella, $con) {
        $sql = "call get_foreign_keys_for_table(:nomet)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":nomet", $nome_tabella);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_NUM);
        return $stmt->fetchAll();
    }


    function getForeignKeyForAttribute($nome_attributo, $nome_tabella, $con) {
        $sql = "call get_foreign_keys_for_attribute(:nomea, :nomet)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(":nomea", $nome_attributo);
        $stmt->bindParam(":nomet", $nome_tabella);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

?>