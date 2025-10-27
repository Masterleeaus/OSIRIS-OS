const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods that allow the renderer process to use
// the ipcRenderer without exposing the entire object
contextBridge.exposeInMainWorld('electronAPI', {
  // Platform information
  platform: process.platform,
  versions: process.versions,

  // Blockchain integration
  connectBlockchain: (network) => ipcRenderer.invoke('connect-blockchain', network),
  disconnectBlockchain: () => ipcRenderer.invoke('disconnect-blockchain'),
  getBalance: (address) => ipcRenderer.invoke('get-balance', address),
  sendTransaction: (to, amount) => ipcRenderer.invoke('send-transaction', to, amount),

  // AI integration
  initializeAI: (provider) => ipcRenderer.invoke('initialize-ai', provider),
  sendAIMessage: (message) => ipcRenderer.invoke('send-ai-message', message),
  getAIResponse: (callback) => ipcRenderer.on('ai-response', callback),

  // File system operations (secure)
  selectDirectory: () => ipcRenderer.invoke('select-directory'),
  readFile: (filePath) => ipcRenderer.invoke('read-file', filePath),
  writeFile: (filePath, content) => ipcRenderer.invoke('write-file', filePath, content),

  // Network operations
  checkConnection: () => ipcRenderer.invoke('check-connection'),
  getNetworkInfo: () => ipcRenderer.invoke('get-network-info'),

  // Settings and preferences
  getSettings: () => ipcRenderer.invoke('get-settings'),
  saveSettings: (settings) => ipcRenderer.invoke('save-settings', settings),

  // Auto-updater
  checkForUpdates: () => ipcRenderer.invoke('check-for-updates'),
  onUpdateAvailable: (callback) => ipcRenderer.on('update-available', callback),
  onUpdateDownloaded: (callback) => ipcRenderer.on('update-downloaded', callback),

  // Notifications
  showNotification: (title, body) => ipcRenderer.invoke('show-notification', title, body),

  // Generic IPC
  sendMessage: (channel, data) => {
    // Whitelist of allowed channels
    const validChannels = ['message', 'blockchain-data', 'ai-data', 'platform-info'];
    if (validChannels.includes(channel)) {
      ipcRenderer.send(channel, data);
    }
  },

  onMessage: (channel, callback) => {
    const validChannels = ['message', 'blockchain-update', 'ai-update', 'platform-update'];
    if (validChannels.includes(channel)) {
      ipcRenderer.on(channel, callback);
    }
  },

  // Remove listeners
  removeAllListeners: (channel) => ipcRenderer.removeAllListeners(channel)
});
