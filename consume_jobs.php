<?php

include 'external.php';

/**
 * @var PDO $dbConnection
 * @var array $argv
 * --type email_validation
 * --type email_sub_exp
 */
$dbConnection = require 'db_connection.php';

$offset = 0;
$chunk = 1;
$max = 2;

$strategies = [
    'email_validation' => function (PDO $dbConnection, $job, $params) {
        $validationResults = checkEmail($params['email']);
        updateUser($dbConnection, $validationResults, $params['id']);
        acknowledgeJob($dbConnection, $job, $params);
    },
    'email_sub_exp' => function (PDO $dbConnection, $job, $params) {
        sendEmail(
            'from_me@test.com',
            $params['email'],
            sprintf('%s, your subscription is expiring soon', $params['name']),
        );
        acknowledgeJob($dbConnection, $job, $params);
    },
];

$type = resolveType($argv, $strategies);

$continue = true;

do {

    try {
        $dbConnection->beginTransaction();
        $job = getReadyJob($dbConnection, $type, $offset);
        $dbConnection->commit();
    } catch (PDOException) {
        continue;
    }

    if (!empty($job)) {
        $params = json_decode($job['params'], true);
        try {
            $strategies[$type]($dbConnection, $job, $params);
        } catch (Exception) {
            restoreJob($dbConnection, $job);
        }
        echo sprintf('Job %s has need acknowledged!', json_encode($job)) . PHP_EOL;
    }

    $offset += $chunk;
    $continue = !empty($job) && $offset < $max;
} while ($continue);


function resolveType(array $argv, array $strategies): string
{
    $typeKey = array_search('--type', $argv);

    if (!$typeKey) {
        error_log('--type is required');
        exit(1);
    }

    $type = $argv[$typeKey + 1] ?? '';

    if (!in_array($type, array_keys($strategies))) {
        error_log('Type is not supported');
        exit(1);
    }

    return $type;
}

function getReadyJob(PDO $dbConnection, string $type, string $offset): array|null
{
    $stmt = $dbConnection->prepare(
        'SELECT id, params FROM jobs
                    WHERE status = "ready"
                    AND type = :param_type
                    ORDER BY created_at ASC
                    LIMIT :param_offset, 1
                    FOR UPDATE;',
    );

    $stmt->bindParam(':param_type', $type, PDO::PARAM_STR);
    $stmt->bindParam(':param_offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$results) {
        return null;
    }

    $job = array_shift($results);

    $dbConnection->prepare('UPDATE jobs SET status = ? WHERE id = ?')
        ->execute([
            'in progress',
            $job['id'],
        ]);

    return $job;
}

function acknowledgeJob(PDO $dbConnection, array $job, array $params): void
{
    $dbConnection
        ->prepare('DELETE FROM user_jobs WHERE job_id = ? AND user_id = ?')
        ->execute([
            $job['id'],
            $params['id'],
        ]);

    $dbConnection->prepare('UPDATE jobs SET status = ? WHERE id = ?')
        ->execute([
            'acknowledged',
            $job['id'],
        ]);
}

function updateUser(PDO $dbConnection, int $validationResults, $userId): void
{
    $dbConnection->prepare('UPDATE users SET checked = ?, valid = ? WHERE id = ?')
        ->execute([
            1,
            $validationResults,
            $userId,
        ]);
}

function restoreJob(PDO $dbConnection, array $job): void
{
    $dbConnection->prepare('UPDATE jobs SET status = ? WHERE id = ?')
        ->execute([
            'ready',
            $job['id'],
        ]);
}
