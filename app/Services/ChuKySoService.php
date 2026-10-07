<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Class ChuKySoService – Dịch vụ Ký số điện tử & Xác thực chứng thư số
 * Tuân thủ Rule mục 12: Dùng OpenSSL thật, lưu khóa và chứng thư demo trong storage/keys/
 */
class ChuKySoService
{
    private static string $keysDir = '';

    private static function initKeysDir(): string
    {
        if (empty(self::$keysDir)) {
            self::$keysDir = dirname(__DIR__, 2) . '/storage/keys';
            if (!is_dir(self::$keysDir)) {
                mkdir(self::$keysDir, 0755, true);
            }
        }
        return self::$keysDir;
    }

    /**
     * Lấy hoặc khởi tạo tự động cặp khóa RSA & chứng thư X.509 demo cho người dùng
     *
     * @param int $userId ID người dùng
     * @param string $hoTen Họ tên người dùng
     * @return array{private_key_path: string, cert_path: string}
     */
    public static function getOrCreateUserCertificate(int $userId, string $hoTen = 'Cán bộ HP-eGov'): array
    {
        $dir = self::initKeysDir();
        $keyPath = "{$dir}/user_{$userId}.key";
        $certPath = "{$dir}/user_{$userId}.crt";

        if (file_exists($keyPath) && file_exists($certPath)) {
            return [
                'private_key_path' => $keyPath,
                'cert_path' => $certPath,
            ];
        }

        // Tạo cặp khóa RSA 2048-bit mới
        $dn = [
            "countryName" => "VN",
            "stateOrProvinceName" => "Hai Phong",
            "localityName" => "Hai Phong",
            "organizationName" => "UBND TP Hai Phong",
            "organizationalUnitName" => "Bo phan Mot cua",
            "commonName" => $hoTen,
            "emailAddress" => "canbo{$userId}@haiphong.gov.vn"
        ];

        // Tự động tìm file openssl.cnf trên Windows / XAMPP nếu cần
        $sslConfig = [];
        $candidates = [
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            'C:\\xampp\\apache\\bin\\openssl.cnf',
        ];
        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $sslConfig['config'] = $cand;
                break;
            }
        }

        $privKey = openssl_pkey_new(array_merge([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ], $sslConfig));

        if ($privKey === false) {
            throw new \RuntimeException("Không thể tạo cặp khóa OpenSSL: " . openssl_error_string());
        }

        $csr = openssl_csr_new($dn, $privKey, array_merge(['digest_alg' => 'sha256'], $sslConfig));
        $x509 = openssl_csr_sign($csr, null, $privKey, 365, array_merge(['digest_alg' => 'sha256'], $sslConfig));

        openssl_pkey_export_to_file($privKey, $keyPath, null, $sslConfig);
        openssl_x509_export_to_file($x509, $certPath);

        return [
            'private_key_path' => $keyPath,
            'cert_path' => $certPath,
        ];
    }

    /**
     * Ký số tài liệu bằng chứng thư cá nhân cán bộ
     *
     * @param int $userId ID cán bộ ký
     * @param string $filePath Đường dẫn tệp cần ký
     * @param string $loaiChuKy Loại chữ ký (SO_HOA, DUYET_BUOC_1, PHAT_HANH, DONG_DAU_CO_QUAN)
     * @param int|null $hoSoTaiLieuId ID tài liệu trong bảng ho_so_tai_lieu (nếu có)
     * @param string $ghiChu Ghi chú thêm
     * @return array{id: int, sha256: string, signature: string, cert_path: string}
     */
    public static function signFile(
        int $userId,
        string $filePath,
        string $loaiChuKy = 'SO_HOA',
        ?int $hoSoTaiLieuId = null,
        string $ghiChu = 'Số hóa đầu vào theo NĐ 107/2021/NĐ-CP'
    ): array {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Tệp cần ký không tồn tại: {$filePath}");
        }

        $db = Database::connection();
        $user = $db->query("SELECT ho_ten FROM nguoi_dung WHERE id = {$userId}")->fetch(PDO::FETCH_ASSOC);
        $hoTen = $user['ho_ten'] ?? 'Cán bộ Một cửa';

        $keys = self::getOrCreateUserCertificate($userId, $hoTen);
        $privateKeyStr = file_get_contents($keys['private_key_path']);
        $privateKey = openssl_pkey_get_private($privateKeyStr);

        $sha256 = hash_file('sha256', $filePath);
        $binarySignature = '';
        $success = openssl_sign($sha256, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new \RuntimeException("Ký số thất bại bằng khóa riêng OpenSSL.");
        }

        $signatureBase64 = base64_encode($binarySignature);

        // Lưu vào bảng chu_ky_so
        $stmt = $db->prepare("INSERT INTO chu_ky_so (
            ho_so_tai_lieu_id, loai_chu_ky, nguoi_ky_id, sha256_truoc_ky, 
            chu_ky_binary_base64, thoi_gian_ky, ket_qua_xac_thuc, ghi_chu
        ) VALUES (
            :tlid, :loai, :uid, :sha, :sig, NOW(), 1, :gc
        )");

        $stmt->execute([
            ':tlid' => $hoSoTaiLieuId,
            ':loai' => $loaiChuKy,
            ':uid' => $userId,
            ':sha' => $sha256,
            ':sig' => $signatureBase64,
            ':gc' => $ghiChu,
        ]);

        $signatureId = (int)$db->lastInsertId();

        // Cập nhật lại chu_ky_so_id trong ho_so_tai_lieu nếu có
        if ($hoSoTaiLieuId) {
            $db->prepare("UPDATE ho_so_tai_lieu SET chu_ky_so_id = :sid WHERE id = :tlid")
               ->execute([':sid' => $signatureId, ':tlid' => $hoSoTaiLieuId]);
        }

        return [
            'id' => $signatureId,
            'sha256' => $sha256,
            'signature' => $signatureBase64,
            'cert_path' => $keys['cert_path'],
        ];
    }

    /**
     * Xác thực chữ ký số bằng chứng thư công khai
     */
    public static function verifySignature(string $data, string $signatureBase64, string $certPath): bool
    {
        if (!file_exists($certPath)) {
            return false;
        }
        $certStr = file_get_contents($certPath);
        $pubKey = openssl_pkey_get_public($certStr);
        if (!$pubKey) {
            return false;
        }

        $binarySignature = base64_decode($signatureBase64);
        $result = openssl_verify($data, $binarySignature, $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
}
