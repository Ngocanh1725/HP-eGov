<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Model;
use LogicException;
use TestRunner;

// Model mẫu dùng cho kiểm thử
class SampleHoSoModel extends Model
{
    protected string $table = 'ho_so';
    protected array $fillable = ['ma_ho_so', 'chu_ho_so', 'trang_thai'];

    public function testFilterPublic(array $data): array
    {
        return $this->filterFillable($data);
    }
}

class ModelTest
{
    public function testPhysicalDeleteIsStrictlyForbidden(): void
    {
        $model = new SampleHoSoModel();

        // Kiểm tra lệnh delete() phải ném LogicException theo quy định bảo mật bất biến
        TestRunner::assertThrows(LogicException::class, function () use ($model) {
            $model->delete(1);
        });
    }

    public function testFillableWhitelistFiltering(): void
    {
        $model = new SampleHoSoModel();
        $input = [
            'ma_ho_so' => '000-260115-0042-000001',
            'chu_ho_so' => 'Nguyễn Văn A',
            'trang_thai' => 'DANG_XU_LY',
            'malicious_column' => 'DROP TABLE dm_dvhc;', // Cột không được phép
        ];

        $filtered = $model->testFilterPublic($input);

        TestRunner::assertTrue(isset($filtered['ma_ho_so']));
        TestRunner::assertTrue(isset($filtered['chu_ho_so']));
        TestRunner::assertTrue(isset($filtered['trang_thai']));
        TestRunner::assertFalse(isset($filtered['malicious_column']));
    }
}
