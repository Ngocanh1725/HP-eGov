<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Integrations\Contracts\SignatureProviderInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class OpenSslSignatureProvider – Triển khai Ký số OpenSSL thuần (Mock dùng OpenSSL thật)
 * Tuân thủ Rule mục 12: Dùng OpenSSL thật, lưu khóa và chứng thư demo trong storage/keys/
 */
class OpenSslSignatureProvider implements SignatureProviderInterface
{
    private array $sslConfig = [];

    public function __construct()
    {
        $this->detectOpenSslConfig();
    }

    /**
     * Tự động dò tìm file openssl.cnf trên môi trường Windows / XAMPP / Linux
     */
    private function detectOpenSslConfig(): void
    {
        $candidates = [
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            'C:\\xampp\\apache\\bin\\openssl.cnf',
            'C:/Program Files/Common Files/SSL/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $this->sslConfig = ['config' => $candidate];
                break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function sign(string $data, string $privateKeyPem, string $passphrase = ''): string
    {
        $keyResource = openssl_pkey_get_private($privateKeyPem, $passphrase);
        if ($keyResource === false) {
            throw new InvalidArgumentException("Khóa riêng RSA không hợp lệ hoặc sai mật khẩu: " . openssl_error_string());
        }

        $binarySignature = '';
        $success = openssl_sign($data, $binarySignature, $keyResource, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new RuntimeException("Ký số OpenSSL thất bại: " . openssl_error_string());
        }

        return base64_encode($binarySignature);
    }

    /**
     * @inheritDoc
     */
    public function verify(string $data, string $signatureBase64, string $certOrPubKeyPem): bool
    {
        $pubKey = openssl_pkey_get_public($certOrPubKeyPem);
        if ($pubKey === false) {
            return false;
        }

        $binarySignature = base64_decode($signatureBase64, true);
        if ($binarySignature === false) {
            return false;
        }

        $result = openssl_verify($data, $binarySignature, $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    /**
     * @inheritDoc
     */
    public function generateKeyPair(array $dn = [], int $days = 1095): array
    {
        $defaultDn = [
            "countryName"            => "VN",
            "stateOrProvinceName"    => "Hai Phong",
            "localityName"           => "Hai Phong",
            "organizationName"       => "UBND TP Hai Phong",
            "organizationalUnitName" => "He thong HP-eGov",
            "commonName"             => "Can bo HP-eGov",
            "emailAddress"           => "admin@haiphong.gov.vn",
        ];

        $subject = array_merge($defaultDn, $dn);

        $configArgs = array_merge([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ], $this->sslConfig);

        $privKeyResource = openssl_pkey_new($configArgs);
        if ($privKeyResource === false) {
            throw new RuntimeException("Không thể khởi tạo cặp khóa RSA: " . openssl_error_string());
        }

        $csrConfigArgs = array_merge(['digest_alg' => 'sha256'], $this->sslConfig);
        $csr = openssl_csr_new($subject, $privKeyResource, $csrConfigArgs);
        if ($csr === false) {
            throw new RuntimeException("Không thể tạo Certificate Signing Request (CSR): " . openssl_error_string());
        }

        $serialInt = random_int(10000000, 2147483647);
        $serialHex = strtoupper(dechex($serialInt));

        $signConfigArgs = array_merge(['digest_alg' => 'sha256'], $this->sslConfig);
        $x509 = openssl_csr_sign($csr, null, $privKeyResource, $days, $signConfigArgs, $serialInt);
        if ($x509 === false) {
            throw new RuntimeException("Không thể ký tạo chứng thư X.509: " . openssl_error_string());
        }

        $privateKeyPem = '';
        openssl_pkey_export($privKeyResource, $privateKeyPem, null, $this->sslConfig);

        $certPem = '';
        openssl_x509_export($x509, $certPem);

        return [
            'private_key' => $privateKeyPem,
            'cert_pem'    => $certPem,
            'serial'      => $serialHex,
        ];
    }
}
