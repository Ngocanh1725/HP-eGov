<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\DataScopeService;
use TestRunner;

class DataScopeTest
{
    public function testSysadminBlockedFromBusinessPermissions(): void
    {
        // 1. Đăng nhập tài khoản sysadmin
        $res = AuthService::attemptCanBo('sysadmin', 'Hpegov@2026');
        TestRunner::assertTrue($res['success']);
        TestRunner::assertTrue(AuthService::hasRole('SYSADMIN'));

        // 2. Kiểm tra quyền của SYSADMIN tuyệt đối không chứa hoso.* hoặc kyso.*
        $perms = AuthService::permissions();
        foreach ($perms as $p) {
            TestRunner::assertFalse(str_starts_with($p, 'hoso.'), "SYSADMIN không được có quyền {$p}");
            TestRunner::assertFalse(str_starts_with($p, 'kyso.'), "SYSADMIN không được có quyền {$p}");
        }

        // 3. Kiểm tra DataScope của SYSADMIN là NONE
        $scope = DataScopeService::getScope();
        TestRunner::assertEquals('NONE', $scope['type']);

        AuthService::logout();
    }

    public function testCitizenIdorProtection(): void
    {
        // Đăng nhập công dân A
        AuthService::loginFromVNeID([
            'so_cccd' => '031090001234',
            'ho_ten' => 'Nguyễn Văn An',
            'vneid_sub' => 'sub_an_123',
        ]);

        $citizenId = AuthService::id();
        $scope = DataScopeService::getScope();

        // Scope của công dân A phải gắn chặt với ID của A
        TestRunner::assertEquals('CONG_DAN', $scope['type']);
        TestRunner::assertEquals($citizenId, $scope['params'][':scope_cong_dan_id']);

        AuthService::logout();
    }
}
