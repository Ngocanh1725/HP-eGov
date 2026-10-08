const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const USER_DATA_DIR = path.join(__dirname, '..', 'storage', 'temp_chrome');
const ARTIFACT_DIR = "C:\\Users\\Admin\\.gemini\\antigravity-ide\\brain\\3434ccef-25a7-4322-9431-bb74355c843a";

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function waitForHttp(url, retries = 30) {
    for (let i = 0; i < retries; i++) {
        try {
            const res = await fetch(url);
            if (res.ok) return await res.json();
        } catch (e) {}
        await sleep(300);
    }
    throw new Error(`Cannot connect to ${url}`);
}

async function main() {
    console.log("1. Khởi động Headless Chrome...");
    if (!fs.existsSync(USER_DATA_DIR)) {
        fs.mkdirSync(USER_DATA_DIR, { recursive: true });
    }

    const chrome = spawn(CHROME_PATH, [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-sandbox',
        '--window-size=1440,960',
        `--user-data-dir=${USER_DATA_DIR}`
    ]);

    let ws = null;
    try {
        console.log("2. Kết nối Chrome DevTools Protocol...");
        const versionInfo = await waitForHttp('http://127.0.0.1:9222/json/version');
        const wsUrl = versionInfo.webSocketDebuggerUrl;
        console.log("WebSocket URL:", wsUrl);

        ws = new WebSocket(wsUrl);
        await new Promise((resolve, reject) => {
            ws.onopen = resolve;
            ws.onerror = reject;
        });

        let idCounter = 1;
        const pending = new Map();
        const consoleMessages = [];

        ws.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && pending.has(msg.id)) {
                const { resolve, reject } = pending.get(msg.id);
                pending.delete(msg.id);
                if (msg.error) reject(msg.error);
                else resolve(msg.result);
            }
        };

        function send(method, params = {}) {
            return new Promise((resolve, reject) => {
                const id = idCounter++;
                pending.set(id, { resolve, reject });
                ws.send(JSON.stringify({ id, method, params }));
            });
        }

        // Tạo target mới
        const { targetId } = await send('Target.createTarget', { url: 'about:blank' });

        // Giao tiếp trực tiếp qua page WebSocket
        const targets = await waitForHttp('http://127.0.0.1:9222/json/list');
        const pageTarget = targets.find(t => t.id === targetId || t.type === 'page');
        const pageWs = new WebSocket(pageTarget.webSocketDebuggerUrl);

        await new Promise((resolve, reject) => {
            pageWs.onopen = resolve;
            pageWs.onerror = reject;
        });

        const pagePending = new Map();
        pageWs.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.id && pagePending.has(msg.id)) {
                const { resolve, reject } = pagePending.get(msg.id);
                pagePending.delete(msg.id);
                if (msg.error) reject(msg.error);
                else resolve(msg.result);
            }
            if (msg.method === 'Runtime.consoleAPICalled') {
                consoleMessages.push(msg.params);
            }
            if (msg.method === 'Log.entryAdded') {
                consoleMessages.push(msg.params.entry);
            }
        };

        function sendPage(method, params = {}) {
            return new Promise((resolve, reject) => {
                const id = idCounter++;
                pagePending.set(id, { resolve, reject });
                pageWs.send(JSON.stringify({ id, method, params }));
            });
        }

        await sendPage('Page.enable');
        await sendPage('Runtime.enable');
        await sendPage('Log.enable');
        await sendPage('Network.enable');
        await sendPage('Network.setCacheDisabled', { cacheDisabled: true });
        await sendPage('Emulation.setDeviceMetricsOverride', {
            width: 1440,
            height: 960,
            deviceScaleFactor: 1,
            mobile: false
        });

        async function navigateAndWait(url) {
            console.log(`Đang mở: ${url}...`);
            await sendPage('Page.navigate', { url });
            await sleep(2500); // Đợi tải DOM và render font
        }

        async function takeScreenshot(filename, fullPage = false) {
            let params = { format: 'png' };
            if (fullPage) {
                params.captureBeyondViewport = true;
            }
            const result = await sendPage('Page.captureScreenshot', params);
            const filePath = path.join(ARTIFACT_DIR, filename);
            fs.writeFileSync(filePath, Buffer.from(result.data, 'base64'));
            console.log(`✔ Đã lưu ảnh: ${filePath}`);
            return filePath;
        }

        async function evalScript(expression) {
            const res = await sendPage('Runtime.evaluate', { expression, returnByValue: true });
            return res.result?.value;
        }

        // 1. Mở trang đăng nhập
        await navigateAndWait('http://localhost/HP-eGov/admin/dang-nhap');
        console.log("3. Đang đăng nhập tài khoản motcua.hongbang...");
        await evalScript(`
            document.getElementById('ten_dang_nhap').value = 'motcua.hongbang';
            document.getElementById('mat_khau').value = 'Hpegov@2026';
            document.getElementById('form-admin-login').submit();
        `);
        await sleep(2500);

        // 2. Chụp Dashboard
        await navigateAndWait('http://localhost/HP-eGov/admin/dashboard');
        await takeScreenshot('phien3_01_dashboard_workstation.png');

        // 3. Chụp Hộp việc thụ lý chuyên viên
        await navigateAndWait('http://localhost/HP-eGov/admin/thu-ly/hop-viec');
        await takeScreenshot('phien3_02_hop_viec_thu_ly.png');

        // 4. Chụp Bàn tiếp nhận một cửa
        await navigateAndWait('http://localhost/HP-eGov/admin/mot-cua/tiep-nhan?tthc_id=3');
        await takeScreenshot('phien3_03_mot_cua_tiep_nhan.png');

        // 5. Chụp Mẫu in phiếu hẹn (Mẫu 01) - Tăng viewport để hiển thị trọn vẹn cả trang A4 kèm QR
        await sendPage('Emulation.setDeviceMetricsOverride', {
            width: 1440,
            height: 1200,
            deviceScaleFactor: 1,
            mobile: false
        });
        await navigateAndWait('http://localhost/HP-eGov/admin/ho-so/1/in-phieu-hen');
        await takeScreenshot('phien3_04_in_phieu_hen_mau01.png', true);

        console.log("Kiểm tra console logs:", consoleMessages.length, "thông điệp.");
        const errors = consoleMessages.filter(m => m.level === 'error' || m.type === 'error');
        console.log("Số lỗi console JavaScript:", errors.length);
        if (errors.length > 0) {
            console.log("Chi tiết lỗi:", JSON.stringify(errors, null, 2));
        }

        pageWs.close();
        ws.close();
        console.log("✔ Hoàn thành chụp ảnh màn hình 4 trang công vụ thành công!");
    } catch (err) {
        console.error("Lỗi:", err);
    } finally {
        chrome.kill();
    }
}

main();
