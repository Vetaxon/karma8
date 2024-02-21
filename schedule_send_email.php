<?php

/**
 * @var PDO $dbConnection
 */
$dbConnection = require 'db_connection.php';

$offset = 0;
$chunk = 100;
$max = 1000;

do {
    $stmt = $dbConnection->prepare(
        'SELECT users.id as id, users.email as email, users.username as name
            FROM users
            LEFT JOIN user_jobs ON users.id = user_jobs.user_id
            LEFT JOIN jobs ON user_jobs.job_id = jobs.id
        WHERE (users.valid = 1 OR users.confirmed = 1)
        AND users.validts > 0
        AND TIMESTAMPDIFF(DAY, CURDATE(), FROM_UNIXTIME(users.validts)) IN (1, 3)
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
                'email_sub_exp',
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
