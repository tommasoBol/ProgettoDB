drop database if exists esqldb;
create database if not exists esqldb;
use esqldb;

create table utente (
	
    email varchar(100) not null,
    pass varchar(30) not null,
    nome varchar(30),
    cognome varchar(50),
    telefono char(10),
    primary key(email)

);

create table docente (

	email_utente varchar(100) not null,
    dipartimento varchar(50),
    corso varchar(50),
    primary key(email_utente),
    foreign key(email_utente) references utente(email)

);

create table studente (

	email_utente varchar(100) not null,
    codice char(16) unique not null,
    anno_immatricolazione int,
    primary key(email_utente),
    foreign key(email_utente) references utente(email)
	
);


create table tabella (
	
    nome varchar(30) not null,
    data_creazione date,
    num_righe int default 0,
    email_docente varchar(100) not null,
    primary key(nome),
    foreign key(email_docente) references docente(email_utente)
	
);


create table attributo (

	nome varchar(30) not null,
    nome_tabella varchar(30) not null,
    tipo varchar(20) not null,
    is_primary_key boolean,
    primary key(nome, nome_tabella),
    foreign key(nome_tabella) references tabella(nome)

);


create table chiave_esterna (

	attributo_referenziante varchar(30),
    tabella_attributo_referenziante varchar(30),
    attributo_referenziato varchar(30),
    tabella_attributo_referenziato varchar(30),
    primary key(attributo_referenziante, tabella_attributo_referenziante),
    foreign key (attributo_referenziante, tabella_attributo_referenziante) references attributo(nome, nome_tabella),
    foreign key (attributo_referenziato, tabella_attributo_referenziato) references attributo(nome, nome_tabella)

);

create table test (

	titolo varchar(50), 
    data_creazione date,
    visualizza_risposte boolean default false,
    foto blob,
    email_docente varchar(100) not null,
    primary key(titolo),
    foreign key(email_docente) references docente(email_utente)

);


create table quesito (

	numero int,
    titolo_test varchar(50),
    difficolta enum("Basso", "Medio", "Alto") default "Basso",
    num_risposte int default 0,
    descrizione varchar(200),
    primary key(numero, titolo_test),
    foreign key(titolo_test) references test(titolo)

);

create table quesito_risposta_chiusa (

	numero_quesito int,
    titolo_test_quesito varchar(50),
    primary key(numero_quesito, titolo_test_quesito),
    foreign key(numero_quesito, titolo_test_quesito) references quesito(numero, titolo_test)

);

create table quesito_codice (

	numero_quesito int,
    titolo_test_quesito varchar(50),
    primary key(numero_quesito, titolo_test_quesito),
    foreign key(numero_quesito, titolo_test_quesito) references quesito(numero, titolo_test)
    
);


create table soluzione (

	numero int,
    testo varchar(300),
    numero_quesito int,
    titolo_test_quesito varchar(50),
    primary key(numero, numero_quesito, titolo_test_quesito),
    foreign key(numero_quesito, titolo_test_quesito) references quesito_codice(numero_quesito, titolo_test_quesito)

); 


create table opzione_risposta (

	numero int,
    testo varchar(100),
    is_correct boolean default false,
    numero_quesito int,
    titolo_test_quesito varchar(50),
    primary key(numero,numero_quesito,titolo_test_quesito),
    foreign key(numero_quesito, titolo_test_quesito) references quesito_risposta_chiusa(numero_quesito, titolo_test_quesito)

);

create table riferimento (

	numero_quesito int,
    titolo_test varchar(50),
    nome_tabella varchar(30),
    primary key(numero_quesito, titolo_test, nome_tabella),
    foreign key(numero_quesito, titolo_test) references quesito(numero, titolo_test),
    foreign key(nome_tabella) references tabella(nome)

);


create table completamento (

	codice_studente char(16),
    titolo_test varchar(50),
    data_prima_risposta date,
    data_ultima_risposta date,
    stato enum("Aperto","InCompletamento","Concluso") default "Aperto",
    primary key(codice_studente, titolo_test),
    foreign key(codice_studente) references studente(codice),
    foreign key(titolo_test) references test(titolo)

);

