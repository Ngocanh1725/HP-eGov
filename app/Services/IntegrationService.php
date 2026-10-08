<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use PDO;
use Throwable;

/**
 * Class IntegrationService
 * Quản trị các cổng kết nối tích hợp ngoài (§1.1, §1.4, §2.3)
 * Danh mục 9 cổng: VNeID, LGSP, C06, CSDL_TTHC_QG, SmartCA, SMS, Zalo ZNS, VNPost, ClamAV
 */
class IntegrationService
{
    /**
     * Lấy danh sách toàn bộ cổng tích hợp kèm thống kê từ nhat_ky_tich_hop
     *
     * @return array<int, array>
     */
    public function getAllServicesWithStats(): array
    {
        $pdo = Database::pdo();

        // 1. Lấy danh sách cấu hình
        $stmt = $pdo->query("SELECT * FROM cau_hinh_tich_hop ORDER BY id ASC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Lấy thống kê từ nhat_ky_tich_hop
        $statsStmt = $pdo->query("
            SELECT
                dich_vu,
                COUNT(*) as tong_so_luot,
                SUM(CASE WHEN status_code IS NULL OR status_code >= 400 THEN 1 ELSE 0 END) as so_luot_loi,
                AVG(thoi_gian_xu_ly_ms) as do_tre_tb,
                MAX(created_at) as lan_goi_cuoi
            FROM nhat_ky_tich_hop
            GROUP BY dich_vu
        ");
        $statsMap = [];
        while ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
            $statsMap[$row['dich_vu']] = $row;
        }

        foreach ($services as &$s) {
            $code = $s['ma_dich_vu'];
            $st = $statsMap[$code] ?? null;

            $tong = $st ? (int)$st['tong_so_luot'] : 0;
            $loi = $st ? (int)$st['so_luot_loi'] : 0;
            $latency = $st ? round((float)$st['do_tre_tb'], 1) : 0;
            $tyLeLoi = $tong > 0 ? round(($loi / $tong) * 100, 2) : 0.0;

            $s['thong_ke'] = [
                'tong_luot'      => $tong,
                'luot_loi'       => $loi,
                'ty_le_loi'      => $tyLeLoi,
                'do_tre_tb_ms'   => $latency,
                'lan_goi_cuoi'   => $st['lan_goi_cuoi'] ?? null,
            ];
        }

        return $services;
    }

    /**
     * Kiểm tra kết nối (Ping Test) tới dịch vụ tích hợp
     *
     * @param string $maDichVu Mã dịch vụ (VNEID, LGSP, C06, ...)
     * @return array{
     *   success: bool,
     *   ma_dich_vu: string,
     *   status_code: int,
     *   latency_ms: int,
     *   message: string,
     *   response_payload: string
     * }
     */
    public function pingService(string $maDichVu): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM cau_hinh_tich_hop WHERE ma_dich_vu = :ma LIMIT 1");
        $stmt->execute(['ma' => strtoupper($maDichVu)]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            throw new \InvalidArgumentException("Không tìm thấy cấu hình dịch vụ tích hợp: {$maDichVu}");
        }

        $cheDo = $config['che_do'];
        $endpoint = $config['endpoint_url'];
        $startTime = microtime(true);

        $statusCode = 200;
        $isSuccess = true;
        $responsePayload = '';
        $message = '';

        if ($cheDo === 'MOCK') {
            // Chế độ MOCK: Mô phỏng bắt tay SSL/TLS và phản hồi giao thức tương ứng
            // Giả lập độ trễ mạng thực tế từ 15ms đến 65ms
            $simulatedDelayMs = rand(15, 65);
            usleep($simulatedDelayMs * 1000);

            $latencyMs = (int)round((microtime(true) - $startTime) * 1000);

            $responsePayload = json_encode([
                'status'     => 'SUCCESS',
                'service'    => $maDichVu,
                'mode'       => 'MOCK',
                'timestamp'  => date('Y-m-d H:i:s'),
                'latency_ms' => $latencyMs,
                'message'    => "Kết nối kiểm tra MOCK Gateway thành công. Hệ thống sẵn sàng trao đổi dữ liệu.",
            ], JSON_UNESCAPED_UNICODE);

            $message = "Kiểm tra MOCK thành công (Độ trễ: {$latencyMs}ms)";
        } else {
            // Chế độ LIVE: Thực hiện cURL request kiểm tra endpoint thật
            try {
                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => (int)$config['thoi_gian_timeout_giay'],
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_CUSTOMREQUEST  => 'GET',
                    CURLOPT_USERAGENT      => 'HP-eGov-HealthCheck/1.0',
                ]);

                $res = curl_exec($ch);
                $latencyMs = (int)round((microtime(true) - $startTime) * 1000);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError !== '' || $httpCode >= 400 || $httpCode === 0) {
                    $isSuccess = false;
                    $statusCode = $httpCode ?: 503;
                    $message = "Lỗi kết nối LIVE: " . ($curlError ?: "Mã HTTP {$httpCode}");
                    $responsePayload = json_encode(['error' => $message]);
                } else {
                    $statusCode = $httpCode;
                    $message = "Kết nối LIVE thành công (Mã {$httpCode}, Độ trễ: {$latencyMs}ms)";
                    $responsePayload = (string)$res;
                }
            } catch (Throwable $liveEx) {
                $latencyMs = (int)round((microtime(true) - $startTime) * 1000);
                $isSuccess = false;
                $statusCode = 500;
                $message = "Ngoại lệ khi gọi LIVE endpoint: " . $liveEx->getMessage();
                $responsePayload = json_encode(['exception' => $liveEx->getMessage()]);
            }
        }

        // Ghi nhật ký vào nhat_ky_tich_hop
        $stmtLog = $pdo->prepare("
            INSERT INTO nhat_ky_tich_hop (
                dich_vu, endpoint, method, request_payload, response_payload, status_code, thoi_gian_xu_ly_ms
            ) VALUES (
                :dv, :ep, 'PING', :req, :res, :code, :lat
            )
        ");
        $stmtLog->execute([
            'dv'   => $maDichVu,
            'ep'   => $endpoint,
            'req'  => json_encode(['action' => 'PING_TEST', 'mode' => $cheDo]),
            'res'  => $responsePayload,
            'code' => $statusCode,
            'lat'  => $latencyMs,
        ]);

        // Cập nhật trạng thái vào cau_hinh_tich_hop
        $trangThaiKetNoi = $isSuccess ? 'KET_NOI_TOT' : 'LOI_KET_NOI';
        $stmtUpdate = $pdo->prepare("
            UPDATE cau_hinh_tich_hop
            SET trang_thai_ket_noi = :tt, lan_kiem_tra_cuoi = NOW(), thong_tin_phan_hoi = :msg
            WHERE ma_dich_vu = :ma
        ");
        $stmtUpdate->execute([
            'tt'  => $trangThaiKetNoi,
            'msg' => $message,
            'ma'  => $maDichVu,
        ]);

        return [
            'success'          => $isSuccess,
            'ma_dich_vu'       => $maDichVu,
            'status_code'      => $statusCode,
            'latency_ms'       => $latencyMs,
            'message'          => $message,
            'response_payload' => $responsePayload,
        ];
    }

    /**
     * Cập nhật thông số kết nối dịch vụ
     *
     * @param string $maDichVu
     * @param array{
     *   endpoint_url?: string,
     *   che_do?: string,
     *   api_key?: string,
     *   secret_key?: string,
     *   thoi_gian_timeout_giay?: int
     * } $data
     * @return bool
     */
    public function updateConfig(string $maDichVu, array $data): bool
    {
        $pdo = Database::pdo();
        $fields = [];
        $params = ['ma' => strtoupper($maDichVu)];

        if (isset($data['endpoint_url'])) {
            $fields[] = 'endpoint_url = :endpoint';
            $params['endpoint'] = trim($data['endpoint_url']);
        }
        if (isset($data['che_do'])) {
            $fields[] = 'che_do = :che_do';
            $params['che_do'] = strtoupper($data['che_do']) === 'LIVE' ? 'LIVE' : 'MOCK';
        }
        if (isset($data['api_key'])) {
            $fields[] = 'api_key = :api_key';
            $params['api_key'] = trim($data['api_key']);
        }
        if (isset($data['secret_key'])) {
            $fields[] = 'secret_key = :secret_key';
            $params['secret_key'] = trim($data['secret_key']);
        }
        if (isset($data['thoi_gian_timeout_giay'])) {
            $fields[] = 'thoi_gian_timeout_giay = :timeout';
            $params['timeout'] = (int)$data['thoi_gian_timeout_giay'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE cau_hinh_tich_hop SET " . implode(', ', $fields) . " WHERE ma_dich_vu = :ma";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Lấy danh sách 20 giao dịch nhật ký tích hợp gần nhất
     *
     * @param string|null $filterDichVu
     * @return array<int, array>
     */
    public function getRecentLogs(?string $filterDichVu = null): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT * FROM nhat_ky_tich_hop";
        $params = [];

        if ($filterDichVu) {
            $sql .= " WHERE dich_vu = :dv";
            $params['dv'] = strtoupper($filterDichVu);
        }

        $sql .= " ORDER BY id DESC LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
