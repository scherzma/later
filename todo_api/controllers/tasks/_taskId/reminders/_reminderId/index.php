<?php
$params = $_REQUEST['params'] ?? [];
$taskId = $params['taskId'] ?? null;
$reminderId = $params['reminderId'] ?? null;

echo json_encode([
    'taskId' => $taskId,
    'reminderId' => $reminderId
]);