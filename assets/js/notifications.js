/**
 * Sistema de Notificações Push - Automático
 * Solicita permissão automaticamente ao acessar o site
 */

class NotificationManager {
    constructor() {
        this.vapidPublicKey = 'BOYXEbV0gz0T4x0JM56sqEfsnr-_YDPsTvVdgz7syHHW3PgpkfD2AsJ85xa5UCuG4llS7BQm5_NLXhODRm4zdaY';
        this.init();
    }

    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.log('Push notifications não suportadas neste navegador');
            return;
        }

        try {
            // Registrar Service Worker
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registrado:', registration);

            // SOLICITAR NOTIFICAÇÕES AUTOMATICAMENTE
            await this.requestNotificationPermissionAuto();
        } catch (error) {
            console.error('Erro ao inicializar notificações:', error);
        }
    }

    async requestNotificationPermissionAuto() {
        // Verificar se já pediu permissão antes
        const hasAskedBefore = localStorage.getItem('notification_asked');
        
        // Se já perguntou ou já tem permissão/negada, não mostrar popup
        if (hasAskedBefore || Notification.permission !== 'default') {
            return;
        }

        // Aguardar 2 segundos após carregar a página
        setTimeout(() => {
            this.showNotificationPopup();
        }, 2000);
    }

    showNotificationPopup() {
        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'notification-popup-overlay';
        
        // Criar popup
        const popup = document.createElement('div');
        popup.className = 'notification-popup';
        popup.innerHTML = `
            <div class="notification-popup-header">
                <div class="notification-popup-icon">🔔</div>
                <h3>Receba Novos Devocionais</h3>
            </div>
            <div class="notification-popup-body">
                <p>Ative as notificações e seja avisado quando um novo devocional for publicado!</p>
                <ul class="notification-popup-benefits">
                    <li>📱 Alertas instantâneos</li>
                    <li>✨ Nunca perca um devocional</li>
                    <li>🙏 Fortaleça sua fé diariamente</li>
                </ul>
            </div>
            <div class="notification-popup-actions">
                <button class="notification-popup-btn notification-popup-btn-primary" data-action="allow">
                    ✅ Ativar Notificações
                </button>
                <button class="notification-popup-btn notification-popup-btn-secondary" data-action="later">
                    ⏰ Perguntar Depois
                </button>
                <button class="notification-popup-btn notification-popup-btn-text" data-action="never">
                    Não, obrigado
                </button>
            </div>
        `;
        
        overlay.appendChild(popup);
        document.body.appendChild(overlay);
        
        // Animar entrada
        setTimeout(() => {
            overlay.classList.add('show');
            popup.classList.add('show');
        }, 50);
        
        // Event listeners para botões
        popup.querySelector('[data-action="allow"]').addEventListener('click', async () => {
            this.closePopup(overlay);
            await this.requestPermissionAndSubscribe();
        });
        
        popup.querySelector('[data-action="later"]').addEventListener('click', () => {
            this.closePopup(overlay);
            // Não marcar como perguntado - vai perguntar na próxima visita
        });
        
        popup.querySelector('[data-action="never"]').addEventListener('click', () => {
            this.closePopup(overlay);
            localStorage.setItem('notification_asked', 'true');
        });
        
        // Fechar ao clicar no overlay
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.closePopup(overlay);
            }
        });
    }

    closePopup(overlay) {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 300);
    }

    async requestPermissionAndSubscribe() {
        try {
            const permission = await Notification.requestPermission();
            
            // Marcar que já perguntou
            localStorage.setItem('notification_asked', 'true');
            
            if (permission === 'granted') {
                // Se concedeu permissão, inscrever automaticamente
                await this.subscribe();
                this.showMessage('✅ Notificações ativadas! Você receberá alertas de novos devocionais.', 'success');
            } else if (permission === 'denied') {
                this.showMessage('❌ Permissão negada. Você pode ativar nas configurações do navegador.', 'error');
            }
        } catch (error) {
            console.log('Erro ao solicitar permissão:', error);
            this.showMessage('❌ Erro ao solicitar permissão. Tente novamente.', 'error');
        }
    }

    async subscribe() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Converter VAPID key
            const applicationServerKey = this.urlBase64ToUint8Array(this.vapidPublicKey);
            
            // Criar inscrição
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // Enviar para servidor
            await this.sendSubscriptionToServer(subscription, 'subscribe');
            
            console.log('Inscrito com sucesso:', subscription);
        } catch (error) {
            console.error('Erro ao inscrever:', error);
            this.showMessage('❌ Erro ao ativar notificações. Tente novamente.', 'error');
        }
    }

    async sendSubscriptionToServer(subscription, action) {
        const response = await fetch('/api/subscribe-push.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                subscription: subscription
            })
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao salvar inscrição');
        }

        return data;
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    showMessage(message, type = 'info') {
        // Criar toast
        const toast = document.createElement('div');
        toast.className = `notification-toast notification-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Mostrar
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remover após 5 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new NotificationManager();
    });
} else {
    new NotificationManager();
}
