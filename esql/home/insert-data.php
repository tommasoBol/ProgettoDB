<?php

    function insertData($table_name, $attributes, $data, $con) {
        $attributes_array = [];
        foreach($attributes as $attr) {
            array_push($attributes_array, array("nome"=>$attr["nome"], "valore"=>$data[$attr["nome"]]));
        }

        $attributes_string = [];
        $values_string = [];
        for ($i=0;$i<sizeof($attributes_array);$i++) {
            array_push($attributes_string, $attributes_array[$i]["nome"]);
            if ($attributes_array[$i]["valore"]=="") {
                array_push($values_string, "null");    
            } else {
                array_push($values_string, "'".$attributes_array[$i]["valore"]."'");
            }
        }

        $sql = "insert into $table_name(" . implode(",", $attributes_string) . ") values(" . implode(",", $values_string) . ");";
        $con->exec($sql);
    }

?>