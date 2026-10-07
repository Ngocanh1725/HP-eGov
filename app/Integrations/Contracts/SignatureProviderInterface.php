<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

/**
 * Interface SignatureProviderInterface – Hợp đồng Ký số và Xác thực chữ ký số điện tử
 * Tuân thủ Rule mục 12: Adapter Pattern (Mock / Live)
 */
interface SignatureProviderInterface
{
    /**
     * Ký số chuỗi dữ liệu hoặc hash bằng khóa riêng RSA
     *
     * @param string $data Dữ liệu hoặc hash SHA-256 cần ký
     * @param string $privateKeyPem Nội dung khóa riêng PEM hoặc đường dẫn file
     * @param string $passphrase Mật khẩu khóa riêng nếu có
     * @return string Chữ ký số mã hóa Base64
     */
    public function sign(string $data, string $privateKeyPem, string $passphrase = ''): string;

    /**
     * Xác thực chữ ký số bằng chứng thư công khai PEM hoặc Public Key
     *
     * @param string $data Dữ liệu gốc hoặc hash SHA-256 đã ký
     * @param string $signatureBase64 Chữ ký số mã hóa Base64
     * @param string $certOrPubKeyPem Chứng thư X.509 PEM hoặc Public Key PEM
     * @return bool True nếu chữ ký hợp lệ và nguyên vẹn
     */
    public function verify(string $data, string $signatureBase64, string $certOrPubKeyPem): bool;

    /**
     * Sinh cặp khóa RSA và chứng thư số tự ký X.509
     *
     * @param array $dn Thông tin Distinguished Name (CN, O, OU, C, ST, L, Email)
     * @param int $days Thời hạn hiệu lực (mặc định 1095 ngày = 3 năm)
     * @return array{private_key: string, cert_pem: string, serial: string}
     */
    public function generateKeyPair(array $dn = [], int $days = 1095): array;
}
