<?php

define('CHANEL_ID', 'your channel ID');
define('API_KEY', 'your API KEY');
define('WEBHOOK_URL', 'https://api.wazzup24.com/v3/message');

$msg = file_get_contents('php://input');
if (!empty($msg)) {
    $receivedMessage = json_decode($msg);

    if ($receivedMessage->messages[0]->isEcho != true) {
        if (!empty($receivedMessage->messages[0]->chatId)) {
            $chatId = $receivedMessage->messages[0]->chatId;
            $text = $receivedMessage->messages[0]->text;

            $state = getUserState($chatId);
            
            processMessage($chatId, $text, $state);
        }
    }

    header("HTTP/1.0 200 OK");
}

function sendMessage($chatId, $text) {

    $postData = json_encode([
        'channelId' => 'your channel ID',
        'chatType' => "whatsapp",
        'chatId' => $chatId,
        'text' => $text
    ]);

    $ch = curl_init(WEBHOOK_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . API_KEY]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_close($ch);
}

function getUserState($userId) {
    $userFile = __DIR__ . "/users/" . $userId . ".json";
    if (file_exists($userFile)) {
        return json_decode(file_get_contents($userFile), true);
    }
    return ['step' => 'init'];
}

function updateUserState($userId, $data) {
    $userFile = __DIR__ . "/users/" . $userId . ".json";
    file_put_contents($userFile, json_encode($data));
}

function processMessage($chatId, $text, $state) {
    
    switch ($state['step']) {

        case 'init':
            $state['step'] = 'hello';
            $responseText = "Hey! I'am youre personal WhatsApp Bot!";
            sendMessage($chatId, $responseText);
            updateUserState($chatId, $state);
            processMessage($chatId, $text, $state);
            break;
        
        case 'hello':
            if ($text === "Your template message (for example: '/start')") {
                $responseText = "Please enter your name.";
                $state['step'] = 'ask_name';
            } else {
                $responseText = "Please describe your request.";
                $state['step'] = 'other_request';
            }
            updateUserState($chatId, $state);
            sendMessage($chatId, $responseText);
            break;

        case 'ask_name':
            if ($text === "Your template message (for example: '/start')") {
                $state['step'] = 'hello';
                updateUserState($chatId, $state);
                processMessage($chatId, $text, $state);
            } else {
                $state['name'] = $text;
                $state['step'] = 'ask_email';
                updateUserState($chatId, $state);
                sendMessage($chatId, "Please enter your email.");
            }
            break;

        case 'ask_email':
            if ($text === "Your template message (for example: '/start')") {
                $state['step'] = 'hello';
                updateUserState($chatId, $state);
                processMessage($chatId, $text, $state);
            } else {
                $state['email'] = $text;
                $state['step'] = 'completed';
                updateUserState($chatId, $state);
                sendMessage($chatId, "Thank you!");
            }
            break;

        case 'other_request':
            if ($text === "Your template message (for example: '/start')") {
                $state['step'] = 'hello';
                updateUserState($chatId, $state);
                processMessage($chatId, $text, $state);
            } else {
                $state['other_request'] = $text;
                $state['step'] = 'completed';
                updateUserState($chatId, $state);
                sendMessage($chatId, "Thank you!");
            }
            break;
        
        case 'completed':
            if ($text === "Your template message (for example: '/start')") {
                $state['step'] = 'hello';
                updateUserState($chatId, $state);
                processMessage($chatId, $text, $state);
            }
            break;
    }
}

?>