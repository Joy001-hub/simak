const { app, BrowserWindow, shell, ipcMain } = require('electron');
const path = require('node:path');

const isDev = process.env.NODE_ENV === 'development' || !app.isPackaged;
const devServerUrl = process.env.DEV_SERVER_URL || process.env.VITE_DEV_SERVER_URL || process.env.VITE_DEV_URL;

function createMainWindow() {
    const mainWindow = new BrowserWindow({
        width: 1366,
        height: 840,
        backgroundColor: '#0b1224',
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.cjs'),
            contextIsolation: true,
            nodeIntegration: false,
            sandbox: false,
        },
    });

    if (isDev && devServerUrl) {
        mainWindow.loadURL(devServerUrl);
        mainWindow.webContents.openDevTools({ mode: 'detach' });
    } else {
        const indexPath = path.join(__dirname, 'public', 'build', 'index.html');
        mainWindow.loadFile(indexPath);
    }

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });

    return mainWindow;
}

app.whenReady().then(() => {
    app.setAppUserModelId('com.simak.desktop');
    createMainWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createMainWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

ipcMain.handle('runtime-info', () => ({
    isDev,
    isPackaged: app.isPackaged,
    appVersion: app.getVersion(),
    userDataPath: app.getPath('userData'),
    platform: process.platform,
}));
