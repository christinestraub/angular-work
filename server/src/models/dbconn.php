<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function connect_db() {
    $server = 'localhost';
    $user = 'root';
    $pass = '';
    $database = 'ccc';
    $connection = new mysqli($server, $user, $pass, $database);

    return $connection;
}