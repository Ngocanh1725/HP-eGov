<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\ChuKySoService;
use PDO;
use Throwable;

/**
 * Class LeaderApiController
 * REST API dành cho ứng dụng di động Lãnh đạo (Mobile App §1.1, §2.2)
 * Yêu cầu Bearer Token có scope 'lanh_dao'
 */
class LeaderApiController extends ApiController
{
    /**
     * Danh sách hồ sơ chờ ký duyệt của Lãnh đạo
     */
    public function danhSachChoKy(Request $request): Response
    {
        $userId = $request->getAttribute('api_user_id');
        if (!$userId) {
            return $this->error('Token không liên kết với tài khoản cán bộ lãnh đạo', 403);
        }

        $pdo = Database::pdo();

        // Lấy vai trò cán bộ
        $stmtRole = $pdo->prepare("
            SELECT vt.ma_vai_tro FROM vai_tro vt
            JOIN nguoi_dung_vai_tro ndvt ON vt.id = ndvt.vai_tro_id
            WHERE ndvt.nguoi_dung_id = :uid
        ");
        $stmtRole->execute(['uid' => $userId]);
        $roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

        // SYSADMIN không xem hồ sơ nghiệp vụ (Rule #6, #12)
        if (in_array('SYSADMIN', $roles, true) && !in_array('LD_PHONG', $roles, true) && !in_array('LD_CO_QUAN', $roles, true)) {
            return $this->error('Quản trị viên (SYSADMIN) không được can thiệp duyệt hồ sơ nghiệp vụ', 403);
        }

        // Lấy danh sách hồ sơ ở trạng thái chờ ký duyệt / chờ phê duyệt
        $stmt = $pdo->prepare("
            SELECT 
                hs.id,
                hs.ma_ho_so,
                hs.nguoi_nop_ho_ten,
                tthc.ten_tthc,
                dv.ten_don_vi AS co_quan,
                hs.trang_thai,
                hs.sla_mau,
                hs.ngay_tiep_nhan,
                hs.ngay_hen_tra
            FROM ho_so hs
            JOIN thu_tuc_hanh_chinh tthc ON hs.tthc_id = tthc.id
            JOIN don_vi dv ON hs.don_vi_tiep_nhan_id = dv.id
            WHERE hs.trang_thai IN ('CHO_PHE_DUYET', 'CHO_KY_DUYET', 'DANG_XU_LY')
            ORDER BY hs.id DESC
            LIMIT 30
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success([
            'tong_so' => count($items),
            'items'   => $items,
        ], 'Lấy danh sách hồ sơ chờ ký thành công');
    }

    /**
     * Ký số phê duyệt hồ sơ từ Mobile App
     */
    public function kySo(Request $request): Response
    {
        $userId = $request->getAttribute('api_user_id');
        if (!$userId) {
            return $this->error('Token không liên kết với tài khoản cán bộ lãnh đạo', 403);
        }

        $input = $request->all();
        $hoSoId = (int)($input['ho_so_id'] ?? 0);
        $pin = trim((string)($input['ma_pin_smartca'] ?? '123456'));
        $yKien = trim((string)($input['y_kien'] ?? 'Đồng ý phê duyệt và ký số qua Mobile App'));
        $loaiKy = trim((string)($input['loai_chu_ky'] ?? 'DUYET_BUOC_1'));

        if ($hoSoId <= 0) {
            return $this->error('ID hồ sơ không hợp lệ', 400);
        }

        $pdo = Database::pdo();

        // 1. Kiểm tra hồ sơ
        $stmt = $pdo->prepare("SELECT * FROM ho_so WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $hoSoId]);
        $hoSo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hoSo) {
            return $this->error('Không tìm thấy hồ sơ', 404);
        }

        // 2. Lấy vai trò lãnh đạo
        $stmtRole = $pdo->prepare("
            SELECT vt.ma_vai_tro FROM vai_tro vt
            JOIN nguoi_dung_vai_tro ndvt ON vt.id = ndvt.vai_tro_id
            WHERE ndvt.nguoi_dung_id = :uid
        ");
        $stmtRole->execute(['uid' => $userId]);
        $roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('SYSADMIN', $roles, true)) {
            return $this->error('Quản trị viên (SYSADMIN) bị cấm tuyệt đối ký số hồ sơ nghiệp vụ', 403);
        }

        // Tự động chọn loại chữ ký phù hợp với vai trò
        if (in_array('LD_CO_QUAN', $roles, true)) {
            $loaiKy = 'PHAT_HANH';
        } elseif (in_array('LD_PHONG', $roles, true)) {
            $loaiKy = 'DUYET_BUOC_1';
        }

        // 3. Chuẩn bị tệp kết quả để ký số
        $dir = ChuKySoService::initKeysDir();
        $dummyDoc = $dir . DIRECTORY_SEPARATOR . "doc_hoso_{$hoSoId}.pdf";
        if (!file_exists($dummyDoc)) {
            file_put_contents($dummyDoc, "%PDF-1.4 [Ho so HP-eGov ID: {$hoSoId} - {$hoSo['ma_ho_so']}]");
        }

        $pdo->beginTransaction();
        try {
            // Thực hiện ký số bằng OpenSSL qua ChuKySoService
            $signResult = ChuKySoService::signDocument(
                $userId,
                $dummyDoc,
                $loaiKy,
                null,
                ['pin' => $pin, 'ho_so_id' => $hoSoId, 'ghi_chu' => $yKien]
            );

            // Cập nhật trạng thái hồ sơ
            $trangThaiMoi = ($loaiKy === 'PHAT_HANH') ? 'HOAN_THANH' : 'CHO_DONG_DAU';
            $updateHs = $pdo->prepare("
                UPDATE ho_so 
                SET trang_thai = :st, updated_at = NOW() 
                WHERE id = :id
            ");
            $updateHs->execute(['st' => $trangThaiMoi, 'id' => $hoSoId]);

            // Ghi vết luân chuyển
            $pdo->prepare("
                INSERT INTO ho_so_luan_chuyen (
                    ho_so_id, tu_don_vi_id, den_don_vi_id, nguoi_chuyen_id,
                    hanh_dong, trang_thai_truoc, trang_thai_sau, noi_dung_y_kien, thoi_gian_chuyen
                ) VALUES (
                    :hs_id, :tu_dv, :den_dv, :uid,
                    :action, :tt_truoc, :tt_sau, :yk, NOW()
                )
            ")->execute([
                'hs_id'    => $hoSoId,
                'tu_dv'    => $hoSo['don_vi_tiep_nhan_id'],
                'den_dv'   => $hoSo['don_vi_tiep_nhan_id'],
                'uid'      => $userId,
                'action'   => 'KY_SO_MOBILE_' . $loaiKy,
                'tt_truoc' => $hoSo['trang_thai'],
                'tt_sau'   => $trangThaiMoi,
                'yk'       => $yKien,
            ]);

            $pdo->commit();

            return $this->success([
                'ho_so_id'       => $hoSoId,
                'ma_ho_so'       => $hoSo['ma_ho_so'],
                'loai_chu_ky'    => $loaiKy,
                'trang_thai_moi' => $trangThaiMoi,
                'serial'         => $signResult['serial'] ?? 'SMARTCA-2026',
                'sha256'         => $signResult['file_sha256'] ?? '',
                'thoi_gian_ky'   => date('Y-m-d H:i:s'),
            ], 'Ký số điện tử hồ sơ qua Mobile App thành công');
        } catch (Throwable $e) {
            $pdo->rollBack();
            return $this->error('Lỗi thực hiện ký số: ' . $e->getMessage(), 400);
        }
    }
}
