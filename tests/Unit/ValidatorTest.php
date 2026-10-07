<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use TestRunner;

class ValidatorTest
{
    public function testRequiredAndEmailValidation(): void
    {
        $v = Validator::make([
            'email' => 'invalid-email',
            'ho_ten' => '',
        ], [
            'ho_ten' => 'required',
            'email' => 'required|email',
        ]);

        TestRunner::assertTrue($v->fails());
        TestRunner::assertContains('bắt buộc', $v->first('ho_ten'));
        TestRunner::assertContains('email hợp lệ', $v->first('email'));
    }

    public function testCccdValidation(): void
    {
        // CCCD đúng 12 chữ số
        $vValid = Validator::make(['so_cccd' => '031090001234'], ['so_cccd' => 'required|cccd']);
        TestRunner::assertTrue($vValid->passes());

        // CCCD sai (11 chữ số hoặc chứa chữ)
        $vInvalid = Validator::make(['so_cccd' => '03109000123'], ['so_cccd' => 'required|cccd']);
        TestRunner::assertTrue($vInvalid->fails());
        TestRunner::assertContains('12 chữ số', $vInvalid->first('so_cccd'));
    }

    public function testMstValidation(): void
    {
        // MST đúng 10 số hoặc 13 số
        $v10 = Validator::make(['mst' => '0101234567'], ['mst' => 'required|mst']);
        TestRunner::assertTrue($v10->passes());

        $v13 = Validator::make(['mst' => '0101234567-001'], ['mst' => 'required|mst']);
        TestRunner::assertTrue($v13->passes());

        // MST sai
        $vFail = Validator::make(['mst' => '12345'], ['mst' => 'required|mst']);
        TestRunner::assertTrue($vFail->fails());
    }

    public function testPhoneValidation(): void
    {
        // Số điện thoại Việt Nam 10 số đầu 09/08/03/07/05
        $v = Validator::make(['sdt' => '0912345678'], ['sdt' => 'required|phone']);
        TestRunner::assertTrue($v->passes());

        $vBad = Validator::make(['sdt' => '0112345678'], ['sdt' => 'required|phone']);
        TestRunner::assertTrue($vBad->fails());
    }

    public function testInAndDateValidation(): void
    {
        $v = Validator::make([
            'kenh' => 'TRUC_TUYEN',
            'ngay' => '2026-10-15',
        ], [
            'kenh' => 'required|in:TRUC_TUYEN,MOT_CUA',
            'ngay' => 'required|date:Y-m-d',
        ]);
        TestRunner::assertTrue($v->passes());

        $vBad = Validator::make([
            'kenh' => 'KHAC',
            'ngay' => '15/10/2026',
        ], [
            'kenh' => 'in:TRUC_TUYEN,MOT_CUA',
            'ngay' => 'date:Y-m-d',
        ]);
        TestRunner::assertTrue($vBad->fails());
    }
}
