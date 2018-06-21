<?php

// *****************************************************************************
// Project		: PTP145A100
// Programmer	: Sergio Bertana
// Date			: 21/06/2018
// *****************************************************************************
// Inclusioni generali.
// -----------------------------------------------------------------------------

// Error report
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------------------
// CREDENZIALI ACCESSO AL DATABASE
// -----------------------------------------------------------------------------
// Definire le credenziali di accesso al database.
// Occorre definire db_user, db_password, db_name, db_host:port

$DbRefs=array("Host" => "localhost", "User" => "user", "Password" => "password", "Database" => "database");
$GLOBALS['Db']=new ezSQL_pdo("mysql:host={$DbRefs["Host"]}; dbname={$DbRefs["Database"]}", $DbRefs["User"], $DbRefs["Password"]); //Database PDO

define("SISTEMIDX", "Ptp145_SystemIDx"); //Tabella ID di sistema
define("RESTDATA", "Ptp145_RESTData"); //Tabella dati REST
define("SPYDATA", "Ptp145_SpyData"); //Tabella dati spionaggio

// Definizioni generali.

define("STORETIME", (30*60)); //Tempo di storicizzazione dati

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
// FUNZIONE "EpochToMySQLTime($Epoch)"
// *****************************************************************************
// Questa funzione ritorna valore data/ora nel formato adatto a database MySQL.
//
// Parametri funzione:
// $Epoch: Epoch time (In UTC).
//
// La funzione ritorna Data ora nel formato MySQL.
// -----------------------------------------------------------------------------

function EpochToMySQLTime($Epoch)
{
	return(gmdate("Y-m-d H:i:s", $Epoch));
}

// *****************************************************************************
// FUNZIONE "MySQLToEpochTime($DateTime)"
// *****************************************************************************
// Questa funzione ritorna il valore di Epoch da una data formato MySQL.
//
// Parametri funzione:
// $DateTime: Data e ora in formato MySQL.
//
// La funzione ritorna il tempo unix.
// -----------------------------------------------------------------------------

function MySQLToEpochTime($DateTime)
{
	list($Year, $Month, $Day, $Hour, $Minute, $Second)=sscanf($DateTime, "%04d-%02d-%02d %02d:%02d:%02d");
	return(gmmktime($Hour, $Minute, $Second, $Month, $Day, $Year));
}

// *****************************************************************************
// FUNZIONE "FIFOToEpochTime($DateTime)"
// *****************************************************************************
// Questa funzione ritorna il valore di Epoch da una data formato FIFO.
//
// Parametri funzione:
// $DateTime: Data e ora in formato MySQL.
//
// La funzione ritorna il tempo unix.
// -----------------------------------------------------------------------------

function FIFOToEpochTime($DateTime)
{
	list($Day, $Month, $Year, $Hour, $Minute, $Second)=sscanf($DateTime, "%02d/%02d/%04d %02d:%02d:%02d");
	return(gmmktime($Hour, $Minute, $Second, $Month, $Day, $Year));
}

// *****************************************************************************
// FUNZIONE "RESTData($DateTime, $Field, $Value)"
// *****************************************************************************
// Funzione di memorizzazione campo in tabella dati REST.
//
// Parametri funzione:
// $DateTime: Data/Ora in formato MySQL.
// $Field: Report spionaggio.
// $Value: Report spionaggio.
//
// La funzione non prevede ritorni
// -----------------------------------------------------------------------------

function RESTData($DateTime, $Field, $Value)
{
	// Lettura da database di ultimo valore memorizzato se nessuno inserisco.

	$DbRow=$GLOBALS['Db']->get_row("SELECT * FROM ".RESTDATA." WHERE (SID = {$GLOBALS['St']['SIDx']->ID} AND Field = '{$Field}') ORDER BY ID DESC LIMIT 1");
	if ($DbRow == NULL) goto INSERTRECORD;

	// Controllo se timestamp record minore del tempo di memorizzazione.

	if ((MySQLToEpochTime($DateTime)-STORETIME) < MySQLToEpochTime($DbRow->DateTime))
	{$GLOBALS['Db']->query("UPDATE ".RESTDATA." SET Value=".(($DbRow->Value+$Value)/2)." WHERE ID = {$DbRow->ID}"); return;}

	// Inserisco nuovo record in database.

	INSERTRECORD:
	$GLOBALS['Db']->query("INSERT INTO ".RESTDATA." (SID, DateTime, Field, Value) VALUES ({$GLOBALS['St']['SIDx']->ID}, '$DateTime', '{$Field}', '{$Value}')");
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
	$GLOBALS['Db']->query("INSERT INTO ".SPYDATA." (SID, DateTime, Report) VALUES ({$GLOBALS['St']['SIDx']->ID}, '".EpochToMySQLTime(GetTimeNow(false))."', '{$Report}')");
}


?>
