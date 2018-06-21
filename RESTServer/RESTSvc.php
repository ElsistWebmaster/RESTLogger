<?php

// *****************************************************************************
// Project		: PTP145A100
// Programmer	: Sergio Bertana
// Date			: 21/06/2018
// *****************************************************************************
// Script eseguito da sistema SlimLine da FB "RESTClient". Viene ricevuta
// una richiesta in HTTP POST il messaggio di richiesta puo' essere un heartbeat
// in questo caso non contiene dati ma ha solo l'header. Oppure un messaggio con
// dati a cui dopo l'header seguono i dati.
// -----------------------------------------------------------------------------
// Ecco alcuni esempi di messaggi REST ricevuti.

// {"MID":1234, "ST":0, "UID":10879070, "MV":"1.0", "FIFO":[{"Date":"09/06/2018 08:20:00", "Read":"WVariable"}]}
// {"MID":1234, "ST":0, "UID":10879070, "MV":"1.0", "FIFO":[{"Date":"09/06/2018 08:20:00","Write":"WVariable", "Value":100}]}

// Per il test da browser è possibile modificare la ricezione dei parametri.
// Ecco alcuni link possibili da browser.

// http://www.slimline.altervista.org/Ptp145a100/RESTSvc.php?Post={"MID":1234, "ST":0, "UID":10879070, "MV":"1.0", "FIFO":[{"Date":"09/06/2018 08:20:00", "Value":{"Read":"RVariable"}}]}
// http://www.slimline.altervista.org/Ptp145a100/RESTSvc.php?Post={"MID":1234, "ST":0, "UID":10879070, "MV":"1.0", "FIFO":[{"Date":"09/06/2018 08:20:00", "Value":{"Write":"WVariable", "Value":100}}]}
// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------
// INCLUSIONE FILE
// -----------------------------------------------------------------------------
// Eseguo inclusione files.

$HomeDir=substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], "/")); //Rilevo Home directory
require_once $HomeDir."/ezSQL/ez_sql_core.php"; //Include ezSQL core
require_once $HomeDir."/ezSQL/ez_sql_pdo.php"; //Database PDO
require_once $HomeDir."/Include/Include.php"; //Inclusioni generali
require_once $HomeDir."/Include/CreateTables.php"; //Creazione tabelle database

// -----------------------------------------------------------------------------
// MODIFICA PER TESTARE RICHIESTA REST IN GET
// -----------------------------------------------------------------------------
// Per testare lo script da browser è possibile passare i parametri in GET.

if (isset($_REQUEST['Post']))
	$RxMessage=$_REQUEST['Post']; //Messaggio REST ricevuto
else
	$RxMessage=file_get_contents("php://input"); //Messaggio REST ricevuto

//echo $RxMessage;

// -----------------------------------------------------------------------------
// OPERAZIONI SCHEDULATE
// -----------------------------------------------------------------------------
// Lo script è eseguito ad ogni richiesta REST dei sistemi, utilizzo esecuzione
// per gestire operazioni schedulate.
// -----------------------------------------------------------------------------
// Elimino da tabella spionaggio dati più vecchi di 5 minuti.

$GLOBALS['Db']->query("DELETE FROM ".SPYDATA." WHERE DateTime < '".EpochToMySQLTime(GetTimeNow(false)-(5*60))."'");

// Elimino da tabella dati REST dati più vecchi di 7 giorni.

$GLOBALS['Db']->query("DELETE FROM ".RESTDATA." WHERE DateTime < '".EpochToMySQLTime(GetTimeNow(false)-(7*24*3600))."'");

// -----------------------------------------------------------------------------
// CONTROLLO RICHIESTA IN ARRIVO
// -----------------------------------------------------------------------------
// Acquisisco richiesta JSON ricevuta in POST.

$RESTRx=json_decode($RxMessage, true); //Converto in associative array
$RESTTx=array(); //Inizializzo array risposta REST

// La richiesta deve contenere i campi, MID, ST, UID, MV. Se errore esco.

if ($RESTRx == null ) exit("Wrong REST parameters, Received:[{$RxMessage}]");
if (!isset($RESTRx['MID'])) exit("MID not in message, Received:[{$RxMessage}]");
if (!isset($RESTRx['ST'])) exit("ST not in message, Received:[{$RxMessage}]");
if (!isset($RESTRx['UID'])) exit("UID not in message, Received:[{$RxMessage}]");
if (!isset($RESTRx['MV'])) exit("MV not in message, Received:[{$RxMessage}]");

// Controllo se campi numerici sono corretti.

if (!is_numeric($RESTRx['MID'])) exit("Wrong system MID, Received:[{$RxMessage}]");
if (!is_numeric($RESTRx['UID'])) exit("Wrong system UID, Received:[{$RxMessage}]");

// Per ogni sistema (Riconoscibile dal suo "UID") esiste un record nel database.
// Se sistema non presente in tabella lo aggiungo.

