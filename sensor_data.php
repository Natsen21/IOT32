<?php
header('Content-Type: application/json; charset=utf-8');

// Allowed device IDs. Each device gets its own file: data_1.json, data_2.json
$DEVICES = [1, 2];

function device_file($id) {
    return __DIR__ . '/data_' . $id . '.json';
}

function read_device($id) {
    $file = device_file($id);
    if (!file_exists($file)) {
        return ['error' => 'No data yet'];
    }
    $data = json_decode(file_get_contents($file), true);
    if ($data === null) {
        return ['error' => 'Corrupt data file'];
    }
    $data['_last_modified'] = filemtime($file);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Device ID comes from the JSON body ("device") or the query string (?device=)
    $id = (int)($data['device'] ?? ($_GET['device'] ?? 0));
    if (!in_array($id, $DEVICES, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid device id (use 1 or 2)']);
        exit;
    }

    $written = @file_put_contents(device_file($id), json_encode($data));
    if ($written === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not write data file — check folder write permissions']);
        exit;
    }

    echo json_encode(['status' => 'OK', 'device' => $id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ?device=1 -> single device; no param -> both devices
    if (isset($_GET['device'])) {
        $id = (int)$_GET['device'];
        if (!in_array($id, $DEVICES, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid device id']);
            exit;
        }
        echo json_encode(read_device($id));
        exit;
    }

    $out = [];
    foreach ($DEVICES as $id) {
        $out[$id] = read_device($id);
    }
    echo json_encode($out);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
