<?php

// *****************************************************************************
// Project		: PTP145A000
// Programmer	: Sergio Bertana
// Date			: 20/10/2017
// *****************************************************************************
// Inclusioni generali.
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// 	INIZIALIZZAZIONI
// -----------------------------------------------------------------------------
// Abilito visualizzazione degli errori.

ini_set('display_errors','On');
error_reporting(E_ALL);

// -----------------------------------------------------------------------------
// CREDENZIALI ACCESSO AL DATABASE
// -----------------------------------------------------------------------------
// Definire le credenziali di accesso al database.
// Occorre definire db_user, db_password, db_name, db_host:port

$DbRefs=array("Host" => "localhost", "User" => "User", "Password" => "Password", "Database" => "Database");
$GLOBALS['Db']=new ezSQL_pdo("mysql:host={$DbRefs["Host"]}; dbname={$DbRefs["Database"]}", $DbRefs["User"], $DbRefs["Password"]); //Database PDO

// Definizioni tabelle database.

define("SISTEMIDX", "Ptp145_SystemIDx"); //Tabella ID di sistema
define("RESTDATA", "Ptp145_RESTData"); //Tabella dati REST
define("SPYDATA", "Ptp145_SpyData"); //Tabella dati spionaggio

// Definizioni generali.

define("STORETIME", (30*60)); //Tempo di storicizzazione dati

// -----------------------------------------------------------------------------
// DEFINIZIONE VARIABILE GLOBALE DATI SISTEMA
// -----------------------------------------------------------------------------
// Array stato sistema contiene i dati per gestire il REST.

$GLOBALS['St']=array
(
	// Dati ricevuti dal sistema.

	"UID" => 0, //System unique ID
	"MID" => 0, //Message ID
	"MV" => "", //Message version
	"RPAck" => 0, //REST parameters acknowledged
	"Length" => 0, //Lunghezza record dati
	"Epoch" => 0, //Epoch time relativo al record dati
	"RxMessage" => "", //Messaggio ricevuto

	// Variabili inviate dal sistema.

	// Dati inviati al sistema SlimLine in risposta.

	"RPCount" => 0, //REST parameters counter 
	"TxMessage" => "", //Messaggio trasmesso

	// Dati statistici per debug.

	"RPError" => 0, //REST parameters error
	"Resyncs" => 0, //REST resyncronizations
	"PollTime" => 0, //Tempo poll sistema
	"Heartbeat" => GetuTime(), //Data/Ora ultimo heartbeat (UTC)
	"TxPars" => 0, //Numero parametri trasmessi
);

// *****************************************************************************
// FUNZIONE "GetuTime()"
// *****************************************************************************
// microtime() returns a string in the form "msec sec", where sec is the current
// time measured in the number of seconds since the Unix epoch, and msec is the
// number of microseconds that have elapsed since sec expressed in seconds.
//
// La funzione non ha parametri.
//
// La funzione ritorna
// Valore tempo in mSec da Epoch time.
// -----------------------------------------------------------------------------

function GetuTime()
{
	list($uSec, $Sec)=explode(" ", microtime()); 
	return((float)$uSec+(float)$Sec); 
}

// *****************************************************************************
// FUNZIONE "GetTimeNow($Local)"
// *****************************************************************************
// Ritorna ora in Unix epoch time.
//
// Parametri funzione:
// $Local: false, Ritorna GMT, true, Ritorna ora locale
//
// La funzione ritorna epoch time.
// -----------------------------------------------------------------------------

function GetTimeNow($Local)
{
	if (!$Local) return(time()); //Torno tempo in UTC
	return(time()+date("Z")); //Torno tempo locale
}

// *****************************************************************************
// FUNZIONE "UnixToMySQLTime($TimeStamp)"
// *****************************************************************************
// Questa funzione ritorna valore data/ora nel formato adatto a database MySQL.
//
// Parametri funzione:
// $TimeStamp: Time stamp data e ora (In UTC).
//
// La funzione ritorna Data ora nel formato MySQL.
// -----------------------------------------------------------------------------

