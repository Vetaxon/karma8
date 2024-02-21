<?php

/**
 * @var PDO $dbConnection
 */
$dbConnection = require 'db_connection.php';

$offset = 0;
$chunk = 10;
$max = 1000;

do {
    $stmt = $dbConnection->prepare(
        'SELECT users.id as id, users.email as email FROM users 
         LEFT JOIN user_jobs ON users.id = user_jobs.user_id
         LEFT JOIN jobs ON user_jobs.job_id = jobs.id
         WHERE users.checked = 0 
           AND users.confirmed = 0 
           AND (users.validts <= UNIX_TIMESTAMP(NOW() + INTERVAL 5 DAY) AND users.validts > 0)
           AND jobs.type IS NULL
        ORDER BY users.validts ASC
        LIMIT :param_offset, :param_limit;',
    );

    $stmt->bindParam(':param_offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':param_limit', $chunk, PDO::PARAM_INT);

    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $dbConnection->prepare('INSERT INTO jobs (type, params) VALUES (?, ?)')
            ->execute([
                'email_validation',
                json_encode($result),
            ]);
        $jobId = $dbConnection->lastInsertId();
        $dbConnection
            ->prepare('INSERT INTO user_jobs (user_id, job_id) VALUES (?, ?)')
            ->execute([
                $result['id'],
                $jobId,
            ]);
    }

    $offset += $chunk;
    $continue = count($results) === $chunk && $offset < $max;
} while ($continue);
