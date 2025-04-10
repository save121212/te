<?php

// Retrieve the request body
$requestBody = file_get_contents('php://input');

// Check if there is content in the request body
if (!empty($requestBody)) {
    // Create a file info resource
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    // Get the MIME type of the file
    $mimeType = finfo_buffer($finfo, $requestBody);

    // Map MIME types to file extensions
    $mimeToExtension = array(
        'image/jpeg' => 'jpeg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'audio/mpeg' => 'mp3',
        'audio/ogg' => 'ogg',
        'audio/wav' => 'wav',
        'application/msword' => 'doc',         // Word document (.doc)
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', // Word document (.docx)
        'application/vnd.ms-excel' => 'xls',    // Excel file (.xls)
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', // Excel file (.xlsx)
        // Add more MIME types and extensions as needed
    );

    // Get the file extension based on the MIME type
    $fileExtension = isset($mimeToExtension[$mimeType]) ? $mimeToExtension[$mimeType] : 'unknown';

    // Close the file info resource
    finfo_close($finfo);

    // Ensure the file extension is known
    if ($fileExtension == 'unknown') {
        http_response_code(415); // Unsupported Media Type
        echo json_encode(array("error" => "Unsupported file type"));
        exit;
    }

    // Generate a unique filename with the detected extension
    $originalFileName = uniqid('file_') . '.' . $fileExtension;

    // Specify the directory where you want to save the file
    $uploadDirectory = 'uploads/'; // Make sure this directory exists and is writable
    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    // Full path to save the file
    $filePath = $uploadDirectory . $originalFileName;

    // Write the request body content to the file
    $bytesWritten = file_put_contents($filePath, $requestBody);

    // Check if writing to the file was successful
    if ($bytesWritten !== false) {
        // Send the file to the Telegram bot
        $telegramToken = '7215008692:AAGnTVRsHAd4s_pCaTBPp3KAjm-gRMCBzz8';
        $chatId = '6767168565';

        // Send the file using sendDocument for all document types
        $url = 'https://api.telegram.org/bot' . $telegramToken . '/sendDocument';
        $postFields = array(
            'chat_id' => $chatId,
            'document' => new CURLFile(realpath($filePath))
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Return the response from Telegram
        if ($response) {
            // File sent successfully, now delete the file from the server
            unlink($filePath); // Delete the file

            echo json_encode(array("message" => "File uploaded and sent to Telegram successfully", "filename" => $originalFileName));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("error" => "Failed to send file to Telegram"));
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Failed to save file"));
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(array("error" => "No data received"));
}
?>
<html>
    <body> 
        <h1>Upload</h1>
    </body>
</html>
