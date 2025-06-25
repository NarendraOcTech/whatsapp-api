<?php
//  header('Access-Control-Allow-Origin: *');
//  header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
//  header('Access-Control-Allow-Headers: Content-Type, Authorization, cds-pixel-id');
//  header("Content-Type: application/json");
// require_once './bootstrap/app.php';

// $app->run();



require 'vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twilio\Rest\Client;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = AppFactory::create();

// 🟢 API 1: Send WhatsApp message with options
$app->post('/send-whatsapp', function (Request $request, Response $response) {
    $params = (array) $request->getParsedBody();
    $mobile = $params['mobile'] ?? '';
    $name = $params['name'] ?? 'User';

    $msg = "Hi $name 👋\nPlease reply with:\n1️⃣ Order Status\n2️⃣ Talk to Support";

    $twilio = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_TOKEN']);

    $message = $twilio->messages->create(
        "whatsapp:+91$mobile",
        [
            'from' => 'whatsapp:+14155238886', // Twilio Sandbox Number
            'body' => $msg
        ]
    );

    $response->getBody()->write(json_encode([
        'status' => 'sent',
        'sid' => $message->sid,
        'to' => $message->to,
        'body' => $msg
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

// 🟢 API 2: Webhook to handle replies
$app->post('/webhook', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $from = $data['From'] ?? '';
    $body = strtolower(trim($data['Body'] ?? ''));

    $twilio = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_TOKEN']);

    if ($body === '1') {
        $reply = "🛒 Your order is on the way! 🚚";
    } elseif ($body === '2') {
        $reply = "👨‍💼 Our support agent will contact you shortly.";
    } else {
        $reply = "❓ Sorry, I didn't understand. Please reply with 1 or 2.";
    }

    $twilio->messages->create(
        $from,
        [
            'from' => 'whatsapp:+14155238886',
            'body' => $reply
        ]
    );

    $response->getBody()->write('OK');
    return $response;
});

$app->run();
