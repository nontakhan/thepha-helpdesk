<?php
/**
 * config.php
 * ไฟล์สำหรับตั้งค่าต่างๆ ของระบบ Thepha Helpdesk
 */

// โหลด environment variables จาก .env (ถ้ายังไม่ได้โหลด)
if (!function_exists('env')) {
    require_once __DIR__ . '/env_loader.php';
}

// --- ชื่อระบบ ---
define('APP_NAME', 'Thepha Helpdesk');

// --- Telegram Bot Settings ---
// ค่าจะถูกอ่านจากไฟล์ .env โดยอัตโนมัติ
define('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE'));
define('TELEGRAM_CHAT_ID', env('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID_HERE'));