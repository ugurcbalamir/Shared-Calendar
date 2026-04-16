<?php
if (!isset($config)) {
    $config = require __DIR__ . '/config.php';
}

function send_smtp_email(string $to, string $subject, string $bodyHtml): bool
{
    global $config;

    $host = $config['smtp']['host'];
    $port = $config['smtp']['port'];
    $username = $config['smtp']['username'];
    $password = $config['smtp']['password'];
    $from = $config['smtp']['from'];

    $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
    if (!$socket) {
        error_log("SMTP Connect failed: $errstr ($errno)");
        return false;
    }

    $read = function() use ($socket) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    };

    $write = function($cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
    };

    // Initial greeting
    $response = $read();
    if (substr($response, 0, 3) !== '220') { error_log("SMTP Error: $response"); return false; }

    // EHLO
    $write("EHLO " . gethostname());
    $response = $read();
    if (substr($response, 0, 3) !== '250') { error_log("SMTP EHLO Error: $response"); return false; }

    // AUTH LOGIN
    $write("AUTH LOGIN");
    $response = $read();
    if (substr($response, 0, 3) !== '334') { error_log("SMTP AUTH LOGIN Error: $response"); return false; }

    $write(base64_encode($username));
    $response = $read();
    if (substr($response, 0, 3) !== '334') { error_log("SMTP Username Error: $response"); return false; }

    $write(base64_encode($password));
    $response = $read();
    if (substr($response, 0, 3) !== '235') { error_log("SMTP Password Error: $response"); return false; }

    // MAIL FROM
    $write("MAIL FROM: <$username>");
    $response = $read();
    if (substr($response, 0, 3) !== '250') { error_log("SMTP MAIL FROM Error: $response"); return false; }

    // RCPT TO
    $write("RCPT TO: <$to>");
    $response = $read();
    if (substr($response, 0, 3) !== '250') { error_log("SMTP RCPT TO Error: $response"); return false; }

    // DATA
    $write("DATA");
    $response = $read();
    if (substr($response, 0, 3) !== '354') { error_log("SMTP DATA Error: $response"); return false; }

    // Headers & Body
    $headers = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=UTF-8",
        "From: $from",
        "To: $to",
        "Subject: $subject",
        "Date: " . date('r')
    ];

    $content = implode("\r\n", $headers) . "\r\n\r\n" . $bodyHtml . "\r\n.";
    $write($content);
    $response = $read();
    if (substr($response, 0, 3) !== '250') { error_log("SMTP Body Error: $response"); return false; }

    // QUIT
    $write("QUIT");
    fclose($socket);

    return true;
}
