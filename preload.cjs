const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktop', {
    runtimeInfo: () => ipcRenderer.invoke('runtime-info'),
    isElectron: true,
});
