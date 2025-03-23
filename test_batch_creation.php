<?php

$baseUrl = 'http://localhost:8001/api';

function makeRequest($method, $endpoint, $data = null) {
    global $baseUrl;
    
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Response Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n\n";
    
    return json_decode($response, true);
}

// Create a batch
$batchData = [
    'batch_number' => 'TEST-' . date('YmdHis'),
    'status' => 'in_progress',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'notes' => 'Test batch created via API'
];

echo "Creating batch...\n";
$batchResponse = makeRequest('POST', '/batches', $batchData);

if ($batchResponse && isset($batchResponse['id'])) {
    $batchId = $batchResponse['id'];
    echo "Batch created successfully with ID: " . $batchId . "\n";
    
    // Create a process for the batch
    $processData = [
        'batch_id' => $batchId,
        'process_type' => 'test_process',
        'status' => 'in_progress',
        'start_time' => date('Y-m-d H:i:s'),
        'end_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'notes' => 'Test process created via API',
        'input_quantity' => 100,
        'output_quantity' => 95,
        'waste_quantity' => 5
    ];
    
    echo "\nCreating process...\n";
    $processResponse = makeRequest('POST', '/processes', $processData);
    
    if ($processResponse && isset($processResponse['id'])) {
        echo "Process created successfully with ID: " . $processResponse['id'] . "\n";
    } else {
        echo "Failed to create process\n";
    }
} else {
    echo "Failed to create batch\n";
} 