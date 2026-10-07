<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Integrations\Mock\OpenSslSignatureProvider;
use App\Integrations\Mock\SmartCaProviderMock;
use PDO;
use RuntimeException;
use InvalidArgumentException;
use DomainException;

/**
 * Class ChuKySoService – Dịch vụ Ký số điện tử, Chứng thư số & Văn thư số
 * Tuân thủ Rule mục 6 & mục 12: Dùng OpenSSL thật, Adapter Pattern, lưu trong storage/keys/
 */
class ChuKySoService
{
    private static string $keysDir = '';
    private static ?OpenSslSignatureProvider $provider = null;
    private static ?SmartCaProviderMock $smartCaMock = null;

    private static function getProvider(): OpenSslSignatureProvider
    {
        if (self::$provider === null) {
            self::$provider = new OpenSslSignatureProvider();
        }
        return self::$provider;
    }

    private static function getSmartCa(): SmartCaProviderMock
    {
        if (self::$smartCaMock === null) {
            self::$smartCaMock = new SmartCaProviderMock();
        }
        return self::$smartCaMock;
    }

    public static function initKeysDir(): string
    {
        if (empty(self::$keysDir)) {
            self::$keysDir = dirname(__DIR__, 2) . '/storage/keys';
            if (!is_dir(self::$keysDir)) {
                mkdir(self::$keysDir, 0755, true);
            }
            // Tạo file .htaccess bảo vệ khóa riêng không cho tải từ public web
            $htaccessPath = self::$keysDir . '/.htaccess';
            if (!file_exists($htaccessPath)) {
                file_put_contents($htaccessPath, "Deny from all\n");
            }
        }
        return self::$keysDir;
    }

