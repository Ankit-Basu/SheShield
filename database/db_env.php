<?php
// Database settings, shared by the PDO and mysqli layers.
// Docker Compose, Kubernetes, Helm and Render set these. XAMPP uses the defaults.
//
// Hosted MySQL (Aiven, TiDB, and most others) only accepts encrypted connections:
//   DB_SSL=true                    encrypt the connection
//   DB_SSL_CA=/etc/secrets/ca.pem  optional: also verify the server against this CA file
function ss_db_env() {
    $ssl = strtolower((string)getenv('DB_SSL'));
    $ca  = (string)getenv('DB_SSL_CA');

    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'db'   => getenv('DB_NAME') ?: 'sheshield',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'ssl'  => in_array($ssl, ['1', 'true', 'yes', 'on', 'required'], true) || $ca !== '',
        'ca'   => ($ca !== '' && is_readable($ca)) ? $ca : '',
    ];
}

// PDO options that turn on TLS. pdo_mysql only encrypts when it is given a CA
// file, so without one it uses the system bundle and skips verification.
function ss_pdo_ssl_options(array $cfg) {
    if (!$cfg['ssl']) {
        return [];
    }
    $systemBundle = '/etc/ssl/certs/ca-certificates.crt';
    return [
        PDO::MYSQL_ATTR_SSL_CA => $cfg['ca'] ?: $systemBundle,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $cfg['ca'] !== '',
    ];
}

// mysqli connection that honours the same settings. Throws mysqli_sql_exception
// or returns an object whose connect_error is set, like new mysqli() does.
function ss_mysqli_connect(array $cfg, $dbname = '') {
    if (!$cfg['ssl']) {
        return new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $dbname, $cfg['port']);
    }
    $conn = mysqli_init();
    $flags = MYSQLI_CLIENT_SSL;
    if ($cfg['ca'] !== '') {
        $conn->ssl_set(null, null, $cfg['ca'], null, null);
    } else {
        $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
    }
    @$conn->real_connect($cfg['host'], $cfg['user'], $cfg['pass'], $dbname, $cfg['port'], null, $flags);
    return $conn;
}
