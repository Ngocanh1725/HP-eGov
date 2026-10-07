/**
 * ============================================================================
 * HP-eGov JAVASCRIPT FRAMEWORK: app.js
 * Chuẩn module ES6, không dùng inline script để tuân thủ chính sách CSP Cấp độ 3.
 * Cung cấp: Fetch wrapper (tự động gắn CSRF), Toast, Modal, Confirm, Table, Dropzone, Dark Mode.
 * ============================================================================
 */

(function (window, document) {
    'use strict';

    const App = {
        csrfToken: '',

        init() {
            this.setupCsrf();
            this.setupDarkMode();
            this.setupSidebar();
            this.setupDropdowns();
            this.setupTabs();
            this.setupDataTables();
            this.setupDropzones();
            this.setupModals();
        },

        /* 1. CSRF TOKEN & HTTP FETCH WRAPPER */
        setupCsrf() {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                this.csrfToken = metaToken.getAttribute('content') || '';
            } else {
                const inputToken = document.querySelector('input[name="_csrf"]');
                if (inputToken) {
                    this.csrfToken = inputToken.value || '';
                }
            }
        },

        async fetch(url, options = {}) {
            const defaultHeaders = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            };

            if (this.csrfToken) {
                defaultHeaders['X-CSRF-TOKEN'] = this.csrfToken;
            }

            options.headers = {
                ...defaultHeaders,
                ...(options.headers || {})
            };

            // Nếu body là object thường (không phải FormData), chuyển thành JSON
            if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            try {
                const response = await window.fetch(url, options);

                // Xử lý mã lỗi bảo mật phổ biến
                if (response.status === 401) {
                    App.toast.error('Chưa xác thực', 'Phiên làm việc đã hết hạn. Đang chuyển hướng đăng nhập...');
                    setTimeout(() => window.location.href = '/HP-eGov/admin/dang-nhap', 1500);
                    return response;
                }

                if (response.status === 419) {
                    App.toast.warning('Hết hạn bảo mật', 'Mã xác thực CSRF đã hết hạn. Vui lòng tải lại trang.');
                    return response;
                }

                if (response.status === 429) {
                    App.toast.error('Quá giới hạn', 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng chờ trong giây lát.');
                    return response;
                }

                return response;
            } catch (err) {
                App.toast.error('Lỗi mạng', 'Không thể kết nối đến máy chủ: ' + err.message);
                throw err;
            }
        },

        /* 2. HỆ THỐNG THÔNG BÁO TOAST */
        toast: {
            container: null,

            getContainer() {
                if (!this.container) {
                    this.container = document.querySelector('.toast-container');
                    if (!this.container) {
                        this.container = document.createElement('div');
                        this.container.className = 'toast-container';
                        document.body.appendChild(this.container);
                    }
                }
                return this.container;
            },

            show(type, title, message, duration = 4000) {
                const container = this.getContainer();
                const toastEl = document.createElement('div');
                toastEl.className = `toast toast-${type}`;

                const icons = {
                    success: '✔',
                    error: '✖',
                    warning: '⚠',
                    info: 'ℹ',
                };

                toastEl.innerHTML = `
                    <div style="font-size:1.2rem;font-weight:bold;">${icons[type] || '•'}</div>
                    <div class="toast-content">
                        <div class="toast-title">${App.escape(title)}</div>
                        <div class="toast-message">${App.escape(message)}</div>
                    </div>
                `;

                container.appendChild(toastEl);

                // Animation hiện toast
                requestAnimationFrame(() => {
                    toastEl.classList.add('show');
                });

                setTimeout(() => {
                    toastEl.classList.remove('show');
                    setTimeout(() => toastEl.remove(), 300);
                }, duration);
            },

            success(title, message) { this.show('success', title, message); },
            error(title, message)   { this.show('error', title, message, 5000); },
            warning(title, message) { this.show('warning', title, message); },
            info(title, message)    { this.show('info', title, message); },
        },

        /* 3. HỘP THOẠI MODAL & CONFIRM */
        modal: {
            open(modalId) {
                const el = document.getElementById(modalId);
                if (el) {
                    el.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            },

            close(modalId) {
                const el = document.getElementById(modalId);
                if (el) {
                    el.classList.remove('show');
                    document.body.style.overflow = '';
                }
            },
        },

        confirm(title, message, onConfirm) {
            let confirmModal = document.getElementById('app-confirm-modal');
            if (!confirmModal) {
                confirmModal = document.createElement('div');
                confirmModal.id = 'app-confirm-modal';
                confirmModal.className = 'modal-backdrop';
                confirmModal.innerHTML = `
                    <div class="modal-card">
                        <div class="modal-header">
                            <h3 class="modal-title" id="app-confirm-title">Xác nhận</h3>
                            <button class="modal-close" id="btn-close-confirm">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p id="app-confirm-message" style="margin:0;font-size:0.95rem;color:var(--text-main);"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="btn-cancel-confirm">Hủy bỏ</button>
                            <button type="button" class="btn btn-accent" id="btn-accept-confirm">Đồng ý</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(confirmModal);

                document.getElementById('btn-close-confirm').addEventListener('click', () => {
                    App.modal.close('app-confirm-modal');
                });
                document.getElementById('btn-cancel-confirm').addEventListener('click', () => {
                    App.modal.close('app-confirm-modal');
                });
            }

            document.getElementById('app-confirm-title').textContent = title;
            document.getElementById('app-confirm-message').textContent = message;

            const acceptBtn = document.getElementById('btn-accept-confirm');
            const newAcceptBtn = acceptBtn.cloneNode(true);
            acceptBtn.parentNode.replaceChild(newAcceptBtn, acceptBtn);

            newAcceptBtn.addEventListener('click', () => {
                App.modal.close('app-confirm-modal');
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });

            App.modal.open('app-confirm-modal');
        },

        setupModals() {
            // Đóng modal khi bấm vào nút có data-modal-close
            document.addEventListener('click', (e) => {
                const target = e.target.closest('[data-modal-close]');
                if (target) {
                    const modal = target.closest('.modal-backdrop');
                    if (modal) {
                        App.modal.close(modal.id);
                    }
                }
            });

            // Đóng khi click vào backdrop ngoài thẻ card
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal-backdrop')) {
                    App.modal.close(e.target.id);
                }
            });
        },

        /* 4. CHẾ ĐỘ TỐI (DARK MODE) */
        setupDarkMode() {
            const savedTheme = localStorage.getItem('hpegov_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            const toggleBtns = document.querySelectorAll('[data-toggle="dark-mode"]');
            toggleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const current = document.documentElement.getAttribute('data-theme') || 'light';
                    const next = current === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('hpegov_theme', next);
                    App.toast.info('Giao diện', `Đã chuyển sang chế độ ${next === 'dark' ? 'Tối' : 'Sáng'}`);
                });
            });
        },

        /* 5. SIDEBAR CÁN BỘ (COLLAPSIBLE) */
        setupSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            const toggleBtn = document.querySelector('[data-toggle="sidebar"]');

            if (sidebar && toggleBtn) {
                // Đọc trạng thái lưu trước đó
                const isCollapsed = localStorage.getItem('hpegov_sidebar_collapsed') === 'true';
                if (isCollapsed && window.innerWidth > 992) {
                    sidebar.classList.add('collapsed');
                }

                toggleBtn.addEventListener('click', () => {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.toggle('show-mobile');
                    } else {
                        sidebar.classList.toggle('collapsed');
                        localStorage.setItem('hpegov_sidebar_collapsed', sidebar.classList.contains('collapsed'));
                    }
                });
            }
        },

        /* 6. DROPDOWN THÔNG BÁO & USER PROFILE */
        setupDropdowns() {
            document.addEventListener('click', (e) => {
                const toggle = e.target.closest('[data-dropdown-toggle]');
                const openMenus = document.querySelectorAll('.dropdown-menu.show');

                if (toggle) {
                    e.preventDefault();
                    const targetMenu = toggle.nextElementSibling;
                    if (targetMenu && targetMenu.classList.contains('dropdown-menu')) {
                        const isOpen = targetMenu.classList.contains('show');
                        openMenus.forEach(m => m.classList.remove('show'));
                        if (!isOpen) {
                            targetMenu.classList.add('show');
                        }
                    }
                    return;
                }

                if (!e.target.closest('.dropdown-menu')) {
                    openMenus.forEach(m => m.classList.remove('show'));
                }
            });
        },

        /* 7. ĐIỀU HƯỚNG TABS */
        setupTabs() {
            document.addEventListener('click', (e) => {
                const tabItem = e.target.closest('[data-tab-target]');
                if (!tabItem) return;

                const parent = tabItem.closest('.nav-tabs');
                if (!parent) return;

                const targetSelector = tabItem.getAttribute('data-tab-target');
                const targetContent = document.querySelector(targetSelector);

                // Bỏ active tất cả tabs trong cùng nhóm
                parent.querySelectorAll('.nav-tab-item').forEach(t => t.classList.remove('active'));
                tabItem.classList.add('active');

                // Ẩn/hiện nội dung tab tương ứng
                if (targetContent) {
                    const container = targetContent.parentElement;
                    container.querySelectorAll('.tab-pane').forEach(p => p.classList.add('d-none'));
                    targetContent.classList.remove('d-none');
                }
            });
        },

        /* 8. SẮP XẾP VÀ TÌM KIẾM BẢNG (DATA TABLES) */
        setupDataTables() {
            // Sắp xếp cột table
            document.addEventListener('click', (e) => {
                const th = e.target.closest('th.sortable');
                if (!th) return;

                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const colIdx = Array.from(th.parentNode.children).indexOf(th);
                const isAsc = !th.classList.contains('asc');

                // Xóa trạng thái các cột khác
                th.parentNode.querySelectorAll('th').forEach(h => h.classList.remove('asc', 'desc'));
                th.classList.toggle('asc', isAsc);
                th.classList.toggle('desc', !isAsc);

                rows.sort((rowA, rowB) => {
                    const cellA = rowA.children[colIdx]?.innerText.trim() || '';
                    const cellB = rowB.children[colIdx]?.innerText.trim() || '';

                    const numA = parseFloat(cellA.replace(/[^0-9.-]+/g, ''));
                    const numB = parseFloat(cellB.replace(/[^0-9.-]+/g, ''));

                    if (!isNaN(numA) && !isNaN(numB)) {
                        return isAsc ? numA - numB : numB - numA;
                    }
                    return isAsc ? cellA.localeCompare(cellB, 'vi') : cellB.localeCompare(cellA, 'vi');
                });

                rows.forEach(r => tbody.appendChild(r));
            });

            // Lọc dữ liệu table theo từ khóa
            document.addEventListener('input', (e) => {
                const searchInput = e.target.closest('[data-table-search]');
                if (!searchInput) return;

                const tableSelector = searchInput.getAttribute('data-table-search');
                const table = document.querySelector(tableSelector);
                if (!table) return;

                const query = searchInput.value.toLowerCase().trim();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        },

        /* 9. KÉO THẢ TỆP TIN (DRAG & DROP DROPZONE) */
        setupDropzones() {
            document.querySelectorAll('.dropzone').forEach(zone => {
                const fileInput = zone.querySelector('input[type="file"]');
                const progressContainer = zone.querySelector('.progress-bar-container');
                const progressFill = zone.querySelector('.progress-bar-fill');
                const fileNameDisplay = zone.querySelector('.dropzone-file-name');

                zone.addEventListener('click', (e) => {
                    if (e.target !== fileInput && fileInput) {
                        fileInput.click();
                    }
                });

                ['dragenter', 'dragover'].forEach(name => {
                    zone.addEventListener(name, (e) => {
                        e.preventDefault();
                        zone.classList.add('dragover');
                    });
                });

                ['dragleave', 'drop'].forEach(name => {
                    zone.addEventListener(name, (e) => {
                        e.preventDefault();
                        zone.classList.remove('dragover');
                    });
                });

                zone.addEventListener('drop', (e) => {
                    const files = e.dataTransfer?.files;
                    if (files && files.length > 0 && fileInput) {
                        fileInput.files = files;
                        handleFileSelection(files[0]);
                    }
                });

                if (fileInput) {
                    fileInput.addEventListener('change', () => {
                        if (fileInput.files && fileInput.files[0]) {
                            handleFileSelection(fileInput.files[0]);
                        }
                    });
                }

                function handleFileSelection(file) {
                    if (fileNameDisplay) {
                        fileNameDisplay.textContent = `Tệp đã chọn: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    }

                    // Mô phỏng tiến trình upload
                    if (progressContainer && progressFill) {
                        progressContainer.style.display = 'block';
                        progressFill.style.width = '0%';
                        let p = 0;
                        const interval = setInterval(() => {
                            p += 25;
                            progressFill.style.width = p + '%';
                            if (p >= 100) {
                                clearInterval(interval);
                                App.toast.success('Tải tệp thành công', `Đã chuẩn bị tệp ${file.name}`);
                            }
                        }, 120);
                    }
                }
            });
        },

        escape(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }
    };

    // Khởi động khi DOM đã tải xong
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => App.init());
    } else {
        App.init();
    }

    window.App = App;
})(window, document);
