/**
 * Service Worker - Push Notifications
 * Gerencia notificações push e cache offline
 */

const CACHE_NAME = 'devocional-v1';
const urlsToCache = [
    '/',
    '/index.php',
    '/assets/css/main.css',
    '/assets/js/main.js',
    '/assets/images/icon-192.png'
];

// Instalação do Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});

// Ativação do Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Interceptar requisições (offline support)
self.addEventListener('fetch', (event) => {
    // Ignorar requisições externas (Google Analytics, etc)
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }
    
    // Ignorar APIs (sempre buscar do servidor)
    if (event.request.url.includes('/api/')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                return response || fetch(event.request).catch(() => {
                    // Se offline e não está no cache, retornar página offline
                    return caches.match('/');
                });
            })
    );
});

// Receber Push Notification
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    
    const options = {
        body: data.body || 'Novo devocional disponível!',
        icon: data.icon || '/assets/images/icon-192.png',
        badge: '/assets/images/badge.png',
        image: data.image || '',
        vibrate: [200, 100, 200],
        tag: data.tag || 'novo-devocional',
        requireInteraction: true,
        actions: [
            {
                action: 'open',
                title: 'Ler Agora',
                icon: '/assets/images/read-icon.png'
            },
            {
                action: 'close',
                title: 'Depois',
                icon: '/assets/images/close-icon.png'
            }
        ],
        data: {
            url: data.url || '/',
            dateOfArrival: Date.now()
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Novo Devocional', options)
    );
});

// Clique na notificação
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    } else if (event.action === 'close') {
        // Apenas fechar
        return;
    } else {
        // Clique no corpo da notificação
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});
