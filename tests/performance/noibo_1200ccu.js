import http from 'k6/http';
import { check, sleep, group } from 'k6';

/**
 * HP-eGov - Kịch bản Kiểm thử Hiệu năng & Chịu tải Hệ thống Tác nghiệp Nội bộ Cán bộ (k6)
 * Căn cứ: Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov - Chương 3 §3.2
 * 
 * Quy mô: 1.200 Cán bộ công chức đồng thời (1.200 CCU)
 * Nghiệp vụ: Một cửa quét QR & số hóa NĐ 107, Chuyên viên thụ lý chuyển bước, Lãnh đạo ký số batch
 * Tiêu chuẩn nghiệm thu Báo cáo:
 * - Thời gian phản hồi trung bình (Avg Response Time) <= 1.0s (1000ms)
 * - Phân vị thứ 95 (P95 Response Time) <= 1.5s (1500ms)
 * - Tỷ lệ lỗi (Error Rate) <= 0.01% (0.0001)
 */

export const options = {
    scenarios: {
        internal_staff_load: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '2m', target: 400 },   // Ramping up lên 400 CCU
                { duration: '3m', target: 1200 },  // Đạt đỉnh 1.200 CCU cán bộ
                { duration: '10m', target: 1200 }, // Duy trì đỉnh 1.200 CCU trong 10 phút
                { duration: '2m', target: 300 },   // Giảm tải dần
                { duration: '1m', target: 0 },     // Kết thúc
            ],
            gracefulRampDown: '30s',
        },
    },
    thresholds: {
        http_req_duration: ['avg<=1000', 'p(95)<=1500'],
        http_req_failed: ['rate<=0.0001'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost/HP-eGov';
const LEADER_TOKEN = 'hpegov_leader_token_secret_sample_2026';

export default function () {
    const rand = Math.random();

    // 1. Phân bổ hành vi: 40% Cán bộ Một cửa tra cứu hộp việc & tiếp nhận
    if (rand < 0.40) {
        group('01_Mot_Cua_Hop_Viec_Tiep_Nhan', function () {
            // Xem danh sách tiếp nhận
            const resHopViec = http.get(`${BASE_URL}/admin/mot-cua/tiep-nhan`);
            check(resHopViec, {
                'Một cửa bàn làm việc status 200/302': (r) => r.status === 200 || r.status === 302,
                'Thời gian phản hồi Một cửa < 1000ms': (r) => r.timings.duration < 1000,
            });

            // Giả lập tra cứu quét QR CCCD chip
            const cccdQr = '031090001234|Nguyễn Văn An|15051990|Nam|Số 15 Cầu Đất, Hải Phòng';
            const resQr = http.get(`${BASE_URL}/admin/mot-cua/quet-qr?raw=${encodeURIComponent(cccdQr)}`);
            check(resQr, {
                'Phản hồi quét QR < 800ms': (r) => r.timings.duration < 800,
            });
        });
    }
    // 2. Phân bổ hành vi: 35% Chuyên viên thụ lý & thẩm định hồ sơ
    else if (rand < 0.75) {
        group('02_Chuyen_Vien_Thu_Ly_Tham_Dinh', function () {
            // Hộp việc chuyên viên
            const resThuLy = http.get(`${BASE_URL}/admin/thu-ly/danh-sach`);
            check(resThuLy, {
                'Thụ lý danh sách phản hồi thành công': (r) => r.status === 200 || r.status === 302,
            });

            // Chi tiết hồ sơ đang thẩm định
            const resChiTiet = http.get(`${BASE_URL}/admin/thu-ly/chi-tiet/3`);
            check(resChiTiet, {
                'Xem chi tiết thẩm định < 1000ms': (r) => r.timings.duration < 1000,
            });
        });
    }
    // 3. Phân bổ hành vi: 25% Lãnh đạo xem danh sách chờ ký & ký số hàng loạt (Batch signing)
    else {
        group('03_Lanh_Dao_Ky_So_Batch_SmartCA', function () {
            // Lấy danh sách chờ ký qua REST API Mobile App
            const resChoKy = http.get(`${BASE_URL}/api/v1/lanh-dao/cho-ky`, {
                headers: {
                    'Authorization': `Bearer ${LEADER_TOKEN}`,
                    'Accept': 'application/json',
                },
            });
            check(resChoKy, {
                'API Lãnh đạo chờ ký status 200': (r) => r.status === 200,
                'Thời gian tải danh sách chờ ký < 1000ms': (r) => r.timings.duration < 1000,
            });

            // Thực hiện ký số từ xa qua SmartCA
            const signPayload = JSON.stringify({
                ho_so_id: 3,
                tai_lieu_id: 1,
                ma_pin: '123456',
                y_kien: 'Đồng ý phê duyệt kết quả giải quyết TTHC',
            });

            const resKy = http.post(`${BASE_URL}/api/v1/lanh-dao/ky-so`, signPayload, {
                headers: {
                    'Authorization': `Bearer ${LEADER_TOKEN}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });
            check(resKy, {
                'Ký số SmartCA thành công (200/400)': (r) => r.status === 200 || r.status === 400,
                'Độ trễ ký số < 1500ms (P95)': (r) => r.timings.duration < 1500,
            });
        });
    }

    // Nghỉ ngẫu nhiên giữa các thao tác (Think time 2 - 4 giây)
    sleep(Math.random() * 2 + 2);
}
