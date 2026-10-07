<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

/**
 * EformSignerMock – Giả lập ký số biểu mẫu điện tử E-Form cho Công dân / Doanh nghiệp
 * Sử dụng chữ ký điện tử số hóa SHA-256 kèm chứng thư số demo (mô phỏng SmartCA / USB Token)
 */
class EformSignerMock
{
    /**
     * Thực hiện ký số dữ liệu biểu mẫu E-Form
     *
     * @param array $eformData Dữ liệu tờ khai E-Form
     * @param string $soCccd Số CCCD hoặc Mã số thuế người ký
     * @param string $hoTen Họ tên cá nhân hoặc tên Doanh nghiệp
     * @return array{
     *   signer_name: string,
     *   signer_id: string,
     *   signed_at: string,
     *   algorithm: string,
     *   signature_hash: string,
     *   certificate_authority: string
     * }
     */
    public static function sign(array $eformData, string $soCccd, string $hoTen): array
    {
        $signedAt = date('Y-m-d H:i:s');
        $canonicalJson = json_encode($eformData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Tạo chuỗi ký số mô phỏng
        $signPayload = "SIGNER:{$soCccd}|NAME:{$hoTen}|TIME:{$signedAt}|DATA:{$canonicalJson}";
        $signatureHash = hash('sha256', $signPayload);

        return [
            'signer_name' => $hoTen,
            'signer_id' => $soCccd,
            'signed_at' => $signedAt,
            'algorithm' => 'SHA256withRSA',
            'signature_hash' => $signatureHash,
            'certificate_authority' => 'VNPT SmartCA / Cổng DVC Hải Phòng CA',
        ];
    }
}
