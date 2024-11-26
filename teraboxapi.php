<?php
// Function to handle the request
function handleRequest() {
    // Check if 'url' parameter is provided
    if (!isset($_GET['url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No URL provided.']);
        return;
    }

    $targetUrl = $_GET['url'];
    $apiUrl = 'https://ytshorts.savetube.me/api/v1/terabox-downloader';
    $postData = json_encode(['url' => $targetUrl]);

    // cURL request options
    $curlOptions = [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            "Host: ytshorts.savetube.me",
            "content-length: " . strlen($postData),
            "sec-ch-ua: \"Android WebView\";v=\"123\", \"Not:A-Brand\";v=\"8\", \"Chromium\";v=\"123\"",
            "accept: application/json, text/plain, */*",
            "content-type: application/json",
            "sec-ch-ua-mobile: ?1",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
            "sec-ch-ua-platform: \"Android\"",
            "origin: https://ytshorts.savetube.me",
            "x-requested-with: mark.via.gp",
            "sec-fetch-site: same-origin",
            "sec-fetch-mode: cors",
            "sec-fetch-dest: empty",

        ]
    ];

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt_array($ch, $curlOptions);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'Request failed: ' . curl_error($ch)]);
    } else {
        // Set response headers and output the response
        header('Content-Type: application/json');
        echo $response;
    }

    // Close cURL session
    curl_close($ch);
}

// Call the handleRequest function to process the request
handleRequest();
?>
