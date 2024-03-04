<?php

	include_once("../mongo/log.php");

    function registraDocente() {
		try {
			include("../connection/connection.php");
			$con = connect();

			if (!validaEmail($_POST["email_docente"])) {
				echo("<h3>Email non valida</h3>");
				return;
			}

			if (!validaStringaSenzaNumeri($_POST["nome_docente"])) {
				echo("<h3>Nome non valido</h3>");
				return;
			}

			if (!validaStringaSenzaNumeri($_POST["cognome_docente"])) {
				echo("<h3>Cognome non valido</h3>");
				return;
			}

			$telefono = $_POST["telefono_docente"];
			if ($telefono != "") {
				if (!validaTelefono($_POST["telefono_docente"])) {
					echo("<h3>Telefono non valido</h3>");
					return;
				}
			} else {
				$telefono = null;
			}

			$dipartimento = $_POST["dipartimento"];
			if ($dipartimento !="") {
				if (!validaStringaSenzaNumeri($_POST["dipartimento"])) {
					echo("<h3>Nome del dipartimento non valido</h3>");
					return;
				}
			} else {
				$dipartimento = null;
			}

			$corso = $_POST["corso"];
			if ($corso=="") $corso = null;

			$con->beginTransaction();
			$sql = "call registrazione_docente(:email, :password, :nome, :cognome, :telefono, :dipartimento, :corso)";
			$stmt = $con->prepare($sql);

			$stmt->bindParam(":email", $_POST["email_docente"], PDO::PARAM_STR);
			$stmt->bindParam(":password", $_POST["password_docente"], PDO::PARAM_STR);
			$stmt->bindParam(":nome", $_POST["nome_docente"], PDO::PARAM_STR);
			$stmt->bindParam(":cognome", $_POST["cognome_docente"], PDO::PARAM_STR);
			$stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
			$stmt->bindParam(":dipartimento", $dipartimento, PDO::PARAM_STR);
			$stmt->bindParam(":corso", $corso, PDO::PARAM_STR);

			$stmt->execute();

			echo ("<h2>Registrazione avvenuta con successo</h2>");
			echo ("<a href='login.php'>Vai alla pagina di login</a>");
			insertLog("Nuovo utente inserito");

			$con->commit();
		}catch (PDOException $e) {
			echo("<h3>Problemi nella registrazione</h3>");
			$con->rollBack();
		}

    }



	function registraStudente() {
		try {
			include("../connection/connection.php");
			$con = connect();

			if (!validaEmail($_POST["email_studente"])) {
				echo("<h3>Email non valida</h3>");
				return;
			}

			if (!validaStringaSenzaNumeri($_POST["nome_studente"])) {
				echo("<h3>Nome non valido</h3>");
				return;
			}

			if (!validaStringaSenzaNumeri($_POST["cognome_studente"])) {
				echo("<h3>Cognome non valido</h3>");
				return;
			}

			$telefono = $_POST["telefono_studente"];
			if ($telefono != "") {
				if (!validaTelefono($_POST["telefono_studente"])) {
					echo("<h3>Telefono non valido</h3>");
					return;
				}
			} else {
				$telefono = null;
			}


			$anno_immatricolazione = $_POST["anno_immatricolazione"];
			if ($anno_immatricolazione!="") {
				if (!validaAnnoImmatricolazione(intval($anno_immatricolazione))) {
					echo("<h3>Anno immatricolazione non valido</h3>");
					return;
				}
				$anno_immatricolazione = intval($anno_immatricolazione);
			} else {
				$anno_immatricolazione = null;
			}


			$codice = $_POST["codice"];
			if ($codice!="") {
				if (!validaCodiceStudente($codice)) {
					echo("<h3>Codice studente non valido</h3>");
					return;
				}
			} else {
				$codice = null;
			}

			$con->beginTransaction();
			$sql = "call registrazione_studente(:email, :password, :nome, :cognome, :telefono, :codice, :anno)";
			$stmt = $con->prepare($sql);

			$stmt->bindParam(":email", $_POST["email_studente"], PDO::PARAM_STR);
			$stmt->bindParam(":password", $_POST["password_studente"], PDO::PARAM_STR);
			$stmt->bindParam(":nome", $_POST["nome_studente"], PDO::PARAM_STR);
			$stmt->bindParam(":cognome", $_POST["cognome_studente"], PDO::PARAM_STR);
			$stmt->bindParam(":telefono", $telefono, PDO::PARAM_STR);
			$stmt->bindParam(":codice", $codice, PDO::PARAM_STR);
            $stmt->bindParam(":anno", $anno_immatricolazione, PDO::PARAM_INT);

			$stmt->execute();

			echo ("<h2>Registrazione avvenuta con successo</h2><br>");
			echo ("<a href='login.php'>Vai alla pagina di login</a>");
			

            $con->commit();

			insertLog("Nuovo utente inserito");


		}catch (PDOException $e) {
			echo("<h3>Problemi nella registrazione</h3>");
			$con->rollBack();
		}
	}


    function validaEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

	function validaStringaSenzaNumeri($stringa) {
		return ctype_alpha($stringa);
	}

	function validaTelefono($telefono) {
		return (strlen($telefono)==10 && ctype_digit($telefono));
	}

	function validaCodiceStudente($codice) {
		return strlen($codice)==16;
	}

	function validaAnnoImmatricolazione($anno) {
		return filter_var($anno, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1980, "max_range"=>2024)));
	}


