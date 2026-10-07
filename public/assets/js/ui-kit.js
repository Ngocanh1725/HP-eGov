/**
 * ============================================================================
 * HP-eGov UI KIT DEMO SCRIPT: ui-kit.js
 * Khởi tạo tương tác cho trang UI Kit không dùng inline script (Tuân thủ CSP)
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // 1. Kích hoạt Modal mẫu
    const btnTestModal = document.getElementById('btnTestModal');
    if (btnTestModal) {
        btnTestModal.addEventListener('click', function () {
            window.App.modal.open('demoSampleModal');
        });
    }

    // 2. Kích hoạt Confirm Dialog
    const btnTestConfirm = document.getElementById('btnTestConfirm');
    if (btnTestConfirm) {
        btnTestConfirm.addEventListener('click', function () {
            window.App.confirm(
                'Xác nhận hủy logic hồ sơ',
                'Bạn có chắc chắn muốn hủy logic hồ sơ này? Hành động này sẽ được ghi vào nhật ký kiểm toán WORM và không thể hoàn tác vật lý.',
                function () {
                    window.App.toast.success('Đã hủy hồ sơ', 'Hồ sơ đã được đánh dấu hủy logic thành công.');
                }
            );
        });
    }

    // 3. Kích hoạt Toast Notifications
    const btnSuccess = document.getElementById('btnToastSuccess');
    if (btnSuccess) {
        btnSuccess.addEventListener('click', function () {
            window.App.toast.success('Tiếp nhận thành công', 'Hồ sơ 000.04.15.H21-260115-0042 đã được tiếp nhận vào hệ thống.');
        });
    }

    const btnError = document.getElementById('btnToastError');
    if (btnError) {
        btnError.addEventListener('click', function () {
            window.App.toast.error('Lỗi xử lý', 'Không thể ký số: Chứng thư số cán bộ đã hết hạn hiệu lực.');
        });
    }

    const btnWarning = document.getElementById('btnToastWarning');
    if (btnWarning) {
        btnWarning.addEventListener('click', function () {
            window.App.toast.warning('Cảnh báo SLA Vàng', 'Hồ sơ còn dưới 24 giờ làm việc trước khi quá hạn!');
        });
    }

    const btnInfo = document.getElementById('btnToastInfo');
    if (btnInfo) {
        btnInfo.addEventListener('click', function () {
            window.App.toast.info('Thông báo hệ thống', 'Có 3 hồ sơ trực tuyến mới được chuyển từ Cổng DVC Quốc gia.');
        });
    }

    // 4. Khởi tạo biểu đồ Chart.js (SLA Dashboard Hải Phòng)
    const chartCanvas = document.getElementById('slaChartCanvas');
    if (chartCanvas && typeof Chart !== 'undefined') {
        new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Đúng hạn (Xanh)', 'Sắp đến hạn (Vàng)', 'Quá hạn (Đỏ)'],
                datasets: [{
                    data: [198, 14, 3],
                    backgroundColor: ['#16A34A', '#F59E0B', '#DC2626'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Be Vietnam Pro', size: 12 },
                            boxWidth: 14,
                        }
                    }
                }
            }
        });
    }

    // 5. Khởi tạo mã QR Code (qrcode-generator)
    const qrContainer = document.getElementById('qrCodeContainer');
    if (qrContainer && typeof qrcode !== 'undefined') {
        try {
            const qr = qrcode(4, 'M');
            qr.addData('https://dichvucong.haiphong.gov.vn/tra-cuu?ma=000.04.15.H21-260115-0042');
            qr.make();
            qrContainer.innerHTML = qr.createImgTag(4, 8);
        } catch (e) {
            console.error('Lỗi tạo mã QR:', e);
        }
    }
});
