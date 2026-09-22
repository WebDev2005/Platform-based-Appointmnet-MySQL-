<?php

/**
 * Database connection. Credentials come from the environment so nothing
 * secret lives in the repository. Railway's MySQL plugin already exports the
 * MYSQL* variables; DB_* are accepted as aliases for local development.
 */
function db_setting($names, $default = null) {
    foreach ((array) $names as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }
    return $default;
}

function getDB() {
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = db_setting(['MYSQLHOST', 'DB_HOST'], '127.0.0.1');
    $user = db_setting(['MYSQLUSER', 'DB_USER'], 'root');
    $pass = db_setting(['MYSQLPASSWORD', 'DB_PASS'], '');
    $db   = db_setting(['MYSQLDATABASE', 'DB_NAME'], 'railway');
    $port = (int) db_setting(['MYSQLPORT', 'DB_PORT'], '3306');

    $conn = mysqli_connect($host, $user, $pass, $db, $port);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}
?>
