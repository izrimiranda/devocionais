/**
 * Sistema de Instalação do PWA
 * Exibe botão "Instalar App" no menu apenas quando possível
 */

class PWAInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.installBtn = document.getElementById('install-app-btn');
        this.installContainer = document.getElementById('install-container');
        
        this.init();
    }

    init() {
        console.log('PWAInstaller: Inicializando...');
        console.log('PWAInstaller: installContainer encontrado:', this.installContainer !== null);
        console.log('PWAInstaller: installBtn encontrado:', this.installBtn !== null);
        
        // Verificar se já está instalado
        if (this.isAppInstalled()) {
            console.log('PWAInstaller: App já está instalado');
            this.hideInstallButton();
            return;
        }

        console.log('PWAInstaller: Aguardando evento beforeinstallprompt...');

        // Escutar evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWAInstaller: Evento beforeinstallprompt recebido!');
            
            // Prevenir mini-infobar automático do Chrome
            e.preventDefault();
            
            // Guardar evento para usar depois
            this.deferredPrompt = e;
            
            // Mostrar botão de instalação
            this.showInstallButton();
        });

        // Escutar quando app for instalado
        window.addEventListener('appinstalled', () => {
            console.log('PWA instalado com sucesso!');
            this.hideInstallButton();
            this.deferredPrompt = null;
            
            // Marcar como instalado
            localStorage.setItem('pwa_installed', 'true');
        });

        // Configurar clique no botão
        if (this.installBtn) {
            this.installBtn.addEventListener('click', () => this.installApp());
        }
    }

    showInstallButton() {
        console.log('PWAInstaller: Mostrando botão de instalação');
        if (this.installContainer) {
            this.installContainer.classList.add('show');
            console.log('PWAInstaller: Botão exibido!');
        } else {
            console.error('PWAInstaller: installContainer não encontrado!');
        }
    }

    hideInstallButton() {
        console.log('PWAInstaller: Escondendo botão de instalação');
        if (this.installContainer) {
            this.installContainer.classList.remove('show');
        }
    }

    async installApp() {
        if (!this.deferredPrompt) {
            console.log('Prompt de instalação não disponível');
            return;
        }

        // Mostrar prompt de instalação
        this.deferredPrompt.prompt();

        // Aguardar escolha do usuário
        const { outcome } = await this.deferredPrompt.userChoice;
        
        console.log(`Escolha do usuário: ${outcome}`);

        if (outcome === 'accepted') {
            this.showToast('✅ App sendo instalado...', 'success');
        } else {
            this.showToast('ℹ️ Instalação cancelada', 'info');
        }

        // Limpar prompt usado
        this.deferredPrompt = null;
    }

    isAppInstalled() {
        // Verificar se está em modo standalone (instalado)
        if (window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }

        // Verificar se foi marcado como instalado
        if (localStorage.getItem('pwa_installed') === 'true') {
            return true;
        }

        // iOS Safari
        if (window.navigator.standalone === true) {
            return true;
        }

        return false;
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `notification-toast notification-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PWAInstaller();
    });
} else {
    new PWAInstaller();
}