create table risposta_chiusa (

	id int auto_increment,
    esito boolean,
    codice_studente char(16),
    numero_opzione int,
    numero_quesito int,
    titolo_test_quesito varchar(50),
    primary key(id),
    foreign key (codice_studente) references studente(codice),
    foreign key (numero_opzione, numero_quesito, titolo_test_quesito) references opzione_risposta(numero, numero_quesito, titolo_test_quesito)

);


create table risposta_codice (

	id int auto_increment,
    esito boolean,
    testo varchar(300),
    numero_quesito int,
    titolo_test_quesito varchar(50),
    codice_studente char(16),
    primary key (id),
    foreign key (codice_studente) references studente(codice),
    foreign key (numero_quesito, titolo_test_quesito) references quesito_codice(numero_quesito, titolo_test_quesito)

);

create table messaggio (
	
    id int auto_increment,
    titolo varchar(50),
    testo varchar(1000),
    data_inserimento date,
    titolo_test varchar(50),
    utente_mittente varchar(100),
    primary key(id),
    foreign key (titolo_test) references test(titolo),
    foreign key(utente_mittente) references utente(email)

);


create table destinatario (

	id_messaggio int,
    utente_destinatario varchar(100),
    primary key(id_messaggio, utente_destinatario),
    foreign key(id_messaggio) references messaggio(id),
    foreign key(utente_destinatario) references utente(email)

);
	



/* 	LOGIN E REGISTRAZIONE  */

DELIMITER $
create procedure inserimento_utente(in new_email varchar(100), in pw varchar(30), in new_name varchar(30), in new_surname varchar(50), in new_cell char(10))
	begin 
        insert into utente(email, pass, nome, cognome, telefono) values(new_email, pw, new_name, new_surname, new_cell);
    end $
DELIMITER ;

DELIMITER |
create procedure registrazione_docente(in new_email varchar(100), in pw varchar(30), in new_name varchar(30), in new_surname varchar(50), in new_cell char(10), in dip varchar(50), in cor varchar(50))
	begin
		call inserimento_utente(new_email, pw, new_name, new_surname, new_cell);
		insert into docente(email_utente, dipartimento, corso) values(new_email, dip, cor);
    end |
DELIMITER ;


DELIMITER $
create procedure registrazione_studente(in new_email varchar(100), in pw varchar(30), in new_name varchar(30), in new_surname varchar(50), in new_cell char(10), in cod char(16), in anno int)
	begin
		call inserimento_utente(new_email, pw, new_name, new_surname, new_cell);
		insert into studente(email_utente, codice, anno_immatricolazione) values(new_email, cod, anno);
    end $
DELIMITER ;

DELIMITER $
create procedure login_docente(in new_email varchar(100), in pw varchar(30))
	begin
		select count(*)
        from docente join utente on docente.email_utente=utente.email
        where email_utente = new_email AND pass=pw;
    end $
DELIMITER ;

DELIMITER $
create procedure login_studente(in new_email varchar(100), in pw varchar(30))
	begin
		select count(*)
        from studente 
        join utente on studente.email_utente=utente.email
        where email_utente = new_email AND pass=pw;
    end $
DELIMITER ;




/*	TABELLE	*/


DELIMITER $
create procedure get_table_from_docente(in email varchar(100))
	begin
		select nome
        from tabella
        where email_docente=email;
    end $
DELIMITER ;

DELIMITER $
create procedure get_attributes_from_table(in tnome varchar(30))
	begin
		select *
        from attributo
        where nome_tabella = tnome;
    end $
DELIMITER ;

DELIMITER $
create procedure get_primary_key_attributes(in tnome varchar(30))
	begin
		select nome
        from attributo
        where is_primary_key = true and nome_tabella= tnome;
	end $
DELIMITER ;


DELIMITER $
create procedure get_foreign_keys_for_table(in tnome varchar(30))
	begin
		select attributo_referenziante, attributo_referenziato, tabella_attributo_referenziato
        from chiave_esterna
        where tabella_attributo_referenziante = tnome;
	end $
