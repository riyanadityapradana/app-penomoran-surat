<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../library/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../library/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../library/PHPMailer/src/SMTP.php';

if (!function_exists('app_env')) {
    function app_env($key, $default = '')
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url()
    {
        return rtrim(app_env('APP_BASE_URL', 'http://localhost/app_no-surat'), '/');
    }
}

if (!function_exists('app_format_datetime_id')) {
    function app_format_datetime_id($value)
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $day = date('d', $timestamp);
        $month = $months[(int) date('n', $timestamp)];
        $year = date('Y', $timestamp);
        $time = date('H:i', $timestamp);

        return $day . ' ' . $month . ' ' . $year . ' ' . $time;
    }
}

if (!function_exists('app_send_email')) {
    function app_send_email($to, $subject, $htmlBody, $altBody = '')
    {
        if (empty($to)) {
            return ['ok' => false, 'message' => 'Alamat email kosong'];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = app_env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = app_env('MAIL_USERNAME', 'sekretariatrspelitainsani@gmail.com');
            $mail->Password = app_env('MAIL_PASSWORD', 'wino fwge gpyg ajny');
            $mail->SMTPSecure = app_env('MAIL_ENCRYPTION', 'tls');
            $mail->Port = (int) app_env('MAIL_PORT', '587');
            $mail->CharSet = 'UTF-8';

            $fromAddress = app_env('MAIL_FROM_ADDRESS', $mail->Username);
            $fromName = app_env('MAIL_FROM_NAME', 'Sistem Pengajuan Dokumen');

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $mail->send();

            return ['ok' => true, 'message' => 'Email berhasil dikirim'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $mail->ErrorInfo ?: $e->getMessage()];
        }
    }
}

if (!function_exists('app_send_email_to_admins')) {
    function app_send_email_to_admins($config, $subject, $htmlBody, $altBody = '')
    {
        $result = ['sent' => 0, 'failed' => []];
        $query = mysqli_query($config, "SELECT email_user FROM tb_user WHERE level = 'Admin' AND email_user IS NOT NULL AND email_user <> ''");

        if (!$query) {
            $result['failed'][] = 'Query email admin gagal';
            return $result;
        }

        $emails = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $emails[] = strtolower(trim($row['email_user']));
        }

        $emails = array_values(array_unique(array_filter($emails)));

        foreach ($emails as $email) {
            $send = app_send_email($email, $subject, $htmlBody, $altBody);
            if ($send['ok']) {
                $result['sent']++;
            } else {
                $result['failed'][] = $email . ': ' . $send['message'];
            }
        }

        return $result;
    }
}

if (!function_exists('app_send_telegram')) {
    function app_send_telegram($message)
    {
        $token = app_env('TELEGRAM_BOT_TOKEN');
        $chatId = app_env('TELEGRAM_CHANNEL_ID');
        $threadId = app_env('TELEGRAM_MESSAGE_THREAD_ID');

        if ($token === '' || $chatId === '') {
            return ['ok' => false, 'message' => 'Telegram belum dikonfigurasi'];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($threadId !== '') {
            $payload['message_thread_id'] = $threadId;
        }

        $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['ok' => false, 'message' => 'Request Telegram gagal'];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return ['ok' => false, 'message' => is_array($decoded) && isset($decoded['description']) ? $decoded['description'] : 'Telegram menolak request'];
        }

        return ['ok' => true, 'message' => 'Telegram berhasil dikirim'];
    }
}
?>
