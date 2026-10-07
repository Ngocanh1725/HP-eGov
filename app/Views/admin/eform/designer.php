<?php $this->layout('layouts/admin', ['title' => 'Trình thiết kế Biểu mẫu E-Form']); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">TRÌNH THIẾT KẾ BIỂU MẪU ĐIỆN TỬ (E-FORM DESIGNER)</h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Định nghĩa trường động, hỗ trợ dữ liệu hành chính ĐVHC 3 cấp & Liên thông Tự động điền dữ liệu từ VNeID (C06)
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-outline-info" onclick="previewEformModal()">
            👁️ Xem trước giao diện (Live Preview)
        </button>
        <button type="button" class="btn btn-primary" onclick="submitEformForm()">
            💾 Lưu phiên bản E-Form mới
        </button>
    </div>
</div>

<!-- Lựa chọn TTHC cần thiết kế -->
<div class="card mb-4 p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
    <form method="GET" action="<?= e(url('/admin/danh-muc/eform')) ?>" style="display: flex; gap: 1rem; align-items: center;">
        <label style="font-weight: 600; font-size: 0.9rem; white-space: nowrap;">Thủ tục hành chính áp dụng:</label>
        <select name="tthc_id" class="form-control" style="max-width: 500px;" onchange="this.form.submit()">
            <?php foreach ($tthcList as $tt): ?>
                <option value="<?= $tt['id'] ?>" <?= ((int)$currentTthcId === (int)$tt['id']) ? 'selected' : '' ?>>
                    [<?= e($tt['ma_rut_gon']) ?>] <?= e($tt['ten_tthc']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="badge badge-info" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
            Phiên bản hiện tại: v<?= e($eform['phien_ban'] ?? '1') ?>
        </span>
    </form>
</div>

<!-- Giao diện thiết kế 2 cột: Cột trái Trình dựng trường, Cột phải Schema JSON -->
<div class="row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Cột trái: Trình tạo trường Form Builder -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <form id="eform-main-form" action="<?= e(url('/admin/danh-muc/eform/luu')) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="tthc_id" value="<?= e($currentTthcId) ?>">
            <input type="hidden" name="schema_json" id="schema_json_input">

            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Tiêu đề biểu mẫu E-Form:</label>
                <input type="text" name="ten_bieu_mau" id="form_title" class="form-control" value="<?= e($schema['title'] ?? 'Tờ khai điện tử') ?>" required>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
                <h3 style="font-size: 1.1rem; margin: 0; color: #334155;">Danh sách các trường thông tin (Fields)</h3>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="addNewField()">
                    ➕ Thêm trường mới
                </button>
            </div>

            <!-- Vùng chứa các trường -->
            <div id="fields-container" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Render động bằng Javascript -->
            </div>
        </form>
    </div>

    <!-- Cột phải: Xem trực tiếp Schema JSON & Hướng dẫn VNeID -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="card p-3" style="background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <h4 style="font-size: 0.95rem; margin: 0; color: #1e293b; font-weight: 600;">JSON Schema định nghĩa</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="syncJsonToFields()">🔄 Cập nhật từ JSON</button>
            </div>
            <textarea id="schema_json_raw" class="form-control" style="font-family: monospace; font-size: 0.75rem; height: 350px; resize: vertical;"></textarea>
        </div>

        <div class="card p-3" style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: var(--radius-md);">
            <h4 style="font-size: 0.9rem; margin: 0 0 0.5rem 0; color: #1d4ed8; font-weight: 600;">💡 Tích hợp VNeID Auto-fill (C06)</h4>
            <p style="font-size: 0.8rem; color: #334155; margin: 0; line-height: 1.4;">
                Khi chọn thuộc tính VNeID tương ứng, hệ thống sẽ tự động điền thông tin định danh của công dân từ token OIDC khi công dân nộp hồ sơ, không cho phép chỉnh sửa sai lệch so với CSDL Dân cư Quốc gia.
            </p>
        </div>
    </div>
</div>

<!-- Modal Live Preview E-Form -->
<div id="modal-preview-eform" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: #fff; width: 100%; max-width: 800px; max-height: 90vh; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-primary); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="preview-modal-title" style="margin: 0; font-size: 1.15rem; color: #fff;">Xem trước biểu mẫu điện tử</h3>
            <button type="button" onclick="document.getElementById('modal-preview-eform').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="preview-modal-body" style="padding: 1.5rem; overflow-y: auto; flex: 1;">
            <!-- Form render mẫu ở đây -->
        </div>
        <div style="padding: 0.75rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-preview-eform').style.display='none'">Đóng xem trước</button>
        </div>
    </div>
</div>

<script>
let formFields = <?= json_encode($schema['fields'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

const FIELD_TYPES = {
    'text': 'Văn bản ngắn (Text)',
    'number': 'Chữ số / Số tiền (Number)',
    'date': 'Ngày tháng năm (Date)',
    'select': 'Danh sách chọn (Select)',
    'cccd': 'Số CCCD / ĐDCN (12 chữ số)',
    'dvhc_address': 'Địa chỉ ĐVHC 3 cấp (Tỉnh - Huyện - Xã)',
    'repeater': 'Bảng lặp động (Repeater / Table)',
    'file': 'Tệp đính kèm (File / PDF / Ảnh)',
    'textarea': 'Văn bản nhiều dòng (Textarea)'
};

const VNEID_ATTRIBUTES = {
    '': '-- Không tự động điền --',
    'ho_ten': 'Họ và tên (ho_ten)',
    'so_cccd': 'Số CCCD (so_cccd)',
    'ngay_sinh': 'Ngày sinh (ngay_sinh)',
    'gioi_tinh': 'Giới tính (gioi_tinh)',
    'que_quan': 'Quê quán (que_quan)',
    'dia_chi_thuong_tru': 'Địa chỉ thường trú (dia_chi_thuong_tru)'
};

function renderFieldsList() {
    const container = document.getElementById('fields-container');
    container.innerHTML = '';

    formFields.forEach((field, index) => {
        const item = document.createElement('div');
        item.style.border = '1px solid var(--border-color)';
        item.style.borderRadius = 'var(--radius-sm)';
        item.style.padding = '1rem';
        item.style.background = '#fafafa';
        item.style.position = 'relative';

        item.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="background: #e2e8f0; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">${index + 1}</span>
                    <span>${field.label || 'Trường chưa đặt tên'}</span>
                    <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.75rem;">${FIELD_TYPES[field.type] || field.type}</span>
                    ${field.autofill_vneid ? '<span class="badge badge-success" style="font-size: 0.7rem;">⚡ VNeID: ' + field.autofill_vneid + '</span>' : ''}
                </div>
                <div style="display: flex; gap: 0.25rem;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveField(${index}, -1)" ${index === 0 ? 'disabled' : ''}>▲</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveField(${index}, 1)" ${index === formFields.length - 1 ? 'disabled' : ''}>▼</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteField(${index})">🗑️</button>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1.2fr auto; gap: 0.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.75rem; color: #64748b;">Mã trường (ID):</label>
                    <input type="text" class="form-control" value="${field.id || ''}" onchange="updateFieldProp(${index}, 'id', this.value)" style="font-size: 0.8rem; padding: 0.3rem 0.5rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: #64748b;">Nhãn hiển thị (Label):</label>
                    <input type="text" class="form-control" value="${field.label || ''}" onchange="updateFieldProp(${index}, 'label', this.value)" style="font-size: 0.8rem; padding: 0.3rem 0.5rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: #64748b;">Loại trường:</label>
                    <select class="form-control" onchange="updateFieldProp(${index}, 'type', this.value)" style="font-size: 0.8rem; padding: 0.3rem 0.5rem;">
                        ${Object.entries(FIELD_TYPES).map(([k, v]) => `<option value="${k}" ${field.type === k ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: #15803d; font-weight: 600;">⚡ VNeID Auto-fill:</label>
                    <select class="form-control" onchange="updateFieldProp(${index}, 'autofill_vneid', this.value)" style="font-size: 0.8rem; padding: 0.3rem 0.5rem; border-color: #86efac;">
                        ${Object.entries(VNEID_ATTRIBUTES).map(([k, v]) => `<option value="${k}" ${(field.autofill_vneid || '') === k ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
                <div style="text-align: center;">
                    <label style="font-size: 0.75rem; color: #64748b; display: block;">Bắt buộc:</label>
                    <input type="checkbox" ${field.required ? 'checked' : ''} onchange="updateFieldProp(${index}, 'required', this.checked)">
                </div>
            </div>
        `;
        container.appendChild(item);
    });

    updateRawJson();
}

function updateFieldProp(index, prop, value) {
    formFields[index][prop] = value;
    renderFieldsList();
}

function addNewField() {
    formFields.push({
        id: 'field_' + (formFields.length + 1),
        type: 'text',
        label: 'Trường thông tin mới',
        required: false,
        autofill_vneid: '',
        placeholder: ''
    });
    renderFieldsList();
}

function deleteField(index) {
    if (confirm('Xóa trường này khỏi biểu mẫu?')) {
        formFields.splice(index, 1);
        renderFieldsList();
    }
}

function moveField(index, dir) {
    const newIndex = index + dir;
    if (newIndex >= 0 && newIndex < formFields.length) {
        const temp = formFields[index];
        formFields[index] = formFields[newIndex];
        formFields[newIndex] = temp;
        renderFieldsList();
    }
}

function updateRawJson() {
    const schema = {
        title: document.getElementById('form_title').value,
        fields: formFields
    };
    document.getElementById('schema_json_raw').value = JSON.stringify(schema, null, 2);
}

function syncJsonToFields() {
    try {
        const parsed = JSON.parse(document.getElementById('schema_json_raw').value);
        if (parsed && Array.isArray(parsed.fields)) {
            formFields = parsed.fields;
            if (parsed.title) document.getElementById('form_title').value = parsed.title;
            renderFieldsList();
        }
    } catch (e) {
        alert('JSON không hợp lệ: ' + e.message);
    }
}

function submitEformForm() {
    const schema = {
        title: document.getElementById('form_title').value,
        fields: formFields
    };
    document.getElementById('schema_json_input').value = JSON.stringify(schema);
    document.getElementById('eform-main-form').submit();
}

function previewEformModal() {
    const title = document.getElementById('form_title').value;
    document.getElementById('preview-modal-title').textContent = 'Xem trước: ' + title;
    const body = document.getElementById('preview-modal-body');
    body.innerHTML = '';

    formFields.forEach(field => {
        const fg = document.createElement('div');
        fg.className = 'form-group mb-3';
        
        let labelHtml = `<label class="form-label" style="font-weight: 600;">${field.label}`;
        if (field.required) labelHtml += ' <span style="color: red;">*</span>';
        if (field.autofill_vneid) labelHtml += ` <span class="badge badge-success" style="font-size: 0.7rem; margin-left: 0.5rem;">🔒 Tự điền từ VNeID: ${field.autofill_vneid}</span>`;
        labelHtml += '</label>';

        let inputHtml = '';
        if (field.type === 'select') {
            inputHtml = `<select class="form-control"><option value="">-- Chọn --</option><option>Giá trị mẫu 1</option><option>Giá trị mẫu 2</option></select>`;
        } else if (field.type === 'textarea') {
            inputHtml = `<textarea class="form-control" rows="3" placeholder="${field.placeholder || ''}"></textarea>`;
        } else if (field.type === 'dvhc_address') {
            inputHtml = `
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                    <select class="form-control"><option>TP. Hải Phòng</option></select>
                    <select class="form-control"><option>Quận Hồng Bàng</option></select>
                    <select class="form-control"><option>Phường Hoàng Văn Thụ</option></select>
                </div>
            `;
        } else if (field.type === 'file') {
            inputHtml = `<input type="file" class="form-control">`;
        } else if (field.type === 'repeater') {
            inputHtml = `
                <table class="table table-bordered mb-1" style="font-size: 0.8rem;">
                    <thead style="background: #f1f5f9;"><tr><th>STT</th><th>Nội dung</th><th>Ghi chú</th></tr></thead>
                    <tbody><tr><td>1</td><td><input type="text" class="form-control" style="font-size: 0.8rem;"></td><td><input type="text" class="form-control" style="font-size: 0.8rem;"></td></tr></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary">+ Thêm dòng</button>
            `;
        } else {
            inputHtml = `<input type="${field.type === 'date' ? 'date' : (field.type === 'number' ? 'number' : 'text')}" class="form-control" placeholder="${field.placeholder || ''}">`;
        }

        fg.innerHTML = labelHtml + inputHtml;
        body.appendChild(fg);
    });

    document.getElementById('modal-preview-eform').style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => {
    renderFieldsList();
});
</script>
<?php $this->endSection(); ?>
