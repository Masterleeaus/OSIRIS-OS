const { app, BrowserWindow, Menu, ipcMain, autoUpdater, Notification, Tray, globalShortcut } = require('electron');
const path = require('path');
const fs = require('fs');
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
    icon: path.join(__dirname, 'assets/icon.png'),
    titleBarStyle: 'default',
    show: false,
    // Linux-specific optimizations
    backgroundColor: '#f5f5f5'
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

  // Create tray icon for Linux
  createTray();

  // Linux-specific window management
  mainWindow.on('minimize', () => {
    mainWindow.hide();
  });

  // Register global shortcuts
  registerGlobalShortcuts();

  return mainWindow;
}

// Create Linux tray icon
function createTray() {
  try {
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
        type: 'separator'
      },
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
  } catch (error) {
    console.log('Tray icon not available:', error.message);
  }
}

// Register global shortcuts
function registerGlobalShortcuts() {
  // Toggle window visibility
  globalShortcut.register('Control+Shift+A', () => {
    if (mainWindow.isVisible()) {
      mainWindow.hide();
    } else {
      mainWindow.show();
      mainWindow.focus();
    }
  });

  // New project shortcut
  globalShortcut.register('Control+Shift+N', () => {
    mainWindow.webContents.send('global-shortcut', 'new-project');
  });
}

// Create Linux-specific menu with desktop integration
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
          label: 'Save Project',
          accelerator: 'Ctrl+S',
          click: () => mainWindow.webContents.send('menu-action', 'save-project')
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
      label: 'Tools',
      submenu: [
        {
          label: 'Blockchain Console',
          click: () => mainWindow.webContents.send('menu-action', 'blockchain-console')
        },
        {
          label: 'AI Assistant',
          click: () => mainWindow.webContents.send('menu-action', 'ai-assistant')
        },
        {
          type: 'separator'
        },
        {
          label: 'Settings',
          click: () => mainWindow.webContents.send('menu-action', 'settings')
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
          label: 'Community',
          click: () => require('electron').shell.openExternal('https://discord.gg/aiplatform')
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
  globalShortcut.unregisterAll();
  if (tray) {
    tray.destroy();
  }
});

// Setup IPC handlers (Linux-specific)
function setupIPCHandlers() {
  // Desktop integration
  ipcMain.handle('integrate-desktop', async () => {
    // Create desktop file for Linux
    const desktopFile = `[Desktop Entry]
Version=1.0
Name=AIPlatform
Comment=Decentralized AI Platform
Exec=${app.getPath('exe')} %U
Icon=${app.getAppPath()}/assets/icon.png
Terminal=false
Type=Application
Categories=Development;AI;Blockchain;
MimeType=application/x-aiplatform-project;`;

    const desktopPath = path.join(require('os').homedir(), '.local/share/applications/aiplatform.desktop');
    fs.writeFileSync(desktopPath, desktopFile);
    return { success: true };
  });

  // Linux notification handler
  ipcMain.handle('show-notification', (event, title, body, options = {}) => {
    if (Notification.isSupported()) {
      const notification = new Notification({
        title,
        body,
        icon: path.join(__dirname, 'assets/icon.png'),
        urgency: options.urgency || 'normal',
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

  // Add other IPC handlers similar to Windows/macOS
  // ... (blockchain, AI, file system handlers)
}
