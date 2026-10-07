<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * CaptchaService – Dịch vụ sinh và kiểm tra mã Captcha chống Brute-Force
 * Sử dụng PHP GD extension có sẵn trong XAMPP
 */
class CaptchaService
{
    private const SESSION_KEY = 'auth_captcha_code';
    private const CHARACTERS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Loại bỏ 0, O, 1, I để tránh nhầm lẫn

    /**
     * Sinh mã captcha ngẫu nhiên và lưu vào session
     */
    public static function generateCode(int $length = 5): string
    {
        $code = '';
        $max = strlen(self::CHARACTERS) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= self::CHARACTERS[random_int(0, $max)];
        }
        Session::set(self::SESSION_KEY, strtoupper($code));
        return $code;
    }

    /**
     * Lấy mã captcha hiện tại trong session
     */
    public static function getCode(): ?string
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Kiểm tra tính đúng đắn của captcha
     */
    public static function verify(?string $input): bool
    {
        if (empty($input)) {
            return false;
        }

        $storedCode = Session::get(self::SESSION_KEY);
        if (empty($storedCode)) {
            return false;
        }

        $isValid = (strtoupper(trim($input)) === $storedCode);
        // Sau khi kiểm tra (dù đúng hay sai), xóa mã cũ để tránh replay
        Session::remove(self::SESSION_KEY);

        return $isValid;
    }

    /**
     * Xuất hình ảnh PNG trực tiếp ra luồng output
     */
    public static function renderImage(int $width = 130, int $height = 42): void
    {
        $code = self::generateCode();

        // Tạo canvas hình ảnh
        $image = imagecreatetruecolor($width, $height);

        // Màu nền xám nhạt hiện đại
        $bg = imagecolorallocate($image, 240, 243, 248);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        // Vẽ các đường kẻ nhiễu (noise lines)
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, random_int(180, 210), random_int(180, 210), random_int(200, 230));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        // Vẽ các chấm nhiễu (noise dots)
        for ($i = 0; $i < 150; $i++) {
            $dotColor = imagecolorallocate($image, random_int(150, 200), random_int(150, 200), random_int(170, 220));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
        }

        // Màu chữ Navy thương hiệu HP-eGov (#0B2E59) pha chút ngẫu nhiên
        $len = strlen($code);
        $charWidth = (int)(($width - 20) / $len);

        for ($i = 0; $i < $len; $i++) {
            $textColor = imagecolorallocate($image, random_int(11, 30), random_int(46, 70), random_int(89, 130));
            $char = $code[$i];
            $x = 12 + ($i * $charWidth) + random_int(-3, 3);
            $y = random_int(8, 16);

            // Dùng font tích hợp GD font 5 (font lớn nhất tích hợp sẵn)
            imagestring($image, 5, $x, $y, $char, $textColor);
        }

        // Trả về header PNG và ảnh
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        imagepng($image);
        imagedestroy($image);
        exit;
    }
}
