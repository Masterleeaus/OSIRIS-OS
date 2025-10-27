const { app, BrowserWindow, Menu, ipcMain, autoUpdater } = require('electron');
const path = require('path');

function createWindow() {
  const mainWindow = new BrowserWindow({
    width: 1200,
    height: 800,
    minWidth: 800,
    minHeight: 600,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false,
      preload: path.join(__dirname, 'preload.js')
    },
    icon: path.join(__dirname, 'assets/icon.icns'),
    titleBarStyle: 'default',
    show: false
  });

  const startUrl = isDev
    ? 'http://localhost:3000'
    : `file://${path.join(__dirname, '../dist/index.html')}`;

  mainWindow.loadURL(startUrl);

  // Show window when ready to prevent visual flash
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
  });

  // Open DevTools in development
  if (isDev) {
    mainWindow.webContents.openDevTools();
  }

  // Auto-updater
  if (!isDev) {
    autoUpdater.checkForUpdatesAndNotify();
  }

  return mainWindow;
}

// Create native macOS menu
function createMenu() {
  const template = [
    {
      label: 'AIPlatform',
      submenu: [
        {
          label: 'About AIPlatform',
          role: 'about'
        },
        {
          type: 'separator'
        },
        {
          label: 'Services',
          role: 'services'
        },
        {
          type: 'separator'
        },
        {
          label: 'Hide AIPlatform',
          accelerator: 'Command+H',
          role: 'hide'
        },
        {
          label: 'Hide Others',
          accelerator: 'Command+Shift+H',
          role: 'hideothers'
        },
        {
          label: 'Show All',
          role: 'unhide'
        },
        {
          type: 'separator'
        },
        {
          label: 'Quit',
          accelerator: 'Command+Q',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'Edit',
      submenu: [
        {
          label: 'Undo',
          accelerator: 'Command+Z',
          role: 'undo'
        },
        {
          label: 'Redo',
          accelerator: 'Shift+Command+Z',
          role: 'redo'
        },
        {
          type: 'separator'
        },
        {
          label: 'Cut',
          accelerator: 'Command+X',
          role: 'cut'
        },
        {
          label: 'Copy',
          accelerator: 'Command+C',
          role: 'copy'
        },
        {
          label: 'Paste',
          accelerator: 'Command+V',
          role: 'paste'
        },
        {
          label: 'Select All',
          accelerator: 'Command+A',
          role: 'selectall'
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        {
          label: 'Reload',
          accelerator: 'Command+R',
          click: () => BrowserWindow.getFocusedWindow().webContents.reload()
        },
        {
          label: 'Toggle Developer Tools',
          accelerator: 'Alt+Command+I',
          click: () => BrowserWindow.getFocusedWindow().webContents.toggleDevTools()
        },
        {
          type: 'separator'
        },
        {
          label: 'Reset Zoom',
          role: 'resetzoom'
        },
        {
          label: 'Zoom In',
          role: 'zoomin'
        },
        {
          label: 'Zoom Out',
          role: 'zoomout'
        },
        {
          type: 'separator'
        },
        {
          label: 'Toggle Fullscreen',
          role: 'togglefullscreen'
        }
      ]
    },
    {
      label: 'Window',
      submenu: [
        {
          label: 'Minimize',
          accelerator: 'Command+M',
          role: 'minimize'
        },
        {
          label: 'Close',
          accelerator: 'Command+W',
          role: 'close'
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

app.whenReady().then(() => {
  createMenu();
  createWindow();
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

const { app, BrowserWindow, Menu, ipcMain, autoUpdater, dialog, Notification } = require('electron');
const path = require('path');
const fs = require('fs');

// Blockchain and AI service imports (add these when implementing)
let blockchainService = null;
let aiService = null;

function createWindow() {
  // ... existing window creation code ...

  // IPC handlers
  setupIPCHandlers(mainWindow);

  return mainWindow;
}

// Setup IPC handlers
function setupIPCHandlers(window) {
  // Blockchain handlers
  ipcMain.handle('connect-blockchain', async (event, network) => {
    try {
      // Initialize blockchain service
      if (!blockchainService) {
        const { PolkadotBridge } = require('../../bridges/blockchain-bridges/polkadot/bridge.js');
        blockchainService = new PolkadotBridge(network);
        await blockchainService.connect();
      }
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  });

  ipcMain.handle('get-balance', async (event, address) => {
    if (blockchainService) {
      return await blockchainService.getBalance(address);
    }
    return { error: 'Blockchain not connected' };
  });

  ipcMain.handle('send-transaction', async (event, to, amount) => {
    if (blockchainService) {
      return await blockchainService.sendTransaction(null, to, amount);
    }
    return { error: 'Blockchain not connected' };
  });

  // AI handlers
  ipcMain.handle('initialize-ai', async (event, provider) => {
    try {
      if (!aiService) {
        const { OpenAIBridge } = require('../../bridges/ai-bridges/openai/bridge.js');
        aiService = new OpenAIBridge();
        await aiService.connect();
      }
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  });

  ipcMain.handle('send-ai-message', async (event, message) => {
    if (aiService) {
      const response = await aiService.sendMessage(message);
      window.webContents.send('ai-response', response);
      return { success: true };
    }
    return { error: 'AI not initialized' };
  });

  // File system handlers
  ipcMain.handle('select-directory', async () => {
    const result = await dialog.showOpenDialog(window, {
      properties: ['openDirectory']
    });
    return result.canceled ? null : result.filePaths[0];
  });

  ipcMain.handle('read-file', async (event, filePath) => {
    try {
      return fs.readFileSync(filePath, 'utf8');
    } catch (error) {
      return { error: error.message };
    }
  });

  ipcMain.handle('write-file', async (event, filePath, content) => {
    try {
      fs.writeFileSync(filePath, content);
      return { success: true };
    } catch (error) {
      return { error: error.message };
    }
  });

  // Network handlers
  ipcMain.handle('check-connection', async () => {
    return require('dns').promises.resolve('google.com').then(() => true).catch(() => false);
  });

  // Settings handlers
  ipcMain.handle('get-settings', () => {
    const settingsPath = path.join(app.getPath('userData'), 'settings.json');
    try {
      return JSON.parse(fs.readFileSync(settingsPath, 'utf8'));
    } catch {
      return {};
    }
  });

  ipcMain.handle('save-settings', (event, settings) => {
    const settingsPath = path.join(app.getPath('userData'), 'settings.json');
    fs.writeFileSync(settingsPath, JSON.stringify(settings, null, 2));
    return { success: true };
  });

  // Auto-updater handlers
  ipcMain.handle('check-for-updates', () => {
    if (!isDev) {
      autoUpdater.checkForUpdates();
    }
    return { success: true };
  });

  // Notification handlers
  ipcMain.handle('show-notification', (event, title, body) => {
    if (Notification.isSupported()) {
      new Notification({ title, body }).show();
    }
    return { success: true };
  });

  // Auto-updater events
  autoUpdater.on('update-available', () => {
    window.webContents.send('update-available');
  });

  autoUpdater.on('update-downloaded', () => {
    window.webContents.send('update-downloaded');
  });
}