DELIMITER ;

DELIMITER $
create procedure get_foreign_keys_for_attribute(in anome varchar(30), in tnome varchar(30))
	begin
		select *
        from chiave_esterna
        where attributo_referenziante=anome and tabella_attributo_referenziante=tnome;
    end $
DELIMITER ;



/*	QUESITI	*/


DELIMITER $
create procedure create_quesito(in numero_q int, in titolo_t varchar(50), in dif varchar(20), in descriz varchar(200)) 
	begin
		insert into quesito(numero, titolo_test, difficolta, descrizione) values(numero_q, titolo_t, dif, descriz);
	end $
DELIMITER ;


DELIMITER $
create procedure create_quesito_risposta_chiusa(in numero_q int, in titolo_t varchar(50), in dif varchar(20), in descriz varchar(200))
	begin
		call create_quesito(numero_q, titolo_t, dif, descriz);
        insert into quesito_risposta_chiusa(numero_quesito, titolo_test_quesito) values(numero_q, titolo_t);
    end $
DELIMITER ;


DELIMITER $
create procedure create_quesito_codice(in numero_q int, in titolo_t varchar(50), in dif varchar(20), in descriz varchar(200))
	begin
		call create_quesito(numero_q, titolo_t, dif, descriz);
        insert into quesito_codice(numero_quesito, titolo_test_quesito) values(numero_q, titolo_t);
    end $
DELIMITER ;



/*	MESSAGGI	*/

DELIMITER $
create procedure get_messaggi_from_destinatario(in email_destinatario varchar(100))
	begin
		select * 
        from messaggio join destinatario on messaggio.id=destinatario.id_messaggio
        where destinatario.utente_destinatario = email_destinatario;
    end $
DELIMITER ;







DELIMITER $
create trigger after_insert_risposta_codice
after insert on risposta_codice
for each row
	begin
		declare flag int default 0;
        declare risposte_chiuse_inserite int default 0;
        declare risposte_codice_inserite int default 0;
        declare numero_quesiti_per_test int default 0;
        set flag = (select count(*) from completamento where codice_studente=NEW.codice_studente and titolo_test=NEW.titolo_test_quesito);
        if flag>0 then
			update completamento
            set data_ultima_risposta = CURDATE()
            where codice_studente = NEW.codice_studente and titolo_test = NEW.titolo_test_quesito;
		else
			insert into completamento(codice_studente, titolo_test, data_prima_risposta, data_ultima_risposta, stato) values(NEW.codice_studente, NEW.titolo_test_quesito, CURDATE(), CURDATE(),  "InCompletamento");
        end if;
        
        set numero_quesiti_per_test = (select count(*) from quesito where titolo_test=NEW.titolo_test_quesito);
		set risposte_chiuse_inserite = (select count(distinct numero_quesito, titolo_test_quesito) from risposta_chiusa where codice_studente = NEW.codice_studente and titolo_test_quesito=NEW.titolo_test_quesito);
        set risposte_codice_inserite = (select count(distinct numero_quesito, titolo_test_quesito) from risposta_codice where codice_studente = NEW.codice_studente and titolo_test_quesito=NEW.titolo_test_quesito);
        
		if risposte_chiuse_inserite+risposte_codice_inserite = numero_quesiti_per_test then
			update completamento
            set stato="Concluso"
            where codice_studente = NEW.codice_studente and titolo_test=NEW.titolo_test_quesito;
        end if;
        
        update quesito
        set num_risposte = num_risposte+1
        where numero=NEW.numero_quesito and titolo_test=NEW.titolo_test_quesito;
    end $
DELIMITER ;

