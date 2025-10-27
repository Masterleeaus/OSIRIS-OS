const { app, BrowserWindow, Menu, ipcMain, autoUpdater, Notification, Tray } = require('electron');
const path = require('path');
const isDev = process.env.NODE_ENV === 'development';

let tray = null;
let mainWindow = null;

function createWindow() {
  mainWindow = new BrowserWindow({
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
    icon: path.join(__dirname, 'assets/icon.ico'),
    titleBarStyle: 'default',
    show: false,
    // Windows-specific optimizations
    backgroundColor: '#ffffff'
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

  // Create tray icon
  createTray();

  // Windows-specific window management
  mainWindow.on('minimize', () => {
    mainWindow.hide();
  });

  return mainWindow;
}

// Create Windows tray icon
function createTray() {
  const trayIcon = path.join(__dirname, 'assets/tray-icon.png');
  tray = new Tray(trayIcon);

  const contextMenu = Menu.buildFromTemplate([
    {
      label: 'Show AIPlatform',
      click: () => {
        mainWindow.show();
        mainWindow.focus();
      }
    },
    {
      label: 'Quit',
      click: () => {
        app.isQuiting = true;
        app.quit();
      }
    }
  ]);

  tray.setToolTip('AIPlatform - Decentralized AI Platform');
  tray.setContextMenu(contextMenu);

  tray.on('double-click', () => {
    mainWindow.show();
    mainWindow.focus();
  });
}

// Create Windows-specific menu
function createMenu() {
  const template = [
    {
      label: 'File',
      submenu: [
        {
          label: 'New Project',
          accelerator: 'Ctrl+N',
          click: () => mainWindow.webContents.send('menu-action', 'new-project')
        },
        {
          label: 'Open Project',
          accelerator: 'Ctrl+O',
          click: () => mainWindow.webContents.send('menu-action', 'open-project')
        },
        {
          type: 'separator'
        },
        {
          label: 'Exit',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'Edit',
      submenu: [
        {
          label: 'Undo',
          accelerator: 'Ctrl+Z',
          role: 'undo'
        },
        {
          label: 'Redo',
          accelerator: 'Ctrl+Y',
          role: 'redo'
        },
        {
          type: 'separator'
        },
        {
          label: 'Cut',
          accelerator: 'Ctrl+X',
          role: 'cut'
        },
        {
          label: 'Copy',
          accelerator: 'Ctrl+C',
          role: 'copy'
        },
        {
          label: 'Paste',
          accelerator: 'Ctrl+V',
          role: 'paste'
        },
        {
          label: 'Select All',
          accelerator: 'Ctrl+A',
          role: 'selectall'
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        {
          label: 'Reload',
          accelerator: 'Ctrl+R',
          click: () => mainWindow.webContents.reload()
        },
        {
          label: 'Toggle Developer Tools',
          accelerator: 'Ctrl+Shift+I',
          click: () => mainWindow.webContents.toggleDevTools()
        },
        {
          type: 'separator'
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
          label: 'Reset Zoom',
          role: 'resetzoom'
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
      label: 'Help',
      submenu: [
        {
          label: 'Documentation',
          click: () => require('electron').shell.openExternal('https://docs.aiplatform.org')
        },
        {
          label: 'About',
          click: () => require('electron').shell.openExternal('https://aiplatform.org/about')
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
  setupIPCHandlers();
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});

app.on('before-quit', () => {
  app.isQuiting = true;
  if (tray) {
    tray.destroy();
  }
});

// Security: Prevent new window creation
app.on('web-contents-created', (event, contents) => {
  contents.on('new-window', (event, navigationUrl) => {
    event.preventDefault();
    require('electron').shell.openExternal(navigationUrl);
  });
});

// Setup IPC handlers (similar to macOS but Windows-specific)
function setupIPCHandlers() {
  // Windows notification handler
  ipcMain.handle('show-notification', (event, title, body, options = {}) => {
    if (Notification.isSupported()) {
      const notification = new Notification({
        title,
        body,
        icon: path.join(__dirname, 'assets/icon.ico'),
        ...options
      });

      notification.on('click', () => {
        mainWindow.show();
        mainWindow.focus();
      });

      notification.show();
    }
    return { success: true };
  });

  // Windows-specific file associations
  ipcMain.handle('register-file-associations', async (event, extensions) => {
    // Register file associations for Windows
    const { spawn } = require('child_process');
    return new Promise((resolve) => {
      const child = spawn('reg', ['add', 'HKCR\\.aip\\', '/ve', '/d', 'AIPlatform.Project', '/f'], { stdio: 'inherit' });
      child.on('close', (code) => resolve({ success: code === 0 }));
    });
  });

  // Add other IPC handlers similar to macOS
  // ... (blockchain, AI, file system handlers)
}
