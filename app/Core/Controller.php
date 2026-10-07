<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Class Controller
 * Lớp điều khiển cơ sở cung cấp các tiện ích phản hồi View, JSON, Redirect và kiểm tra quyền hạn.
 */
abstract class Controller
{
    /**
     * Trả về giao diện View kèm dữ liệu
     *
     * @param string $view Tên tệp view
     * @param array<string, mixed> $data Dữ liệu truyền vào view
     * @param int $status Mã trạng thái HTTP
     * @return Response
     */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        $html = View::render($view, $data);
        return Response::html($html, $status);
    }

    /**
     * Trả về dữ liệu JSON
     *
     * @param mixed $data Dữ liệu cần phản hồi
     * @param int $status Mã trạng thái HTTP
     * @return Response
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Chuyển hướng trình duyệt
     *
     * @param string $url URL đích
     * @param int $status Mã chuyển hướng
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Kiểm tra quyền hạn của người dùng hiện tại (chống IDOR / trái phép)
     * Nếu không có quyền, ném lỗi 403 Forbidden.
     *
     * @param string $permission Mã quyền hạn cần thẩm định
     * @throws RuntimeException
     */
    protected function authorize(string $permission): void
    {
        $user = auth();
        if ($user === null) {
            throw new RuntimeException("Chưa đăng nhập. Truy cập bị từ chối.", 401);
        }

        // Kiểm tra quyền qua session hoặc RBAC
        $permissions = Session::get('user_permissions', []);
        if (!in_array($permission, $permissions, true) && !in_array('*', $permissions, true)) {
            throw new RuntimeException("Bạn không có quyền thực hiện chức năng này [{$permission}].", 403);
        }
    }
}
