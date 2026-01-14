<?php
/**
 * telegram_sender.php
 * ฟังก์ชันสำหรับส่งข้อความไปยัง Telegram Bot API
 */
function sendTelegramMessage($botToken, $chatId, $message) {
    // URL ของ Telegram API
    $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

    // ข้อมูลที่จะส่งไป (ในรูปแบบ Array)
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    // ใช้ cURL เพื่อส่ง HTTP POST request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // ตั้งเวลา timeout 10 วินาที

    // ทำการ execute request
    $response = curl_exec($ch);
    
    // ตรวจสอบ error (ถ้ามี)
    if (curl_errno($ch)) {
        // ในระบบจริง ควรจะบันทึก error ลง log file
        // error_log('Telegram cURL Error: ' . curl_error($ch));
    }

    // ปิดการเชื่อมต่อ cURL
    curl_close($ch);

    return $response;
}
?>