function UnixToMySQLTime($TimeStamp)
{
	return(gmdate("Y-m-d H:i:s", $TimeStamp));
}

// *****************************************************************************
// FUNZIONE "MySQLToUnixTime($DateTime)"
// *****************************************************************************
// Questa funzione calcola il tempo in formato Unix stamp da una data presente
// nel database MySQL.
//
// Parametri funzione:
// $DateTime: Data e ora in formato MySQL.
//
// La funzione ritorna il tempo unix.
// -----------------------------------------------------------------------------

function MySQLToUnixTime($DateTime)
{
	list($Year, $Month, $Day, $Hour, $Minute, $Second)=sscanf($DateTime, "%04d-%02d-%02d %02d:%02d:%02d");
	return(gmmktime($Hour, $Minute, $Second, $Month, $Day, $Year));
}

// *****************************************************************************
// FUNZIONE "CkReqPars($AList)"
// *****************************************************************************
// Questa funzione esegue il controllo se sono definiti i parametri "$_REQUEST".
//
// Parametri funzione:
// $AList: Lista parametri da controllare
//
// La funzione ritorna, false: Errore parametri. true: Parametri corretti.
// -----------------------------------------------------------------------------

function CkReqPars($AList)
{
	foreach ($AList as $Id => $Field) {if (!isset($_REQUEST[$Field])) return(false);} //Errore parametri
	return(true); //Parametri corretti
}

// *****************************************************************************
// FUNZIONE "RESTData($TimeStamp, $Field, $Value)"
// *****************************************************************************
// Funzione di memorizzazione campo in tabella dati REST.
//
// Parametri funzione:
// $TimeStamp: Time stamp data e ora (In UTC).
// $Field: Report spionaggio.
// $Value: Report spionaggio.
//
// La funzione non prevede ritorni
// -----------------------------------------------------------------------------

function RESTData($TimeStamp, $Field, $Value)
{
	// Lettura da database di ultimo valore memorizzato se nessuno inserisco.

	$DbRow=$GLOBALS['Db']->get_row("SELECT * FROM ".RESTDATA." WHERE (UID = {$GLOBALS['St']['UID']} AND Field = '{$Field}') ORDER BY ID DESC LIMIT 1");
	if ($DbRow == NULL) goto INSERTRECORD;

	// Controllo se timestamp record minore del tempo di memorizzazione.

	if (($TimeStamp-STORETIME) < MySQLToUnixTime($DbRow->DateTime))
		{$GLOBALS['Db']->query("UPDATE ".RESTDATA." SET Value=".(($DbRow->Value+$Value)/2)." WHERE ID = {$DbRow->ID}"); return;}

	// Inserisco nuovo record in database.

	INSERTRECORD:
	SpyData("RESTData: Data:".UnixToMySQLTime($TimeStamp).", {$Field}=, {$Value}"); //Salvo spionaggio
	$GLOBALS['Db']->query("INSERT INTO ".RESTDATA." (UID, DateTime, Field, Value) VALUES ({$GLOBALS['St']['UID']}, '".UnixToMySQLTime($TimeStamp)."', '{$Field}', '{$Value}')");
}

// *****************************************************************************
// FUNZIONE "SpyData($Report)"
// *****************************************************************************
// Funzione di memorizzazione report di spionaggio.
//
// Parametri funzione:
// $Report: Report spionaggio.
//
// La funzione non prevede ritorni
// -----------------------------------------------------------------------------

function SpyData($Report)
{
	$GLOBALS['Db']->query("INSERT INTO ".SPYDATA." (UID, DateTime, Report) VALUES ({$GLOBALS['St']['UID']}, '".UnixToMySQLTime(GetTimeNow(false))."', '{$Report}')");
}

?>
