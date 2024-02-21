<?php

$dbServerName = "database";
$dbUserName = "root";
$dbPassword = "secret";
$dbName = "us-docker";

$dbConnection = new PDO(sprintf('mysql:host=%s;dbname=%s', $dbServerName, $dbName), $dbUserName, $dbPassword);
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

return $dbConnection;