DELIMITER $
create trigger after_insert_risposta_chiusa
after insert on risposta_chiusa
for each row
	begin
		declare flag int default 0;
        declare risposte_chiuse_inserite int default 0;
        declare risposte_codice_inserite int default 0;
        declare numero_quesiti_per_test int default 0;
        set flag = (select count(*) from completamento where codice_studente=NEW.codice_studente and titolo_test=NEW.titolo_test_quesito);
        if flag>0 then
			update completamento
            set data_ultima_risposta = CURDATE()
            where codice_studente = NEW.codice_studente and titolo_test = NEW.titolo_test_quesito;
		else
			insert into completamento(codice_studente, titolo_test, data_prima_risposta, data_ultima_risposta,  stato) values(NEW.codice_studente, NEW.titolo_test_quesito, CURDATE(), CURDATE(), "InCompletamento");
        end if;
        
        set numero_quesiti_per_test = (select count(*) from quesito where titolo_test=NEW.titolo_test_quesito);
		set risposte_chiuse_inserite = (select count(distinct numero_quesito, titolo_test_quesito) from risposta_chiusa where codice_studente = NEW.codice_studente and titolo_test_quesito=NEW.titolo_test_quesito);
        set risposte_codice_inserite = (select count(distinct numero_quesito, titolo_test_quesito) from risposta_codice where codice_studente = NEW.codice_studente and titolo_test_quesito=NEW.titolo_test_quesito);
        
        if risposte_chiuse_inserite+risposte_codice_inserite = numero_quesiti_per_test then
			update completamento
            set stato="Concluso"
            where codice_studente = NEW.codice_studente and titolo_test=NEW.titolo_test_quesito;
        end if;

        update quesito
        set num_risposte = num_risposte+1
        where numero=NEW.numero_quesito and titolo_test=NEW.titolo_test_quesito;
    end $
DELIMITER ;

DELIMITER $
create trigger calcola_esito_risposta_chiusa
before insert on risposta_chiusa
for each row
	begin
		declare flag_esito int default 0;
		set flag_esito = (select count(*) from opzione_risposta where numero=NEW.numero_opzione and numero_quesito=NEW.numero_quesito and titolo_test_quesito=NEW.titolo_test_quesito and is_correct=true);
        if flag_esito>0 then
            set NEW.esito = true;
		else set NEW.esito = false;
        end if;
    end $
DELIMITER ;

DELIMITER $
create trigger after_update_visualizza_risposte
after update on test
for each row
	begin
		declare new_visualizza_risposte boolean;
        declare old_visualizza_risposte boolean;
        set new_visualizza_risposte = NEW.visualizza_risposte;
        set old_visualizza_risposte = OLD.visualizza_risposte;
        if (new_visualizza_risposte <> old_visualizza_risposte) then
			update completamento
            set stato="Concluso"
            where titolo_test = NEW.titolo;
        end if;
    end $
DELIMITER ;




create view statistica1 as
select codice_studente, count(*) as conteggio
from completamento
where stato="Concluso"
group by codice_studente
order by conteggio;


create view statistica2 as
with risposte_chiuse_inserite as
(select codice, count(codice_studente) as totale
from studente left join risposta_chiusa on studente.codice=risposta_chiusa.codice_studente
group by codice),
risposte_codice_inserite as
(select codice, count(codice_studente) as totale
from studente left join risposta_codice on studente.codice=risposta_codice.codice_studente
group by codice),
risposte_chiuse_corrette as
(select codice, count(codice_studente) as totale
from studente left join risposta_chiusa on studente.codice=risposta_chiusa.codice_studente and esito=true
group by codice),
risposte_codice_corrette as
(select codice, count(codice_studente) as totale
from studente left join risposta_codice on studente.codice=risposta_codice.codice_studente and esito=true
group by codice)
select risposte_chiuse_inserite.codice, ((risposte_chiuse_corrette.totale+risposte_codice_corrette.totale)/(risposte_chiuse_inserite.totale+risposte_codice_inserite.totale)) as punteggio
from risposte_chiuse_inserite join risposte_chiuse_corrette on risposte_chiuse_inserite.codice = risposte_chiuse_corrette.codice
								join risposte_codice_inserite on risposte_chiuse_inserite.codice=risposte_codice_inserite.codice
                                join risposte_codice_corrette on risposte_chiuse_inserite.codice=risposte_codice_corrette.codice
group by risposte_chiuse_inserite.codice
order by punteggio desc;


create view statistica3 as
select *
from quesito
order by num_risposte desc;



