<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\IntegrationService;
use Throwable;

/**
 * Class TichHopController
 * Quản trị các cổng kết nối tích hợp ngoài (VNeID, LGSP, C06, CSDL_TTHC_QG, SmartCA, SMS, Zalo ZNS, VNPost, ClamAV)
 * (§1.1, §2.3)
 */
class TichHopController extends Controller
{
    private IntegrationService $integrationService;

    public function __construct()
    {
        $this->integrationService = new IntegrationService();
    }

    /**
     * Màn hình danh sách 9 cổng kết nối tích hợp
     */
    public function index(Request $request): Response
    {
        $services = $this->integrationService->getAllServicesWithStats();
        $logs = $this->integrationService->getRecentLogs();

        return $this->view('admin/he_thong/tich_hop', [
            'title'    => 'Quản trị Cổng tích hợp liên thông (§1.1, §2.3)',
            'services' => $services,
            'logs'     => $logs,
        ]);
    }

    /**
     * AJAX: Kiểm tra kết nối dịch vụ (Ping test)
     */
    public function ping(Request $request): Response
    {
        $maDichVu = trim((string)$request->input('ma_dich_vu', ''));
        if ($maDichVu === '') {
            return Response::json(['success' => false, 'message' => 'Mã dịch vụ không hợp lệ'], 400);
        }

        try {
            $result = $this->integrationService->pingService($maDichVu);
            return Response::json([
                'success'    => $result['success'],
                'data'       => $result,
                'message'    => $result['message'],
                'latency_ms' => $result['latency_ms'],
            ]);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'message' => 'Lỗi kiểm tra kết nối: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * AJAX: Cập nhật cấu hình dịch vụ
     */
    public function update(Request $request): Response
    {
        $maDichVu = trim((string)$request->input('ma_dich_vu', ''));
        if ($maDichVu === '') {
            return Response::json(['success' => false, 'message' => 'Mã dịch vụ không hợp lệ'], 400);
        }

        $data = [
            'endpoint_url'           => (string)$request->input('endpoint_url', ''),
            'che_do'                 => (string)$request->input('che_do', 'MOCK'),
            'api_key'                => (string)$request->input('api_key', ''),
            'secret_key'             => (string)$request->input('secret_key', ''),
            'thoi_gian_timeout_giay' => (int)$request->input('thoi_gian_timeout_giay', 30),
        ];

        try {
            $ok = $this->integrationService->updateConfig($maDichVu, $data);
            if ($ok) {
                return Response::json(['success' => true, 'message' => "Cập nhật cấu hình cổng '{$maDichVu}' thành công"]);
            }
            return Response::json(['success' => false, 'message' => 'Không thể cập nhật cấu hình'], 400);
        } catch (Throwable $e) {
            return Response::json(['success' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()], 400);
        }
    }
}
