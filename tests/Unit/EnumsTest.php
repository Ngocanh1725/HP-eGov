<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\KenhNop;
use App\Enums\KenhThongBao;
use App\Enums\LoaiBuoc;
use App\Enums\LoaiTaiLieu;
use App\Enums\MauSla;
use App\Enums\TrangThaiHoSo;
use App\Enums\VaiTro;
use TestRunner;

class EnumsTest
{
    public function testTrangThaiHoSoValuesAndMapping(): void
    {
        TestRunner::assertEquals('Mới tiếp nhận', TrangThaiHoSo::MOI_TIEP_NHAN->label());
        TestRunner::assertEquals('TIEP_NHAN', TrangThaiHoSo::MOI_TIEP_NHAN->nhomChuan());
        TestRunner::assertEquals('DA_GIAI_QUYET', TrangThaiHoSo::HOAN_THANH->nhomChuan());
        TestRunner::assertEquals('TRA_LAI', TrangThaiHoSo::TU_CHOI->nhomChuan());
        TestRunner::assertEquals('RUT_HO_SO', TrangThaiHoSo::DA_HUY->nhomChuan());
        TestRunner::assertContains('style="background-color:', TrangThaiHoSo::MOI_TIEP_NHAN->badge());
    }

    public function testVaiTroSevenRoles(): void
    {
        TestRunner::assertEquals('Công dân / Doanh nghiệp', VaiTro::CONG_DAN->label());
        TestRunner::assertEquals('Cán bộ Tiếp nhận Một cửa', VaiTro::CB_MOT_CUA->label());
        TestRunner::assertEquals('Chuyên viên Thụ lý Thẩm định', VaiTro::CHUYEN_VIEN->label());
        TestRunner::assertEquals('Lãnh đạo Phòng chuyên môn', VaiTro::LD_PHONG->label());
        TestRunner::assertEquals('Lãnh đạo Cơ quan / Đơn vị', VaiTro::LD_CO_QUAN->label());
        TestRunner::assertEquals('Văn thư số', VaiTro::VAN_THU->label());
        TestRunner::assertEquals('Quản trị viên Hệ thống', VaiTro::SYSADMIN->label());
    }

    public function testMauSlaThreeColors(): void
    {
        TestRunner::assertEquals('#16A34A', MauSla::XANH->color());
        TestRunner::assertEquals('#F59E0B', MauSla::VANG->color());
        TestRunner::assertEquals('#DC2626', MauSla::DO->color());
    }

    public function testOtherEnumsExist(): void
    {
        TestRunner::assertNotNull(KenhNop::TRUC_TUYEN->label());
        TestRunner::assertNotNull(LoaiBuoc::TIEP_NHAN->label());
        TestRunner::assertNotNull(LoaiTaiLieu::SO_HOA->label());
        TestRunner::assertNotNull(KenhThongBao::SMS->label());
    }
}
