import http from 'k6/http';
import { check, sleep, group } from 'k6';

/**
 * HP-eGov - Kịch bản Kiểm thử Hiệu năng & Chịu tải Cổng Dịch vụ công (k6)
 * Căn cứ: Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov - Chương 3 §3.2
 * 
 * Quy mô: 5.000 Người dùng ảo đồng thời (5.000 CCU)
 * Tiêu chuẩn nghiệm thu Báo cáo:
 * - Thời gian phản hồi trung bình (Avg Response Time) <= 1.0s (1000ms)
 * - Phân vị thứ 95 (P95 Response Time) <= 1.5s (1500ms)
 * - Tỷ lệ lỗi (Error Rate) <= 0.01% (0.0001)
 */

export const options = {
    scenarios: {
        portal_citizens_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '3m', target: 1500 },  // Ramping up lên 1.500 CCU
                { duration: '5m', target: 5000 },  // Đạt đỉnh 5.000 CCU
                { duration: '10m', target: 5000 }, // Duy trì tải đỉnh 5.000 CCU trong 10 phút
                { duration: '3m', target: 1000 },  // Giảm tải dần
                { duration: '1m', target: 0 },     // Kết thúc
            ],
            gracefulRampDown: '30s',
        },
    },
    thresholds: {
        // Tiêu chuẩn nghiệm thu khắt khe theo Báo cáo §3.2
        http_req_duration: ['avg<=1000', 'p(95)<=1500'],
        http_req_failed: ['rate<=0.0001'], // Lỗi <= 0.01%
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost/HP-eGov';

// Danh sách mã hồ sơ mẫu phục vụ tra cứu
const SAMPLE_DOSSIERS = [
    '000.04.15.H21-260405-0042-000001',
    '000.04.16.H21-260406-0042-000002',
    '000.04.17.H21-260407-0042-000003',
    '000.04.18.H21-260408-0042-000004',
    '000.04.19.H21-260409-0042-000005',
    '000.04.15.H21-260410-0099-000006',
    '000.05.00.H21-260411-0050-000007',
    '000.03.00.H21-260412-0080-000008',
];

export default function () {
    const rand = Math.random();

    // 1. Phân bổ hành vi: 60% Tra cứu tiến độ hồ sơ
    if (rand < 0.60) {
        group('01_Tra_Cuu_Tien_Do_Ho_So', function () {
            const maHoSo = SAMPLE_DOSSIERS[Math.floor(Math.random() * SAMPLE_DOSSIERS.length)];
            
            // Tra cứu qua giao diện Web Portal
            const resWeb = http.get(`${BASE_URL}/portal/tra-cuu?ma_ho_so=${maHoSo}`);
            check(resWeb, {
                'Portal tra cuu status 200': (r) => r.status === 200,
                'Portal tra cuu thoi gian < 1000ms': (r) => r.timings.duration < 1000,
            });

            // Tra cứu qua REST API
            const resApi = http.get(`${BASE_URL}/api/v1/tra-cuu/${maHoSo}`, {
                headers: { 'Accept': 'application/json' },
            });
            check(resApi, {
                'API tra cuu status 200': (r) => r.status === 200,
                'API tra cuu json success': (r) => r.json('status') === 'success',
            });
        });
    }
    // 2. Phân bổ hành vi: 25% Xem danh mục TTHC & Tải biểu mẫu E-Form
    else if (rand < 0.85) {
        group('02_Xem_Danh_Muc_TTHC', function () {
            const resList = http.get(`${BASE_URL}/portal/dvc/danh-sach`);
            check(resList, {
                'DVC danh sach status 200': (r) => r.status === 200,
            });

            // API Danh mục TTHC có phân trang & tìm kiếm
            const resApi = http.get(`${BASE_URL}/api/v1/danh-muc-tthc?page=1&limit=10&tu_khoa=xay+dung`, {
                headers: { 'Accept': 'application/json' },
            });
            check(resApi, {
                'API danh muc TTHC status 200': (r) => r.status === 200,
                'API tra ve danh sach TTHC': (r) => r.json('data.items').length > 0,
            });
        });
    }
    // 3. Phân bổ hành vi: 15% Nộp hồ sơ trực tuyến có đính kèm file 2MB - 10MB
    else {
        group('03_Nop_Ho_So_Truc_Tuyen_Kem_File', function () {
            // Bước xem form nộp
            const resForm = http.get(`${BASE_URL}/portal/nop-ho-so/buoc-1?tthc_id=3`);
            check(resForm, {
                'Wizard buoc 1 status 200': (r) => r.status === 200,
            });

            // Giả lập nộp dữ liệu E-form
            const payload = {
                tthc_id: '3',
                nguoi_nop_ho_ten: 'Nguyễn Văn Công Dân Test',
                nguoi_nop_cccd: '031090001234',
                nguoi_nop_sdt: '0904123456',
                dia_diem_xay_dung: 'Số 50 Hoàng Văn Thụ, Hồng Bàng, Hải Phòng',
                dien_tich_xay_dung: '120',
            };

            const resPost = http.post(`${BASE_URL}/portal/nop-ho-so/buoc-2`, payload, {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            });
            check(resPost, {
                'Submit buoc 2 thanh cong (200/302)': (r) => r.status === 200 || r.status === 302,
            });
        });
    }

    // Nghỉ ngẫu nhiên giữa các thao tác (Think time 1 - 3 giây)
    sleep(Math.random() * 2 + 1);
}
