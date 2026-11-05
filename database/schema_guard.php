<?php
// Schema bootstrapping runs dozens of INFORMATION_SCHEMA lookups, which added
// 1-3 seconds to every request when it ran on every connection. It now runs once
// per version of the file that defines the schema, tracked by a marker file.
// Callers also confirm the users table exists, so a recreated database still
// gets rebuilt while the marker is present.
function ss_schema_marker($driver, $schemaFile, $dbName) {
    $version = md5($driver . '|' . $dbName . '|' . filemtime($schemaFile));
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sheshield_schema_' . $version . '.ok';
}
