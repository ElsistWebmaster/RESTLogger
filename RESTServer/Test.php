<?php

// *****************************************************************************
// Project		: PTP145A100
// Programmer	: Sergio Bertana
// Date			: 21/06/2018
// *****************************************************************************
// Script per eseguire test.
// http://www.slimline.altervista.org/Ptp145a100/Test.php
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
// ESECUZIONE TEST
// -----------------------------------------------------------------------------
// Test script.

echo "Data: ".gmdate("d-H:i", MySQLToEpochTime('2018-06-21 08:15:00'));

?>

