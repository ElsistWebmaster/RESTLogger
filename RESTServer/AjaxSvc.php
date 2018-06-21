<?php

// *****************************************************************************
// Project		: PTP145A100
// Programmer	: Sergio Bertana
// Date			: 21/06/2018
// *****************************************************************************
// Script eseguito da pagina web "Home" su richiesta ajax.
// http://www.slimline.altervista.org/Swm771b000/AjaxSvc.php?Selector=Voltage
//
// Letteratura.
// http://www.html.it/articoli/chart-js-creare-grafici-interattivi/
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// INCLUSIONE FILE
// -----------------------------------------------------------------------------
// Eseguo inclusione files.

$HomeDir=substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], "/")); //Rilevo Home directory
require_once $HomeDir."/ezSQL/ez_sql_core.php"; //Include ezSQL core
require_once $HomeDir."/ezSQL/ez_sql_pdo.php"; //Database PDO
require_once $HomeDir."/Include/Include.php"; //Inclusioni generali

// -----------------------------------------------------------------------------
// CONTROLLO RICHIESTA IN ARRIVO
// -----------------------------------------------------------------------------
// La richiesta deve contenere i campi, UID, DOut. Se errore esco.

if (!isset($_REQUEST['Selector'])) exit("Wrong REST parameters");
$GLOBALS['St']['SID']=3407887; //System unique ID
$GLOBALS['St']['SID']=2; //System ID

// -----------------------------------------------------------------------------
// CREO ARRAY PER RITORNO VALORI
// -----------------------------------------------------------------------------
// Creo array per ritorno valori.

$Return=array
(
	// Valori per console spionaggio.

	"SpyConsole" => "", //Console spionaggio

	// Valori istanteaei da visualizzare.

	"Voltage" => 0, //Tensione linea
	"Frequency" => 0, //Frequenza linea
	"AcPower" => 0, //Potenza istantanea
	"PwFactor" => 0, //Fattore di potenza

	// Valori per gestione grafico

	"Label" => $_REQUEST['Selector'], //Nome variabile
	"Border" => "", //Colore linea
	"Area" => "", //Colore area
	"XAxis" => array(), //Riferimenti asse "X"
	"YAxis" => array(), //Valori asse "Y"
);

// Impostazione colori grafico.

switch($_REQUEST['Selector'])
{
	case "Voltage": $Return["Border"]="rgba(139, 195, 74, 1)"; $Return["Area"]="rgba(139, 195, 74, 0.2)"; break;
	case "Frequency": $Return["Border"]="rgba(195, 66, 63, 1)"; $Return["Area"]="rgba(195, 66, 63, 0.2)"; break;
	case "AcPower": $Return["Border"]="rgba(54, 162, 235, 1)"; $Return["Area"]="rgba(54, 162, 235, 0.2)"; break;
	case "PwFactor": $Return["Border"]="rgba(139, 195, 74, 1)"; $Return["Area"]="rgba(139, 195, 74, 0.2)"; break;
}

// -----------------------------------------------------------------------------
// ESEGUO LETTURA DATI PER SPIONAGGIO
// -----------------------------------------------------------------------------
// Ritorno records di spionaggio.

$DbRes=$GLOBALS['Db']->get_results("SELECT * FROM ".SPYDATA." ORDER BY ID DESC LIMIT 20");
if ($DbRes == NULL) $Return["SpyConsole"]="No records";
foreach ($DbRes as $Result) {$Return["SpyConsole"].="[".date("H:i:s", MySQLToEpochTime($Result->DateTime))."] ".$Result->Report."<br>";}

// -----------------------------------------------------------------------------
// ESEGUO LETTURA DATI PER VISUALIZZAZIONE
// -----------------------------------------------------------------------------
// Lettura ultimo record per ogni variabile utilizzata in visualizzazione.

$DbRow=$GLOBALS['Db']->get_row("SELECT Value FROM ".RESTDATA." WHERE (SID = {$GLOBALS['St']['SID']} AND Field = 'Voltage') ORDER BY ID DESC LIMIT 1");
if ($DbRow != NULL) $Return["Voltage"]=sprintf("%5.1f",$DbRow->Value);

$DbRow=$GLOBALS['Db']->get_row("SELECT Value FROM ".RESTDATA." WHERE (SID = {$GLOBALS['St']['SID']} AND Field = 'Frequency') ORDER BY ID DESC LIMIT 1");
if ($DbRow != NULL) $Return["Frequency"]=sprintf("%5.1f", $DbRow->Value);

$DbRow=$GLOBALS['Db']->get_row("SELECT Value FROM ".RESTDATA." WHERE (SID = {$GLOBALS['St']['SID']} AND Field = 'AcPower') ORDER BY ID DESC LIMIT 1");
if ($DbRow != NULL) $Return["AcPower"]=sprintf("%5.1f", $DbRow->Value);

$DbRow=$GLOBALS['Db']->get_row("SELECT Value FROM ".RESTDATA." WHERE (SID = {$GLOBALS['St']['SID']} AND Field = 'PwFactor') ORDER BY ID DESC LIMIT 1");
if ($DbRow != NULL) $Return["PwFactor"]=sprintf("%1.2f", $DbRow->Value);

// -----------------------------------------------------------------------------
// ESEGUO LETTURA DATI PER GRAFICO
// -----------------------------------------------------------------------------
// Per ogni sistema (Riconoscibile dal suo "SID") esiste un record nel database.

$DbRes=$GLOBALS['Db']->get_results("SELECT * FROM ".RESTDATA." WHERE SID = {$GLOBALS['St']['SID']} AND Field = '{$_REQUEST['Selector']}'");
if ($DbRes == NULL) exit("System not found");

// Eseguo loop inserimento riferimenti (Asse "X") e valori (Asse "Y").

foreach ($DbRes as $Result)
{
	array_push($Return["XAxis"], gmdate("d-H:i", MySQLToEpochTime($Result->DateTime))); //Inserisco riferimenti
	array_push($Return["YAxis"], sprintf("%6.2f", $Result->Value)); //Inserisco valori
}

echo json_encode($Return);
?>
