<?php

// *****************************************************************************
// Project		: PTP145A100
// Programmer	: Sergio Bertana
// Date			: 21/06/2018
// *****************************************************************************
// Questo script se non presenti crea le tabelle in database.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// CREAZIONE TABELLA IDENTIFICAZIONE SISTEMI
// -----------------------------------------------------------------------------
// Questa tabella contiene i dati identificativi dei sistemi SlimLine.
// +--------------------+-----------+------------------------------------------+
// | Name               | Type      | Description                              |
// +--------------------+-----------+------------------------------------------+
// | ID                 | int       | Identificativo voce                      |
// | UID                | int       | Unique ID del sistema                    |
// | MID                | int       | ID messaggio                             |
// | DateTime           | datetime  | Data/Ora aggiornamento                   |
// | Heartbeat          | decimal   | Tempo di heartbeat (uS)                  |
// | PollTime           | decimal   | Tempo di poll (uS)                       |
// | Resyncs            | int       | Numero di risincronizzazioni             |
// | RxMessage          | text      | Messaggio REST ricevuto                  |
// | TxMessage          | text      | Messaggio REST inviato                   |
// +--------------------+-----------+------------------------------------------+

if ($GLOBALS['Db']->get_var("SHOW TABLES LIKE '".SISTEMIDX."'") != SISTEMIDX)
{
	$GLOBALS['Db']->query("CREATE TABLE ".SISTEMIDX."(
		ID int(10) NULL PRIMARY KEY AUTO_INCREMENT,
		UID int NULL,
		MID int NULL,
		DateTime datetime NULL,
		Heartbeat decimal(16,3) NULL,
		PollTime decimal(16,3) NULL,
		Resyncs int(5) NULL,
		RxMessage text NULL,
		TxMessage text NULL
	)CHARSET=latin1;");
}

// -----------------------------------------------------------------------------
// CREAZIONE TABELLA REST
// -----------------------------------------------------------------------------
// Questa tabella viene contiene i dati inviati in REST.
// +--------------------+-----------+------------------------------------------+
// | Name               | Type      | Description                              |
// +--------------------+-----------+------------------------------------------+
// | ID                 | int       | Identificativo voce                      |
// | SID                | int       | ID sistema (ID tabella SISTEMIDX)        |
// | DateTime           | datetime  | Data/Ora invio verso SlimLine            |
// | Field              | text      | Nome campo                               |
// | Value              | text      | Valore variabile                         |
// +--------------------+-----------+------------------------------------------+

// Controllo se tabella dati REST esiste, in caso contrario la creo.

if ($GLOBALS['Db']->get_var("SHOW TABLES LIKE '".RESTDATA."'") != RESTDATA)
{
	$GLOBALS['Db']->query("CREATE TABLE ".RESTDATA."(
			ID int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			SID int(10) NOT NULL,
			DateTime datetime NOT NULL,
			Field text NOT NULL,
			Value text NOT NULL
	)CHARSET=latin1;");
}

// -----------------------------------------------------------------------------
// CREAZIONE TABELLA SPIONAGGIO
// -----------------------------------------------------------------------------
// Questa tabella contiene i dati di spionaggio.
// +--------------------+-----------+------------------------------------------+
// | Name               | Type      | Description                              |
// +--------------------+-----------+------------------------------------------+
// | ID                 | int       | Identificativo voce                      |
// | SID                | int       | ID sistema (ID tabella SISTEMIDX)        |
// | DateTime           | datetime  | Data/Ora ricezione da SlimLine           |
// | Report             | text      | Report spionaggio                        |
// +--------------------+-----------+------------------------------------------+

if ($GLOBALS['Db']->get_var("SHOW TABLES LIKE '".SPYDATA."'") != SPYDATA)
{
	$GLOBALS['Db']->query("CREATE TABLE ".SPYDATA."(
			ID int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			SID int(10) NOT NULL,
			DateTime datetime NOT NULL,
			Report text NOT NULL
	)CHARSET=latin1;");
}

// -----------------------------------------------------------------------------
// DEBUG TABELLE DATABASE
// -----------------------------------------------------------------------------
// Ritorno elenco tabella database solo per testare la connessione.

// $DbTables=$Db->get_results("SHOW TABLES", ARRAY_N);
// $Db->debug(); //Print out last query and results

?>