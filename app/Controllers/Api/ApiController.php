<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;

/**
 * Class ApiController
 * Base Controller cho REST API /api/v1 - Chuẩn hóa định dạng JSON phản hồi
 */
abstract class ApiController
{
    /**
     * Trả về phản hồi thành công
     *
     * @param mixed $data Dữ liệu trả về
     * @param string $message Thông điệp thành công
     * @param int $code Mã trạng thái HTTP (mặc định 200)
     */
    protected function success(mixed $data = null, string $message = 'Thành công', int $code = 200): Response
    {
        return Response::json([
            'status'    => 'success',
            'code'      => $code,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('c'),
        ], $code);
    }

    /**
     * Trả về phản hồi thất bại
     *
     * @param string $message Thông điệp lỗi
     * @param int $code Mã trạng thái HTTP (400, 401, 403, 404, 422, 500)
     * @param array $errors Danh sách chi tiết lỗi nếu có
     */
    protected function error(string $message = 'Đã có lỗi xảy ra', int $code = 400, array $errors = []): Response
    {
        return Response::json([
            'status'    => 'error',
            'code'      => $code,
            'message'   => $message,
            'errors'    => $errors,
            'timestamp' => date('c'),
        ], $code);
    }
}