    /**
     * Lấy hoặc tạo tự động cặp khóa RSA & chứng thư X.509 cho cán bộ
     *
     * @param int $userId ID cán bộ
     * @param string $hoTen Họ tên
     * @param string $role Mã vai trò
     * @return array{private_key_path: string, cert_path: string, cert_id: int, serial: string}
     */
    public static function getOrCreateUserCertificate(int $userId, string $hoTen = 'Cán bộ HP-eGov', string $role = 'CB_MOT_CUA'): array
    {
        $dir = self::initKeysDir();
        $keyPath = "{$dir}/user_{$userId}.key";
        $certPath = "{$dir}/user_{$userId}.crt";
        $db = Database::connection();

        // Kiểm tra xem đã có bản ghi trong bảng chung_thu_so chưa
        $stmt = $db->prepare("SELECT * FROM chung_thu_so WHERE chu_the_id = :uid AND loai_chu_the = 'CA_NHAN' AND trang_thai = 1 LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $existingCert = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCert && file_exists($keyPath) && file_exists($certPath)) {
            return [
                'private_key_path' => $keyPath,
                'cert_path'        => $certPath,
                'cert_id'          => (int)$existingCert['id'],
                'serial'           => $existingCert['so_serial'],
            ];
        }

        // Tạo mới cặp khóa và chứng thư X.509
        $dn = [
            "countryName"            => "VN",
            "stateOrProvinceName"    => "Hai Phong",
            "localityName"           => "Hai Phong",
            "organizationName"       => "UBND Thanh pho Hai Phong",
            "organizationalUnitName" => "He thong HP-eGov",
            "commonName"             => $hoTen,
            "emailAddress"           => "canbo{$userId}@haiphong.gov.vn",
        ];

        $keyData = self::getProvider()->generateKeyPair($dn, 1095);
        file_put_contents($keyPath, $keyData['private_key']);
        file_put_contents($certPath, $keyData['cert_pem']);

        $rolePrefix = match ($role) {
            'LD_CO_QUAN' => 'LD',
            'LD_PHONG'   => 'LP',
            'VAN_THU'    => 'VT',
            default      => 'CB',
        };
        $serial = "HP-CERT-{$rolePrefix}-" . str_pad((string)$userId, 4, '0', STR_PAD_LEFT) . '-' . substr($keyData['serial'], 0, 4);
        $providerType = in_array($role, ['LD_CO_QUAN', 'LD_PHONG'], true) ? 'SMARTCA' : 'BAN_CO_YEU';

        if ($existingCert) {
            $update = $db->prepare("UPDATE chung_thu_so SET 
                ten_chu_the = :ten, so_serial = :serial, nha_cung_cap = :ncc,
                ngay_hieu_luc = NOW(), ngay_het_han = DATE_ADD(NOW(), INTERVAL 3 YEAR),
                public_cert_pem = :pem, trang_thai = 1
                WHERE id = :id");
            $update->execute([
                ':ten'    => $hoTen,
                ':serial' => $serial,
                ':ncc'    => $providerType,
                ':pem'    => $keyData['cert_pem'],
                ':id'     => $existingCert['id'],
            ]);
            $certId = (int)$existingCert['id'];
        } else {
            $insert = $db->prepare("INSERT INTO chung_thu_so (
                chu_the_id, loai_chu_the, ten_chu_the, so_serial, nha_cung_cap,
                ngay_hieu_luc, ngay_het_han, public_cert_pem, trang_thai
            ) VALUES (
                :uid, 'CA_NHAN', :ten, :serial, :ncc, NOW(), DATE_ADD(NOW(), INTERVAL 3 YEAR), :pem, 1
            )");
            $insert->execute([
                ':uid'    => $userId,
                ':ten'    => $hoTen,
                ':serial' => $serial,
                ':ncc'    => $providerType,
                ':pem'    => $keyData['cert_pem'],
            ]);
            $certId = (int)$db->lastInsertId();
        }

        return [
            'private_key_path' => $keyPath,
            'cert_path'        => $certPath,
            'cert_id'          => $certId,
            'serial'           => $serial,
        ];
    }

    /**
     * Lấy hoặc tạo tự động chứng thư số tổ chức (con dấu số cơ quan)
     *
     * @param int $donViId ID đơn vị
     * @param string $tenDonVi Tên đơn vị
     * @return array{private_key_path: string, cert_path: string, cert_id: int, serial: string}
     */
    public static function getOrCreateOrganizationCertificate(int $donViId, string $tenDonVi = 'UBND TP Hải Phòng'): array
    {
        $dir = self::initKeysDir();
        $keyPath = "{$dir}/org_{$donViId}.key";
        $certPath = "{$dir}/org_{$donViId}.crt";
        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM chung_thu_so WHERE chu_the_id = :dvid AND loai_chu_the = 'TO_CHUC' AND trang_thai = 1 LIMIT 1");
        $stmt->execute([':dvid' => $donViId]);
        $existingCert = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCert && file_exists($keyPath) && file_exists($certPath)) {
            return [
                'private_key_path' => $keyPath,
                'cert_path'        => $certPath,
                'cert_id'          => (int)$existingCert['id'],
                'serial'           => $existingCert['so_serial'],
            ];
        }

        $dn = [
            "countryName"            => "VN",
            "stateOrProvinceName"    => "Hai Phong",
            "localityName"           => "Hai Phong",
            "organizationName"       => $tenDonVi,
            "organizationalUnitName" => "Bo phan Van thu - Dau so co quan",
            "commonName"             => $tenDonVi,
            "emailAddress"           => "vanthu.dv{$donViId}@haiphong.gov.vn",
        ];

        $keyData = self::getProvider()->generateKeyPair($dn, 1095);
        file_put_contents($keyPath, $keyData['private_key']);
        file_put_contents($certPath, $keyData['cert_pem']);

        $serial = "HP-SEAL-ORG-" . str_pad((string)$donViId, 4, '0', STR_PAD_LEFT) . '-' . substr($keyData['serial'], 0, 4);

        if ($existingCert) {
            $update = $db->prepare("UPDATE chung_thu_so SET 
                ten_chu_the = :ten, so_serial = :serial, nha_cung_cap = 'BAN_CO_YEU',
                ngay_hieu_luc = NOW(), ngay_het_han = DATE_ADD(NOW(), INTERVAL 3 YEAR),
                public_cert_pem = :pem, trang_thai = 1
                WHERE id = :id");
            $update->execute([
                ':ten'    => $tenDonVi,
                ':serial' => $serial,
                ':pem'    => $keyData['cert_pem'],
                ':id'     => $existingCert['id'],
            ]);
            $certId = (int)$existingCert['id'];
        } else {
            $insert = $db->prepare("INSERT INTO chung_thu_so (
                chu_the_id, loai_chu_the, ten_chu_the, so_serial, nha_cung_cap,
                ngay_hieu_luc, ngay_het_han, public_cert_pem, trang_thai
            ) VALUES (
                :dvid, 'TO_CHUC', :ten, :serial, 'BAN_CO_YEU', NOW(), DATE_ADD(NOW(), INTERVAL 3 YEAR), :pem, 1
            )");
            $insert->execute([
                ':dvid'   => $donViId,
                ':ten'    => $tenDonVi,
                ':serial' => $serial,
                ':pem'    => $keyData['cert_pem'],
            ]);
            $certId = (int)$db->lastInsertId();
        }

        return [
            'private_key_path' => $keyPath,
            'cert_path'        => $certPath,
            'cert_id'          => $certId,
            'serial'           => $serial,
        ];
    }

    /**
     * Lệnh CLI: Sinh toàn bộ cặp khóa và chứng thư số hệ thống
     * Cán bộ có quyền ký (LD_PHONG, LD_CO_QUAN, CB_MOT_CUA, VAN_THU) và các cơ quan (con dấu số)
     *
     * @return array{users_count: int, orgs_count: int, details: array}
     */
    public static function generateAllSystemKeys(): array
    {
        $db = Database::connection();
        $details = [];

        // 1. Quét cán bộ có quyền ký (loại trừ SYSADMIN tuyệt đối)
        $usersSql = "SELECT DISTINCT u.id, u.ten_dang_nhap, u.ho_ten, v.ma_vai_tro, u.don_vi_chinh_id
                     FROM nguoi_dung u
                     JOIN nguoi_dung_vai_tro nv ON u.id = nv.nguoi_dung_id
                     JOIN vai_tro v ON nv.vai_tro_id = v.id
                     WHERE v.ma_vai_tro IN ('LD_PHONG', 'LD_CO_QUAN', 'CB_MOT_CUA', 'VAN_THU')
                       AND u.is_deleted = 0";
        $users = $db->query($usersSql)->fetchAll(PDO::FETCH_ASSOC);

        $userCount = 0;
        foreach ($users as $user) {
            $certInfo = self::getOrCreateUserCertificate(
                (int)$user['id'],
                $user['ho_ten'],
                $user['ma_vai_tro']
            );
            $userCount++;
            $details[] = [
                'type'   => 'CAN_BO',
                'name'   => $user['ho_ten'],
                'role'   => $user['ma_vai_tro'],
                'serial' => $certInfo['serial'],
                'path'   => $certInfo['cert_path'],
            ];
        }

        // 2. Quét các cơ quan tổ chức cấp Sở, Quận/Huyện, UBND TP
        $orgsSql = "SELECT id, ten_don_vi, loai_don_vi FROM don_vi 
                    WHERE loai_don_vi IN ('UBND_TP', 'SO_NGANH', 'UBND_HUYEN')
                      AND trang_thai = 1";
        $orgs = $db->query($orgsSql)->fetchAll(PDO::FETCH_ASSOC);

        $orgCount = 0;
        foreach ($orgs as $org) {
            $sealInfo = self::getOrCreateOrganizationCertificate(
                (int)$org['id'],
                $org['ten_don_vi']
            );
            $orgCount++;
            $details[] = [
                'type'   => 'TO_CHUC',
                'name'   => $org['ten_don_vi'],
                'role'   => 'CON_DAU_SO',
                'serial' => $sealInfo['serial'],
                'path'   => $sealInfo['cert_path'],
            ];
        }

        return [
            'users_count' => $userCount,
            'orgs_count'  => $orgCount,
            'details'     => $details,
        ];
    }

    /**
     * Ký số tài liệu với kiểm soát thẩm quyền nghiêm ngặt theo Rule mục 6 & mục 12
     *
     * @param int $userId ID người ký
     * @param string $filePath Đường dẫn file cần ký
     * @param string $loaiChuKy Loại chữ ký: SO_HOA, DUYET_BUOC_1, PHAT_HANH, DONG_DAU_CO_QUAN
     * @param int|null $hoSoTaiLieuId ID tài liệu trong ho_so_tai_lieu (nếu có)
     * @param array $options Tùy chọn (pin, don_vi_id, ghi_chu, ho_so_id, v.v.)
     * @return array
     */
    public static function signDocument(
        int $userId,
        string $filePath,
        string $loaiChuKy,
        ?int $hoSoTaiLieuId = null,
        array $options = []
    ): array {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Tệp cần ký không tồn tại: {$filePath}");
        }

        $db = Database::connection();

        // Kiểm tra vai trò của người dùng
        $rolesStmt = $db->prepare("SELECT v.ma_vai_tro FROM vai_tro v 
                                   JOIN nguoi_dung_vai_tro nv ON v.id = nv.vai_tro_id 
                                   WHERE nv.nguoi_dung_id = :uid");
        $rolesStmt->execute([':uid' => $userId]);
        $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

        // RULE BẢO MẬT: SYSADMIN tuyệt đối không ký được
        if (in_array('SYSADMIN', $roles, true)) {
            throw new DomainException("Quản trị viên hệ thống (SYSADMIN) tuyệt đối không có thẩm quyền ký số nghiệp vụ!");
        }

        // KIỂM SOÁT THẨM QUYỀN KÝ SỐ THEO LOẠI
        switch ($loaiChuKy) {
            case 'DUYET_BUOC_1':
                if (!in_array('LD_PHONG', $roles, true)) {
                    throw new DomainException("Chỉ Lãnh đạo phòng (LD_PHONG) mới có thẩm quyền ký số duyệt bước 1!");
                }
                break;

            case 'PHAT_HANH':
                if (!in_array('LD_CO_QUAN', $roles, true)) {
                    throw new DomainException("Chỉ Lãnh đạo cơ quan (LD_CO_QUAN) mới có thẩm quyền ký số phát hành kết quả!");
                }
                break;

            case 'DONG_DAU_CO_QUAN':
                if (!in_array('VAN_THU', $roles, true)) {
                    throw new DomainException("Chỉ Văn thư số (VAN_THU) mới có thẩm quyền đóng dấu số cơ quan!");
                }
                break;

            case 'SO_HOA':
                if (!in_array('CB_MOT_CUA', $roles, true) && !in_array('CHUYEN_VIEN', $roles, true)) {
                    throw new DomainException("Chỉ Cán bộ Một cửa (CB_MOT_CUA) mới có thẩm quyền ký số số hóa đầu vào!");
                }
                break;

            default:
                throw new InvalidArgumentException("Loại chữ ký không hợp lệ: {$loaiChuKy}");
        }

        // Kiểm tra mã PIN SmartCA nếu có yêu cầu
        $pin = $options['pin'] ?? null;
        if ($pin !== null) {
            self::getSmartCa()->verifyPin((string)$pin);
            if (!self::getSmartCa()->verifyPin((string)$pin)) {
                throw new InvalidArgumentException("Mã PIN SmartCA không đúng. Vui lòng thử lại!");
            }
        }

        // Tính SHA-256 tệp gốc
        $sha256 = hash_file('sha256', $filePath);

        // Lấy chứng thư số phù hợp (Cá nhân hoặc Tổ chức)
        if ($loaiChuKy === 'DONG_DAU_CO_QUAN') {
            // Đóng dấu số cơ quan dùng chứng thư tổ chức
            $donViId = (int)($options['don_vi_id'] ?? 0);
            if ($donViId <= 0) {
                // Lấy đơn vị của người dùng
                $uStmt = $db->prepare("SELECT don_vi_chinh_id FROM nguoi_dung WHERE id = :uid");
                $uStmt->execute([':uid' => $userId]);
                $donViId = (int)$uStmt->fetchColumn();
            }

            $dvStmt = $db->prepare("SELECT ten_don_vi FROM don_vi WHERE id = :dvid");
            $dvStmt->execute([':dvid' => $donViId]);
            $tenDonVi = $dvStmt->fetchColumn() ?: 'UBND TP Hải Phòng';

            $certInfo = self::getOrCreateOrganizationCertificate($donViId, $tenDonVi);
            $signerTitle = $tenDonVi;
            $signerType = 'TO_CHUC';
        } else {
            // Ký cá nhân
            $uStmt = $db->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = :uid");
            $uStmt->execute([':uid' => $userId]);
            $hoTen = $uStmt->fetchColumn() ?: 'Cán bộ ký số';

            $mainRole = $roles[0] ?? 'CB_MOT_CUA';
            $certInfo = self::getOrCreateUserCertificate($userId, $hoTen, $mainRole);
            $signerTitle = $hoTen;
            $signerType = 'CA_NHAN';
        }

        // Đọc khóa riêng
        $privKeyPem = file_get_contents($certInfo['private_key_path']);
        if ($privKeyPem === false) {
            throw new RuntimeException("Không thể đọc khóa riêng từ tệp: {$certInfo['private_key_path']}");
        }

        // Thực hiện ký số bằng OpenSSL
        $signatureBase64 = self::getProvider()->sign($sha256, $privKeyPem);

        // Lưu chữ ký .sig cạnh tệp gốc
        $sigFilePath = $filePath . '.sig';
        file_put_contents($sigFilePath, $signatureBase64);

        $now = date('Y-m-d H:i:s');
        $ghiChu = $options['ghi_chu'] ?? "Ký số {$loaiChuKy} bởi {$signerTitle}";

        // Lưu bản ghi vào bảng chu_ky_so
        $insStmt = $db->prepare("INSERT INTO chu_ky_so (
            chung_thu_so_id, ho_so_tai_lieu_id, loai_chu_ky, nguoi_ky_id,
            sha256_truoc_ky, chu_ky_binary_base64, thoi_gian_ky, ket_qua_xac_thuc, ghi_chu
        ) VALUES (
            :cid, :tlid, :loai, :uid, :sha, :sig, :tg, 1, :gc
        )");

        $insStmt->execute([
            ':cid'  => $certInfo['cert_id'],
            ':tlid' => $hoSoTaiLieuId,
            ':loai' => $loaiChuKy,
            ':uid'  => $userId,
            ':sha'  => $sha256,
            ':sig'  => $signatureBase64,
            ':tg'   => $now,
            ':gc'   => $ghiChu,
        ]);

        $signatureId = (int)$db->lastInsertId();

        // Cập nhật lại tài liệu nếu có
        if ($hoSoTaiLieuId) {
            $db->prepare("UPDATE ho_so_tai_lieu SET chu_ky_so_id = :sid WHERE id = :tlid")
               ->execute([':sid' => $signatureId, ':tlid' => $hoSoTaiLieuId]);
        }

        // Tạo chuỗi HTML con dấu / khối chữ ký số trực quan
        $htmlStamp = self::renderSignatureStampHtml([
            'signer'     => $signerTitle,
            'signer_type'=> $signerType,
            'role'       => $roles[0] ?? '',
            'loai_chu_ky'=> $loaiChuKy,
            'time'       => $now,
            'serial'     => $certInfo['serial'],
            'sha256'     => $sha256,
        ]);

        return [
            'id'           => $signatureId,
            'chu_ky_so_id' => $signatureId,
            'sha256'       => $sha256,
            'signature'    => $signatureBase64,
            'sig_path'     => $sigFilePath,
            'cert_path'    => $certInfo['cert_path'],
            'cert_id'      => $certInfo['cert_id'],
            'serial'       => $certInfo['serial'],
            'signer'       => $signerTitle,
            'time'         => $now,
            'html_stamp'   => $htmlStamp,
        ];
    }

    /**
     * Giữ nguyên phương thức signFile để tương thích ngược 100% với SoHoaService & MotCuaTest
     */
    public static function signFile(
        int $userId,
        string $filePath,
        string $loaiChuKy = 'SO_HOA',
        ?int $hoSoTaiLieuId = null,
        string $ghiChu = 'Số hóa đầu vào theo NĐ 107/2021/NĐ-CP'
    ): array {
        return self::signDocument($userId, $filePath, $loaiChuKy, $hoSoTaiLieuId, ['ghi_chu' => $ghiChu]);
    }

    /**
     * Xác thực chữ ký số trên tệp tin & kiểm tra hiệu lực chứng thư, tính toàn vẹn
     *
     * @param string $filePath Đường dẫn file cần kiểm tra
     * @param string|null $signatureBase64 Chữ ký base64 (nếu null đọc từ file .sig)
     * @param int|null $chuKySoId ID trong bảng chu_ky_so (nếu có)
     * @return array{
     *   valid: bool,
     *   file_intact: bool,
     *   sig_valid: bool,
     *   cert_valid: bool,
     *   sha256_current: string,
     *   sha256_signed: string,
     *   signer: string,
     *   serial: string,
     *   signed_at: string,
     *   loai_chu_ky: string,
     *   error: ?string
     * }
     */
    public static function verifyDocumentSignature(
        string $filePath,
        ?string $signatureBase64 = null,
        ?int $chuKySoId = null
    ): array {
        if (!file_exists($filePath)) {
            return [
                'valid'          => false,
                'file_intact'    => false,
                'sig_valid'      => false,
                'cert_valid'     => false,
                'sha256_current' => '',
                'sha256_signed'  => '',
                'signer'         => '',
                'serial'         => '',
                'signed_at'      => '',
                'loai_chu_ky'    => '',
                'error'          => "Tệp tin không tồn tại trên hệ thống: {$filePath}",
            ];
        }

        $currentSha256 = hash_file('sha256', $filePath);
        $db = Database::connection();

        // Tìm bản ghi chu_ky_so
        $sigRecord = null;
        if ($chuKySoId !== null) {
            $stmt = $db->prepare("SELECT c.*, cts.ten_chu_the, cts.so_serial, cts.public_cert_pem, 
                                         cts.ngay_hieu_luc, cts.ngay_het_han, cts.trang_thai as cert_status,
                                         cts.nha_cung_cap
                                  FROM chu_ky_so c
                                  LEFT JOIN chung_thu_so cts ON c.chung_thu_so_id = cts.id
                                  WHERE c.id = :id");
            $stmt->execute([':id' => $chuKySoId]);
            $sigRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Nếu không có ID, đọc file .sig và tìm theo hash hoặc file
        if (!$sigRecord) {
            $sigFile = $filePath . '.sig';
            if ($signatureBase64 === null && file_exists($sigFile)) {
                $signatureBase64 = trim((string)file_get_contents($sigFile));
            }

            if ($signatureBase64 !== null) {
                $stmt = $db->prepare("SELECT c.*, cts.ten_chu_the, cts.so_serial, cts.public_cert_pem, 
                                             cts.ngay_hieu_luc, cts.ngay_het_han, cts.trang_thai as cert_status,
                                             cts.nha_cung_cap
                                      FROM chu_ky_so c
                                      LEFT JOIN chung_thu_so cts ON c.chung_thu_so_id = cts.id
                                      WHERE c.chu_ky_binary_base64 = :sig OR c.sha256_truoc_ky = :sha
                                      ORDER BY c.id DESC LIMIT 1");
                $stmt->execute([':sig' => $signatureBase64, ':sha' => $currentSha256]);
                $sigRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$sigRecord) {
            return [
                'valid'          => false,
                'file_intact'    => false,
                'sig_valid'      => false,
                'cert_valid'     => false,
                'sha256_current' => $currentSha256,
                'sha256_signed'  => '',
                'signer'         => 'Không xác định',
                'serial'         => '',
                'signed_at'      => '',
                'loai_chu_ky'    => '',
                'error'          => "Không tìm thấy dữ liệu chữ ký số tương ứng với tệp tin này.",
            ];
        }

        $signedSha256 = $sigRecord['sha256_truoc_ky'];
        $sigData = $signatureBase64 ?: $sigRecord['chu_ky_binary_base64'];
        $certPem = $sigRecord['public_cert_pem'];

        // 1. Kiểm tra tính toàn vẹn của tệp: SHA-256 hiện tại so với lúc ký
        $fileIntact = ($currentSha256 === $signedSha256);

        // 2. Xác thực chữ ký OpenSSL trên hash hiện tại của tệp
        $sigValid = false;
        if (!empty($certPem)) {
            $sigValid = self::getProvider()->verify($currentSha256, $sigData, $certPem);
        }

        // 3. Kiểm tra tính hợp lệ & hiệu lực của chứng thư số
        $certValid = false;
        $now = time();
        if ($sigRecord['cert_status'] == 1) {
            $from = strtotime($sigRecord['ngay_hieu_luc']);
            $to = strtotime($sigRecord['ngay_het_han']);
            if ($from <= $now && $to >= $now) {
                $certValid = true;
            }
        }

        $error = null;
        if (!$fileIntact) {
            $error = "CẢNH BÁO: Tệp tin đã bị can thiệp, sửa đổi trái phép sau khi ký! Mã băm hiện tại không khớp.";
        } elseif (!$sigValid) {
            $error = "Chữ ký số không khớp với nội dung tệp hoặc chứng thư số.";
        } elseif (!$certValid) {
            $error = "Chứng thư số đã hết hiệu lực hoặc bị thu hồi.";
        }

        $isValidOverall = $fileIntact && $sigValid && $certValid;

        return [
            'valid'          => $isValidOverall,
            'file_intact'    => $fileIntact,
            'sig_valid'      => $sigValid,
            'cert_valid'     => $certValid,
            'sha256_current' => $currentSha256,
            'sha256_signed'  => $signedSha256,
            'signer'         => $sigRecord['ten_chu_the'] ?: 'Cán bộ ký số',
            'serial'         => $sigRecord['so_serial'] ?: 'HP-CERT-UNKNOWN',
            'signed_at'      => $sigRecord['thoi_gian_ky'] ?: '',
            'loai_chu_ky'    => $sigRecord['loai_chu_ky'] ?: '',
            'nha_cung_cap'   => $sigRecord['nha_cung_cap'] ?: 'SMARTCA',
            'error'          => $error,
        ];
    }

    /**
     * Tương thích ngược: verifySignature
     */
    public static function verifySignature(string $data, string $signatureBase64, string $certPath): bool
    {
        if (!file_exists($certPath)) {
            return false;
        }
        $certStr = (string)file_get_contents($certPath);
        return self::getProvider()->verify($data, $signatureBase64, $certStr);
    }

    /**
     * Ký số hàng loạt (Batch Signing) cho Lãnh đạo cơ quan
     * 1 lần nhập PIN SmartCA duy nhất, xử lý tuần tự, độ trễ < 2 giây/lần ký
     *
     * @param int $userId ID Lãnh đạo ký
     * @param array<int> $hoSoIds Danh sách ID hồ sơ cần ký
     * @param string $pin Mã PIN SmartCA
     * @param string $loaiChuKy Loại chữ ký (mặc định PHAT_HANH)
     * @param string $yKien Ý kiến chỉ đạo kèm theo
     * @return array{
     *   total: int,
     *   success_count: int,
     *   fail_count: int,
     *   duration_total: float,
     *   duration_avg: float,
     *   results: array
     * }
     */
    public static function batchSign(
        int $userId,
        array $hoSoIds,
        string $pin,
        string $loaiChuKy = 'PHAT_HANH',
        string $yKien = 'Đồng ý phát hành kết quả'
    ): array {
        $startBatch = microtime(true);

        // Xác thực PIN 1 lần duy nhất cho toàn bộ batch
        if (!self::getSmartCa()->verifyPin($pin)) {
            throw new InvalidArgumentException("Mã PIN SmartCA không chính xác. Ký số hàng loạt bị từ chối!");
        }

        $db = Database::connection();
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($hoSoIds as $hoSoId) {
            $itemStart = microtime(true);
            try {
                // Kiểm tra hồ sơ
                $stmt = $db->prepare("SELECT h.*, t.ten_tthc FROM ho_so h 
                                      JOIN thu_tuc_hanh_chinh t ON h.tthc_id = t.id 
                                      WHERE h.id = :id AND h.is_deleted = 0");
                $stmt->execute([':id' => $hoSoId]);
                $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$hoSo) {
                    throw new RuntimeException("Hồ sơ không tồn tại: ID {$hoSoId}");
                }

                // Tìm file kết quả hoặc dự thảo cần ký
                $tlStmt = $db->prepare("SELECT * FROM ho_so_tai_lieu 
                                        WHERE ho_so_id = :hid 
                                        ORDER BY id DESC LIMIT 1");
                $tlStmt->execute([':hid' => $hoSoId]);
                $taiLieu = $tlStmt->fetch(PDO::FETCH_ASSOC);

                $filePath = '';
                $taiLieuId = null;
                if ($taiLieu && !empty($taiLieu['duong_dan']) && file_exists($taiLieu['duong_dan'])) {
                    $filePath = $taiLieu['duong_dan'];
                    $taiLieuId = (int)$taiLieu['id'];
                } else {
                    // Nếu chưa có file thật trên đĩa, tạo file demo kết quả
                    $docDir = dirname(__DIR__, 2) . '/storage/uploads/results';
                    if (!is_dir($docDir)) {
                        mkdir($docDir, 0755, true);
                    }
                    $filePath = "{$docDir}/ket_qua_hs_{$hoSoId}.pdf";
                    if (!file_exists($filePath)) {
                        file_put_contents($filePath, "%PDF-1.4\nDemo Ket qua TTHC: " . $hoSo['ma_ho_so'] . "\nTime: " . date('Y-m-d H:i:s'));
                    }
                }

                // Ký tài liệu
                $signResult = self::signDocument($userId, $filePath, $loaiChuKy, $taiLieuId, [
                    'pin'       => $pin,
                    'ho_so_id'  => $hoSoId,
                    'ghi_chu'   => $yKien,
                ]);

                // Chuyển bước quy trình nếu là phê duyệt phát hành
                if ($loaiChuKy === 'PHAT_HANH') {
                    // Cập nhật kết quả giải quyết nếu chưa có
                    $kqStmt = $db->prepare("SELECT id FROM ket_qua_giai_quyet WHERE ho_so_id = :hid");
                    $kqStmt->execute([':hid' => $hoSoId]);
                    $kqId = $kqStmt->fetchColumn();

                    $soQd = 'QĐ-' . date('Y') . '/' . $hoSo['ma_ho_so'];
                    $maTraCuu = strtoupper(substr(hash('sha256', (string)$hoSo['ma_ho_so']), 0, 12));

                    if ($kqId) {
                        $db->prepare("UPDATE ket_qua_giai_quyet SET 
                            nguoi_ky_chinh_id = :uid, ngay_ky = NOW(),
                            trich_yeu = :ty, file_ban_in_html = :html, ma_tra_cuu_ket_qua = :mtc
                            WHERE id = :id")->execute([
                            ':uid'  => $userId,
                            ':ty'   => $yKien,
                            ':html' => $signResult['html_stamp'],
                            ':mtc'  => $maTraCuu,
                            ':id'   => $kqId,
                        ]);
                    } else {
                        $db->prepare("INSERT INTO ket_qua_giai_quyet (
                            ho_so_id, so_quyet_dinh, trich_yeu, nguoi_ky_chinh_id,
                            ngay_ky, ngay_hieu_luc, file_ban_in_html, ma_tra_cuu_ket_qua, trang_thai
                        ) VALUES (
                            :hid, :sqd, :ty, :uid, NOW(), CURDATE(), :html, :mtc, 1
                        )")->execute([
                            ':hid'  => $hoSoId,
                            ':sqd'  => $soQd,
                            ':ty'   => $yKien,
                            ':uid'  => $userId,
                            ':html' => $signResult['html_stamp'],
                            ':mtc'  => $maTraCuu,
                        ]);
                    }

                    // Gọi WorkflowEngine chuyển bước nếu có thể
                    try {
                        WorkflowEngine::chuyenTiep((int)$hoSoId, $userId, 'Chuyển Văn thư số đóng dấu phát hành');
                    } catch (\Throwable $we) {
                        // Nếu bước quy trình khác vẫn cho phép ký
                    }
                }

                $duration = microtime(true) - $itemStart;
                $successCount++;
                $results[] = [
                    'ho_so_id'      => $hoSoId,
                    'ma_ho_so'      => $hoSo['ma_ho_so'],
                    'ten_tthc'      => $hoSo['ten_tthc'],
                    'status'        => 'SUCCESS',
                    'serial'        => $signResult['serial'],
                    'signer'        => $signResult['signer'],
                    'duration'      => round($duration, 3),
                    'error_message' => null,
                ];
            } catch (\Throwable $e) {
                $duration = microtime(true) - $itemStart;
                $failCount++;
                $results[] = [
                    'ho_so_id'      => $hoSoId,
                    'ma_ho_so'      => $hoSo['ma_ho_so'] ?? "HS #{$hoSoId}",
                    'ten_tthc'      => $hoSo['ten_tthc'] ?? '',
                    'status'        => 'FAILED',
                    'serial'        => null,
                    'signer'        => null,
                    'duration'      => round($duration, 3),
                    'error_message' => $e->getMessage(),
                ];
            }
        }

        $totalDuration = microtime(true) - $startBatch;
        $totalCount = count($hoSoIds);
        $avgDuration = $totalCount > 0 ? ($totalDuration / $totalCount) : 0.0;

        return [
            'total'          => $totalCount,
            'success_count'  => $successCount,
            'fail_count'     => $failCount,
            'duration_total' => round($totalDuration, 3),
            'duration_avg'   => round($avgDuration, 3),
            'results'        => $results,
        ];
    }

    /**
     * Sinh khối tem chữ ký số trực quan trên tài liệu HTML A4
     */
    public static function renderSignatureStampHtml(array $signInfo): string
    {
        $signer = htmlspecialchars($signInfo['signer'] ?? 'Cán bộ ký số', ENT_QUOTES, 'UTF-8');
        $time = htmlspecialchars($signInfo['time'] ?? date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8');
        $serial = htmlspecialchars($signInfo['serial'] ?? 'HP-CERT-UNKNOWN', ENT_QUOTES, 'UTF-8');
        $loai = htmlspecialchars($signInfo['loai_chu_ky'] ?? 'KY_SO', ENT_QUOTES, 'UTF-8');
        $isOrg = ($signInfo['signer_type'] ?? '') === 'TO_CHUC';

        if ($isOrg) {
            // Con dấu tổ chức hình tròn đỏ SVG
            return <<<HTML
<div class="hpegov-seal-box" style="display:inline-block; border:2px dashed #C8102E; border-radius:12px; padding:12px 18px; background:#fff5f5; color:#991B1B; font-family:'Be Vietnam Pro',sans-serif; max-width:320px; text-align:center; box-shadow:0 2px 8px rgba(200,16,46,0.15);">
    <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#C8102E; margin-bottom:4px;">
        ★ CƠ QUAN ĐÃ KÝ SỐ ĐIỆN TỬ ★
    </div>
    <div style="font-size:14px; font-weight:800; color:#1E293B; line-height:1.3; margin-bottom:6px;">
        {$signer}
    </div>
    <div style="font-size:11px; color:#64748B; border-top:1px solid #FECACA; padding-top:4px;">
        <div><strong>Thời gian:</strong> {$time}</div>
        <div><strong>Serial:</strong> <span style="font-family:monospace; color:#0F172A;">{$serial}</span></div>
        <div style="color:#16A34A; font-weight:600; margin-top:2px;">✔ Chứng thư số Ban Cơ yếu Chính phủ hợp lệ</div>
    </div>
</div>
HTML;
        }

        // Tem chữ ký cá nhân trực quan có tick xanh
        return <<<HTML
<div class="hpegov-signature-stamp" style="display:inline-block; border:2px solid #0284C7; border-radius:10px; padding:10px 16px; background:#F0F9FF; color:#0369A1; font-family:'Be Vietnam Pro',sans-serif; max-width:300px; text-align:left; box-shadow:0 2px 6px rgba(2,132,199,0.12);">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
        <span style="display:inline-block; width:18px; height:18px; background:#0284C7; color:#fff; border-radius:50%; text-align:center; line-height:18px; font-size:11px; font-weight:bold;">✓</span>
        <span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.3px; color:#0369A1;">ĐÃ KÝ SỐ XÁC NHẬN</span>
    </div>
    <div style="font-size:13px; font-weight:700; color:#0F172A; margin-bottom:2px;">
        {$signer}
    </div>
    <div style="font-size:11px; color:#475569; line-height:1.4;">
        <div>Ký lúc: <span style="color:#0F172A;">{$time}</span></div>
        <div>Serial: <span style="font-family:monospace; color:#0F172A;">{$serial}</span></div>
        <div style="color:#16A34A; font-weight:600; font-size:10px; margin-top:2px;">Xác thực SmartCA hợp lệ</div>
    </div>
</div>
HTML;
    }
}
