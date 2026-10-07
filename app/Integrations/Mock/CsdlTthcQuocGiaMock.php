<?php

declare(strict_types=1);

namespace App\Integrations\Mock;

use App\Integrations\Contracts\CsdlTthcQuocGiaInterface;

/**
 * CsdlTthcQuocGiaMock – Mô phỏng đồng bộ CSDL Quốc gia về TTHC
 * Cung cấp dữ liệu thử nghiệm chuẩn hóa bám sát thực tiễn TP. Hải Phòng
 */
class CsdlTthcQuocGiaMock implements CsdlTthcQuocGiaInterface
{
    public function sync(): array
    {
        // 1. TTHC công bố mới từ Cổng DVC Quốc gia
        $added = [
            [
                'ma_tthc' => '2.002899',
                'ma_rut_gon' => '2899',
                'ten_thu_tuc' => 'Cấp Giấy phép lưu hành xe quá khổ, quá tải trên đường bộ phục vụ Cảng biển Hải Phòng',
                'cap_thuc_hien' => 'TINH',
                'muc_do' => 'TOAN_TRINH',
                'thoi_han_ngay' => 2,
                'phi_le_phi' => 0,
                'mo_ta' => 'Áp dụng cho các phương tiện vận tải logistics siêu trường siêu trọng tại các KCN Đình Vũ, Cát Hải, Nam Cầu Kiền.',
                'can_cu_phap_ly' => 'Thông tư 35/2023/TT-BGTVT; Quyết định số 18/2026/QĐ-UBND Hải Phòng',
                'thay_doi' => 'Công bố mới theo quy chuẩn giao thông cảng biển 2026',
            ],
            [
                'ma_tthc' => '2.002905',
                'ma_rut_gon' => '2905',
                'ten_thu_tuc' => 'Đăng ký biến động quyền sử dụng đất trong Khu kinh tế Đình Vũ – Cát Hải',
                'cap_thuc_hien' => 'TINH',
                'muc_do' => 'TOAN_TRINH',
                'thoi_han_ngay' => 10,
                'phi_le_phi' => 100000,
                'mo_ta' => 'Thủ tục chuyển nhượng quyền sử dụng đất đối với doanh nghiệp logistics thứ cấp trong KKT.',
                'can_cu_phap_ly' => 'Luật Đất đai 2024; Nghị định 102/2024/NĐ-CP',
                'thay_doi' => 'Công bố mới thay thế quy trình cũ',
            ],
        ];

        // 2. TTHC sửa đổi bổ sung (thay đổi thời hạn / mức độ)
        $updated = [
            [
                'ma_tthc' => '1.002888',
                'ma_rut_gon' => '2888',
                'ten_thu_tuc' => 'Cấp đổi Giấy phép lái xe do ngành Giao thông vận tải cấp',
                'cap_thuc_hien' => 'TINH',
                'muc_do_cu' => 'MOT_PHAN',
                'muc_do_moi' => 'TOAN_TRINH',
                'thoi_han_cu' => 5,
                'thoi_han_moi' => 3,
                'phi_cu' => 135000,
                'phi_moi' => 115000,
                'thay_doi' => 'Nâng cấp lên Toàn trình 100%; Rút ngắn từ 5 ngày còn 3 ngày làm việc; Giảm 15% lệ phí trực tuyến theo chính sách kích cầu số của HĐND TP Hải Phòng.',
            ],
            [
                'ma_tthc' => '1.004123',
                'ma_rut_gon' => '4123',
                'ten_thu_tuc' => 'Cấp Giấy phép xây dựng nhà ở riêng lẻ đô thị',
                'cap_thuc_hien' => 'HUYEN',
                'muc_do_cu' => 'MOT_PHAN',
                'muc_do_moi' => 'TOAN_TRINH',
                'thoi_han_cu' => 15,
                'thoi_han_moi' => 12,
                'phi_cu' => 75000,
                'phi_moi' => 75000,
                'thay_doi' => 'Tích hợp số hóa 100% hồ sơ bản vẽ CAD/PDF theo NĐ 107/2021/NĐ-CP; rút ngắn thời hạn còn 12 ngày.',
            ],
        ];

        // 3. TTHC bãi bỏ
        $deprecated = [
            [
                'ma_tthc' => '0.001999',
                'ma_rut_gon' => '1999',
                'ten_thu_tuc' => 'Xác nhận tình trạng nhà ở trên văn bản giấy (mẫu cũ)',
                'ly_do' => 'Bãi bỏ theo chỉ đạo đơn giản hóa của Đề án 06, thay thế bằng tra cứu trực tuyến CSDL Dân cư & CSDL Đất đai.',
            ],
        ];

        return [
            'added' => $added,
            'updated' => $updated,
            'deprecated' => $deprecated,
            'sync_time' => date('Y-m-d H:i:s'),
            'total_changes' => count($added) + count($updated) + count($deprecated),
        ];
    }

    public function getDetail(string $maTthc): ?array
    {
        $sync = $this->sync();
        foreach (array_merge($sync['added'], $sync['updated']) as $item) {
            if ($item['ma_tthc'] === $maTthc) {
                return $item;
            }
        }
        return null;
    }
}
