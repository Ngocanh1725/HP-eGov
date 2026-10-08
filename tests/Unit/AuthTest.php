<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\AuthService;
use TestRunner;

class AuthTest
{
    public function setUp(): void
    {
        $db = Database::connection();
        $db->exec("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE ten_dang_nhap IN ('motcua.hongbang', 'test.lockout')");
    }

    public function testValidCanBoLogin(): void
    {
        // Kiểm tra đăng nhập với tài khoản mẫu motcua.hongbang / Hpegov@2026
        $res = AuthService::attemptCanBo('motcua.hongbang', 'Hpegov@2026');

        TestRunner::assertTrue($res['success']);
        TestRunner::assertNotNull($res['user']);
        TestRunner::assertEquals('motcua.hongbang', $res['user']['ten_dang_nhap']);
        TestRunner::assertTrue(AuthService::check(AuthService::GUARD_CAN_BO));
        TestRunner::assertTrue(AuthService::hasRole('CB_MOT_CUA'));

        AuthService::logout();
    }

    public function testFailedLoginCounterAndLockout(): void
    {
        $db = Database::connection();
        // Reset tài khoản test về trạng thái sạch
        $db->exec("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE ten_dang_nhap = 'test.lockout'");
        
        // Tạo tài khoản test nếu chưa có
        $chk = $db->query("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = 'test.lockout'")->fetchColumn();
        if (!$chk) {
            $mk = password_hash('Hpegov@2026', PASSWORD_BCRYPT);
            $db->exec("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, don_vi_chinh_id, trang_thai) 
                       VALUES ('test.lockout', '{$mk}', 'Cán bộ Test Khóa', 'test.lockout@haiphong.gov.vn', 1, 1)");
        }

        // 1. Thử sai 1 lần
        $res1 = AuthService::attemptCanBo('test.lockout', 'SaiMatKhau@1');
        TestRunner::assertFalse($res1['success']);
        TestRunner::assertFalse($res1['require_captcha']);

        // 2. Thử sai đến lần 3 -> phải kích hoạt require_captcha
        AuthService::attemptCanBo('test.lockout', 'SaiMatKhau@2');
        $res3 = AuthService::attemptCanBo('test.lockout', 'SaiMatKhau@3');
        TestRunner::assertFalse($res3['success']);
        TestRunner::assertTrue($res3['require_captcha']);

        // 3. Thử sai đến lần 5 -> tài khoản bị khóa 15 phút
        $cCode4 = \App\Services\CaptchaService::generateCode();
        AuthService::attemptCanBo('test.lockout', 'SaiMatKhau@4', $cCode4);

        $cCode5 = \App\Services\CaptchaService::generateCode();
        $res5 = AuthService::attemptCanBo('test.lockout', 'SaiMatKhau@5', $cCode5);
        TestRunner::assertFalse($res5['success']);
        TestRunner::assertContains('khóa', mb_strtolower($res5['message']));

        // Kiểm tra trong CSDL cờ khoa_den đã được thiết lập
        $row = $db->query("SELECT so_lan_sai, khoa_den FROM nguoi_dung WHERE ten_dang_nhap = 'test.lockout'")->fetch(\PDO::FETCH_ASSOC);
        TestRunner::assertEquals(5, (int)$row['so_lan_sai']);
        TestRunner::assertNotNull($row['khoa_den']);

        // Dọn dẹp tài khoản test
        $db->exec("UPDATE nguoi_dung SET so_lan_sai = 0, khoa_den = NULL WHERE ten_dang_nhap = 'test.lockout'");
    }

    public function testVneidCitizenLoginAndAutoCreate(): void
    {
        $profile = [
            'so_cccd' => '031099998888',
            'vneid_sub' => 'c06_test_sub_99998888',
            'ho_ten' => 'Vũ Thị Phương Thảo',
            'ngay_sinh' => '1998-12-25',
            'gioi_tinh' => 'NU',
            'que_quan' => 'Hải Phòng',
            'dia_chi_thuong_tru' => 'Số 99 Lê Hồng Phong, Ngô Quyền, Hải Phòng',
            'so_dien_thoai' => '0901234888',
            'email' => 'thaovu.hp@gmail.com',
        ];

        $res = AuthService::loginFromVNeID($profile);

        TestRunner::assertTrue($res['success']);
        TestRunner::assertTrue(AuthService::check(AuthService::GUARD_CONG_DAN));
        TestRunner::assertEquals('031099998888', AuthService::user()['so_cccd']);
        TestRunner::assertTrue(AuthService::can('portal.nop_ho_so'));

        // Kiểm tra đã được ghi vào CSDL bảng cong_dan
        $db = Database::connection();
        $citizen = $db->query("SELECT id, ho_ten FROM cong_dan WHERE so_cccd = '031099998888'")->fetch(\PDO::FETCH_ASSOC);
        TestRunner::assertNotNull($citizen);
        TestRunner::assertEquals('Vũ Thị Phương Thảo', $citizen['ho_ten']);

        AuthService::logout();
    }
}
