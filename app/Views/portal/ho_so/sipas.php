<?php $this->layout('layouts/portal', ['title' => "Khảo sát SIPAS: {$hoSo['ma_ho_so']}"]); ?>

<?php $this->startSection('content'); ?>
<div style="max-width: 800px; margin: 0 auto;">
    <div class="mb-4">
        <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'])) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Chi tiết hồ sơ</a>
        <h1 style="font-size: 1.5rem; color: #0b2e59; margin: 0.25rem 0 0 0;">
            ⭐ KHẢO SÁT MỨC ĐỘ HÀI LÒNG CỦA NGƯỜI DÂN (SIPAS)
        </h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Theo Quyết định số 876/QĐ-BNV của Bộ Nội vụ · Mỗi hồ sơ được thực hiện đánh giá duy nhất 1 lần
        </p>
    </div>

    <div class="card p-3 mb-4" style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
        <div style="font-size: 0.85rem;">
            - Mã hồ sơ: <strong style="font-family: monospace; color: #0b2e59;"><?= e($hoSo['ma_ho_so']) ?></strong><br>
            - Thủ tục: <strong><?= e($hoSo['ten_tthc']) ?></strong><br>
            - Cơ quan giải quyết: <strong><?= e($hoSo['ten_don_vi']) ?></strong>
        </div>
    </div>

    <!-- FORM ĐÁNH GIÁ 5 TIÊU CHÍ -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <form action="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'] . '/danh-gia-sipas')) ?>" method="POST">
            <?= csrf_field() ?>

            <?php
            $criteria = [
                'diem_tiep_can' => [
                    'title' => '1. Tiếp cận thông tin dịch vụ',
                    'desc' => 'Thông tin về thủ tục rõ ràng, dễ tìm kiếm trên Cổng DVC, kênh hướng dẫn dễ hiểu.'
                ],
                'diem_thu_tuc' => [
                    'title' => '2. Thủ tục hành chính & Biểu mẫu điện tử',
                    'desc' => 'Biểu mẫu E-Form tinh gọn, tự động điền VNeID thuận tiện, không đòi hỏi giấy tờ rườm rà.'
                ],
                'diem_cong_chuc' => [
                    'title' => '3. Tinh thần, thái độ phục vụ của cán bộ, công chức',
                    'desc' => 'Cán bộ hướng dẫn tận tình, lịch sự, chuyên nghiệp, không gây phiền hà nhũng nhiễu.'
                ],
                'diem_ket_qua' => [
                    'title' => '4. Tiến độ và kết quả giải quyết',
                    'desc' => 'Hồ sơ được giải quyết đúng hẹn hoặc trước hạn, kết quả điện tử chính xác, đầy đủ giá trị pháp lý.'
                ],
                'diem_phan_anh' => [
                    'title' => '5. Tiếp nhận và xử lý phản ánh kiến nghị',
                    'desc' => 'Các thắc mắc, yêu cầu trợ giúp qua Tổng đài 1022 hoặc hệ thống được phản hồi kịp thời.'
                ],
            ];
            ?>

            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach ($criteria as $field => $c): ?>
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;"><?= e($c['title']) ?></div>
                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px; margin-bottom: 0.75rem;"><?= e($c['desc']) ?></div>

                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer; font-size: 0.85rem; padding: 0.35rem 0.65rem; border: 1px solid #e2e8f0; border-radius: 6px; background: #fafafa;">
                                    <input type="radio" name="<?= e($field) ?>" value="<?= $star ?>" <?= ($star === 5) ? 'checked' : '' ?>>
                                    <span><?= str_repeat('⭐', $star) ?> (<?= $star ?> sao)</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" style="font-weight: 600;">Ý kiến góp ý thêm (nếu có):</label>
                <textarea name="y_kien_dong_gop" class="form-control" rows="3" placeholder="Chia sẻ cảm nhận hoặc đề xuất của Quý khách để chính quyền thành phố Hải Phòng nâng cao chất lượng phục vụ..."></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'])) ?>" class="btn btn-secondary">Quay lại</a>
                <button type="submit" class="btn btn-gold" style="font-weight: 700; padding: 0.65rem 2rem;">
                    ⭐ Gửi đánh giá SIPAS
                </button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection(); ?>
