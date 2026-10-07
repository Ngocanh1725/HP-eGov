<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Core\Database;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class SmartCaProviderMock – Giả lập Cổng ký số từ xa SmartCA (VNPT SmartCA / Viettel-CA / Ban Cơ yếu)
 * Tuân thủ Rule mục 12: Adapter Pattern, mô phỏng xác nhận OTP/PIN SmartCA
 */
class SmartCaProviderMock
{
    private OpenSslSignatureProvider $provider;

    public function __construct()
    {
        $this->provider = new OpenSslSignatureProvider();
    }

    /**
     * Xác thực mã PIN SmartCA của người dùng
     *
     * @param string $pin Mã PIN (mặc định môi trường demo: 123456 hoặc 6 ký tự số)
     * @return bool
     */
    public function verifyPin(string $pin): bool
    {
        // Trong môi trường sandbox / demo: PIN hợp lệ chuẩn là "123456"
        return $pin === '123456' || (strlen($pin) === 6 && ctype_digit($pin) && $pin !== '000000');
    }

    /**
     * Ký số từ xa qua SmartCA với xác thực PIN
     *
     * @param string $dataHash Mã băm SHA-256 của tài liệu cần ký
     * @param string $pin Mã PIN SmartCA của người ký
     * @param string $privateKeyPem Khóa riêng lưu trữ trong HSM/KMS của SmartCA
     * @param array $metadata Thông tin bối cảnh giao dịch ký số
     * @return array{success: bool, signature: string, transaction_id: string, message: string}
     */
    public function signWithPin(string $dataHash, string $pin, string $privateKeyPem, array $metadata = []): array
    {
        $start = microtime(true);

        if (!$this->verifyPin($pin)) {
            $this->logIntegration('SMARTCA', 'SIGN_REQUEST', ['hash' => $dataHash, 'meta' => $metadata], ['error' => 'PIN_INVALID'], 400, microtime(true) - $start);
            throw new InvalidArgumentException("Mã PIN SmartCA không chính xác. Vui lòng kiểm tra lại (PIN mẫu: 123456)!");
        }

        $signatureBase64 = $this->provider->sign($dataHash, $privateKeyPem);
        $txId = 'SMARTCA-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $duration = microtime(true) - $start;
        $this->logIntegration('SMARTCA', 'SIGN_SUCCESS', [
            'hash' => $dataHash,
            'tx_id' => $txId,
        ], [
            'status' => 'SUCCESS',
            'signature_len' => strlen($signatureBase64),
        ], 200, $duration);

        return [
            'success' => true,
            'signature' => $signatureBase64,
            'transaction_id' => $txId,
            'message' => 'Ký số từ xa qua SmartCA thành công',
            'duration' => round($duration, 4),
        ];
    }

    /**
     * Ghi nhật ký tích hợp vào nhat_ky_tich_hop
     */
    private function logIntegration(string $heThong, string $hanhDong, array $req, array $res, int $statusCode, float $duration): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO nhat_ky_tich_hop (
                he_thong, hanh_dong, du_lieu_gui, du_lieu_nhan, ma_trang_thai, thoi_gian_xu_ly_ms, created_at
            ) VALUES (
                :sys, :act, :req, :res, :st, :ms, NOW()
            )");
            $stmt->execute([
                ':sys' => $heThong,
                ':act' => $hanhDong,
                ':req' => json_encode($req, JSON_UNESCAPED_UNICODE),
                ':res' => json_encode($res, JSON_UNESCAPED_UNICODE),
                ':st'  => $statusCode,
                ':ms'  => (int)round($duration * 1000),
            ]);
        } catch (\Throwable $e) {
            // Không ngắt luồng nếu lỗi ghi log tích hợp
        }
    }
}
