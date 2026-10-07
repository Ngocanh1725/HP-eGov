<?php $this->layout('layouts/portal', ['title' => 'Hướng dẫn & Trợ giúp']); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <h1 style="font-size: 1.55rem; color: #0b2e59; margin: 0 0 0.25rem 0;">
        ❓ HƯỚNG DẪN & HỎI ĐÁP THƯỜNG GẶP (FAQ)
    </h1>
    <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
        Tổng hợp các câu hỏi phổ biến và cẩm nang thực hiện Dịch vụ công trực tuyến TP. Hải Phòng
    </p>
</div>

<!-- KHUNG HOTLINE HỖ TRỢ -->
<div class="card p-3 mb-4" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-weight: 700; color: #1e40af; font-size: 1rem;">📞 TỔNG ĐÀI HỖ TRỢ DỊCH VỤ CÔNG HẢI PHÒNG</div>
            <div style="font-size: 0.85rem; color: #1e3a8a; margin-top: 2px;">
                Phục vụ 24/7 giải đáp thắc mắc về thủ tục, nộp hồ sơ trực tuyến và phản ánh kiến nghị.
            </div>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <span style="font-size: 1.5rem; font-weight: 800; color: #dc2626; font-family: monospace;">0225 1022</span>
            <span style="font-size: 0.85rem; color: #64748b;">(hoặc 1900 9095)</span>
        </div>
    </div>
</div>

<!-- DANH SÁCH CÂU HỎI FAQ -->
<div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <h2 style="font-size: 1.15rem; color: #0b2e59; margin: 0 0 1.25rem 0; font-weight: 700; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
        CÁC CÂU HỎI THƯỜNG GẶP
    </h2>

    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 1rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem 0;">
                1. Tôi cần chuẩn bị gì để nộp hồ sơ DVC trực tuyến trên Cổng HP-eGov?
            </h3>
            <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5;">
                Quý khách chỉ cần có tài khoản Định danh điện tử VNeID (Mức độ 2) của Bộ Công an hoặc tài khoản Doanh nghiệp (Mã số thuế). Khi đăng nhập, toàn bộ thông tin nhân thân sẽ được tự động điền sẵn vào tờ khai E-Form mà không cần kê khai lại.
            </p>
        </div>

        <div style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 1rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem 0;">
                2. Kho dữ liệu điện tử cá nhân là gì và có lợi ích như thế nào?
            </h3>
            <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5;">
                Kho dữ liệu điện tử là nơi lưu trữ toàn bộ các giấy tờ đã được số hóa và các kết quả TTHC đã được cấp trước đó. Khi nộp hồ sơ mới, Quý khách chỉ cần chọn giấy tờ từ Kho dữ liệu mà không cần tải lên hay nộp lại bản giấy (tuân thủ Nghị định 107/2021/NĐ-CP).
            </p>
        </div>

        <div style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 1rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem 0;">
                3. Làm sao để tra cứu tiến độ xử lý hồ sơ?
            </h3>
            <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5;">
                Quý khách có thể truy cập mục "Tra cứu" và nhập Mã định danh hồ sơ QCVN 102 (được gửi qua SMS/Zalo hoặc in trên Giấy hẹn), hoặc quét mã QR bằng camera điện thoại/máy tính để xem tiến độ trực tiếp mà không cần đăng nhập.
            </p>
        </div>

        <div style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 1rem;">
            <h3 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 0.35rem 0;">
                4. Tôi có thể nhận kết quả giải quyết TTHC bằng những hình thức nào?
            </h3>
            <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5;">
                Cổng DVC cung cấp 3 hình thức: (1) Nhận bản điện tử có ký số vào Kho dữ liệu cá nhân (nhanh nhất); (2) Nhận bản giấy trực tiếp tại Bộ phận Một cửa; (3) Nhận qua Bưu chính công ích VNPost chuyển phát tận nơi.
            </p>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