?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../css/registrazione.css">
	</head>
	<body>
		<div class="container">
			<h2>Registrati in ESQL</h2>
		</div>
		<div class="container">
			<div>
				<form action="registrazione.php" method="post">
					<div>
						<h3>Registrazione studente</h3>
					</div>
					<div>
						<label for="email_studente">Email</label>
						<input type="text" name="email_studente" required />
					</div>
					<div>
						<label for="password_studente">Password</label>
						<input type="password" name="password_studente" required />
					</div>
					<div>
						<label for="nome_studente">Nome</label>
						<input type="text" name="nome_studente" required />
					</div>
					<div>
						<label for="cognome_studente">Cognome</label>
						<input type="text" name="cognome_studente" required />
					</div>
					<div>
						<label for="codice">Codice 16 cifre</label>
						<input type="text" name="codice" required />
					</div>
					<div>
						<label for="telefono_studente">Telefono</label>
						<input type="text" name="telefono_studente" />
					</div>
					<div>
						<label for="anno_immatricolazione">Anno Immatricolazione</label>
						<input type="text" name="anno_immatricolazione" />
					</div>
					<div>
						<input type="submit" name="registra_studente" value="Registrati" />
					</div>
				</form>
			</div>
			<div>
				<form action="registrazione.php" method="post">
					<div>
						<h3>Registrazione docente</h3>
					</div>
					<div>
						<label for="email_docente">Email</label>
						<input type="text" name="email_docente" required />
					</div>
					<div>
						<label for="password_docente">Password</label>
						<input type="password" name="password_docente" required />
					</div>
					<div>
						<label for="nome_docente">Nome</label>
						<input type="text" name="nome_docente" required />
					</div>
					<div>
						<label for="cognome_docente">Cognome</label>
						<input type="text" name="cognome_docente" required />
					</div>
					<div>
						<label for="telefono_docente">Telefono</label>
						<input type="text" name="telefono_docente" />
					</div>
					<div>
						<label for="dipartimento">Dipartimento</label>
						<input type="text" name="dipartimento" />
					</div>
					<div>
						<label for="corso">Corso</label>
						<input type="text" name="corso" />
					</div>
					<div>
						<input type="submit" name="registra_docente" value="Registrati" />
					</div>
				</form>
			</div>
			<?php
				if (isset($_POST["registra_studente"])) {
					registraStudente();
				}
				if (isset($_POST["registra_docente"])) {
					registraDocente();
				}
			?>
		</div>
	</body>
</html>