$GLOBALS['St']['SIDx']=$GLOBALS['Db']->get_row("SELECT * FROM ".SISTEMIDX." WHERE UID = {$RESTRx['UID']}");
if ($GLOBALS['St']['SIDx'] == NULL)
{
	$GLOBALS['Db']->query("INSERT INTO ".SISTEMIDX." (UID) VALUES ({$RESTRx['UID']})");
	$GLOBALS['St']['SIDx']=$GLOBALS['Db']->get_row("SELECT * FROM ".SISTEMIDX." WHERE UID = {$RESTRx['UID']}");
}

// Calcolo tempo di poll.

SpyData($RxMessage); //Eseguo spionaggio messaggio ricevuto
$GLOBALS['St']['SIDx']->RxMessage=$RxMessage; //Messaggio REST ricevuto
$GLOBALS['St']['SIDx']->DateTime=EpochToMySQLTime(GetTimeNow(false)); //Data/Ora aggiornamento
$GLOBALS['St']['SIDx']->PollTime=sprintf("%6.3f", GetuTime()-$GLOBALS['St']['SIDx']->Heartbeat); //Tempo poll sistema
$GLOBALS['St']['SIDx']->Heartbeat=GetuTime(); //Data/Ora ultimo heartbeat (UTC)

// -------------------------------------------------------------------------
// CONTROLLO ID MESSAGGIO
// -------------------------------------------------------------------------
// Controllo se ricevuto l'acknowledge dallo SlimLine del messaggio REST
// inviato precedentemente dal server. Controllo se il  MID ricevuto e'
// corretto (Successivo al MID del messaggio precedente).

if ((($RESTRx['MID']-$GLOBALS['St']['SIDx']->MID)&0xFFFF) == 1)
{
	// Ricevuto MID successivo messaggio corretto (Nessun messaggio andato
	// perso) utilizzo MID ricevuto.

	$GLOBALS['St']['SIDx']->MID=$RESTRx['MID']; //Message ID
}
else
{
	// Errore ricezione messaggi, occorre eseguire una resincronizzazione
	// sistema, viene inviato un numero random che sara' utilizzato dal
	// sistema come prossimo MID. Non utilizzo i dati ricevuti perche' potrebbe
	// trattarsi di un messaggio inviato da hacker.

	$GLOBALS['St']['SIDx']->MID=rand(0, 65535); //Message ID
	$GLOBALS['St']['SIDx']->Resyncs++; //REST resyncronizations
}

// -----------------------------------------------------------------------------
// ACQUISIZIONE DATI MESSAGGIO
// -----------------------------------------------------------------------------
// Controllo se messaggio contiene i dati FIFO o se heartbeat.

if (!isset($RESTRx['FIFO'])) goto SENDDATA; //E' un heartbeat

// Messaggio con dati.

$GLOBALS['St']['SIDx']->DateTime=EpochToMySQLTime(FIFOToEpochTime($RESTRx['FIFO'][0]['Date'])); //DateTime dati ricevuti

// -----------------------------------------------------------------------------
// GESTIONE VARIABILI RICEVUTE
// -----------------------------------------------------------------------------
// Nel campo "RxMessage" il sistema SlimLine invia le variabili in una stringa
// codificata JSON. Eseguo conteggio variabili ricevute, viene einviato al
// client conb la risposta.

foreach ($RESTRx['FIFO'][0]['Value'] as $Key => $Value)
{
	RESTData($GLOBALS['St']['SIDx']->DateTime, $Key, $Value);
}

// -------------------------------------------------------------------------
// INVIO DATI AL SISTEMA
// -------------------------------------------------------------------------
// Salvo dati in database.

SENDDATA:
$RESTTx=array_merge(array("MID" => $GLOBALS['St']['SIDx']->MID), $RESTTx);
$GLOBALS['St']['SIDx']->TxMessage=json_encode($RESTTx);

$GLOBALS['Db']->query("UPDATE ".SISTEMIDX." SET
	UID={$GLOBALS['St']['SIDx']->UID},
	MID={$GLOBALS['St']['SIDx']->MID},
	DateTime='{$GLOBALS['St']['SIDx']->DateTime}',
	Heartbeat={$GLOBALS['St']['SIDx']->Heartbeat},
	PollTime={$GLOBALS['St']['SIDx']->PollTime},
	Resyncs={$GLOBALS['St']['SIDx']->Resyncs},
	RxMessage='{$GLOBALS['St']['SIDx']->RxMessage}',
	TxMessage='{$GLOBALS['St']['SIDx']->TxMessage}'
WHERE ID = {$GLOBALS['St']['SIDx']->ID}");


// Compilo messaggio di risposta che inizia con il MID. Il valore ritornato
// e' calcolato sommando il valore di UID. In questo modo si garantisce che
// il sistema che riceve il messaggio possa verificalo utilizzando il suo
// unique ID.

exit($GLOBALS['St']['SIDx']->TxMessage);

?>

