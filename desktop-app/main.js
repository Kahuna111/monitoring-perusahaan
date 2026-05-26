const { app, BrowserWindow, Menu, shell } = require('electron');
const path = require('path');
const fs = require('fs');

let win;
let targetUrl = 'http://localhost:8000';

function readConfig() {
    let configPath = path.join(__dirname, 'config.json');

    if (app.isPackaged) {
        const exeDir = path.dirname(process.execPath);
        const prodConfig = path.join(exeDir, 'config.json');
        
        // If config doesn't exist next to EXE, copy the default one
        if (!fs.existsSync(prodConfig)) {
            try {
                fs.copyFileSync(configPath, prodConfig);
            } catch (err) {
                console.error('Gagal menyalin default config.json:', err);
            }
        }
        configPath = prodConfig;
    }

    try {
        if (fs.existsSync(configPath)) {
            const data = fs.readFileSync(configPath, 'utf8');
            const config = JSON.parse(data);
            if (config.url) {
                targetUrl = config.url.trim().replace(/\/$/, ''); // strip trailing slash
            }
        }
    } catch (err) {
        console.error('Gagal membaca file konfigurasi:', err);
    }
}

function createWindow() {
    readConfig();

    win = new BrowserWindow({
        width: 1280,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        title: 'MonitorPro',
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            sandbox: true
        },
        show: false, // show when ready to avoid white flash
        backgroundColor: '#0f1117'
    });

    win.loadURL(targetUrl);

    // Show window when ready
    win.once('ready-to-show', () => {
        win.show();
    });

    // Handle connection failures
    win.webContents.on('did-fail-load', (event, errorCode, errorDescription, validatedURL) => {
        // If the main frame failed to load, redirect to offline page
        if (win.webContents.getURL() === '' || validatedURL.replace(/\/$/, '') === targetUrl) {
            win.loadURL('file://' + path.join(__dirname, 'offline.html') + '?url=' + encodeURIComponent(targetUrl));
        }
    });

    // Open target links (e.g. downloads, external URLs) in browser
    win.webContents.setWindowOpenHandler(({ url }) => {
        if (!url.startsWith(targetUrl)) {
            shell.openExternal(url);
            return { action: 'deny' };
        }
        return { action: 'allow' };
    });

    // Premium custom application menu
    const menuTemplate = [
        {
            label: 'Aplikasi',
            submenu: [
                {
                    label: 'Segarkan Halaman (Reload)',
                    accelerator: 'F5',
                    click: () => {
                        win.loadURL(targetUrl);
                    }
                },
                {
                    label: 'Segarkan Tanpa Cache',
                    accelerator: 'CmdOrCtrl+R',
                    click: () => {
                        win.webContents.reloadIgnoringCache();
                    }
                },
                { type: 'separator' },
                {
                    label: 'Keluar',
                    accelerator: 'CmdOrCtrl+Q',
                    click: () => {
                        app.quit();
                    }
                }
            ]
        },
        {
            label: 'Tampilan',
            submenu: [
                { role: 'zoomIn', label: 'Perbesar' },
                { role: 'zoomOut', label: 'Perkecil' },
                { role: 'resetZoom', label: 'Reset Zoom' },
                { type: 'separator' },
                { role: 'togglefullscreen', label: 'Layar Penuh' }
            ]
        },
        {
            label: 'Bantuan',
            submenu: [
                {
                    label: 'Hubungi Support',
                    click: () => {
                        shell.openExternal('https://github.com');
                    }
                }
            ]
        }
    ];

    const menu = Menu.buildFromTemplate(menuTemplate);
    Menu.setApplicationMenu(menu);

    win.on('closed', () => {
        win = null;
    });
}

// Single instance lock
const additionalData = { myKey: 'monitorpro-desktop-single-instance' };
const isSingleInstance = app.requestSingleInstanceLock(additionalData);

if (!isSingleInstance) {
    app.quit();
} else {
    app.on('second-instance', () => {
        if (win) {
            if (win.isMinimized()) win.restore();
            win.focus();
        }
    });

    app.on('ready', createWindow);
}

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (win === null) {
        createWindow();
    }
});
