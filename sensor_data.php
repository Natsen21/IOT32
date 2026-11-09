<?php
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/data.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $written = @file_put_contents($file, json_encode($data));

    if ($written === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not write data.json — check folder write permissions']);
        exit;
    }

    echo json_encode(['status' => 'OK']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo json_encode(['error' => 'No data yet']);
        exit;
    }

    $raw  = file_get_contents($file);
    $data = json_decode($raw, true);

    if ($data === null) {
        echo json_encode(['error' => 'Corrupt data file']);
        exit;
    }

    $data['_last_modified'] = filemtime($file);
    echo json_encode($data);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);