<?php
/**
 * env_loader.php
 * ฟังก์ชันสำหรับโหลดค่าจากไฟล์ .env
 */

function loadEnv($path = null)
{
    if ($path === null) {
        $path = __DIR__ . '/.env';
    }

    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // ข้าม comment lines
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // แยก key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // ลบ quotes ถ้ามี
            $value = trim($value, '"\'');

            // ตั้งค่า environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    return true;
}

/**
 * ดึงค่า environment variable
 */
function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// โหลด .env อัตโนมัติ
loadEnv();
