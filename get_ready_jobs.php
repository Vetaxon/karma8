<?php

/**
 * @var PDO $dbConnection
 * @var array $argv
 */

$dbConnection = require 'db_connection.php';

$offset = 0;
$chunk = 1;
$max = 2;

$type = resolveType($argv);

$stmt = $dbConnection->prepare(
    'SELECT count(*) as count_jobs FROM jobs
                    WHERE status = "ready"
                    AND type = :param_type;',
);

$stmt->bindParam(':param_type', $type, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo $result['count_jobs'];

function resolveType(array $argv): string
{
    $typeKey = array_search('--type', $argv);

    if (!$typeKey) {
        error_log('--type is required');
        exit(1);
    }

    $type = $argv[$typeKey + 1] ?? '';

    return $type;
}
