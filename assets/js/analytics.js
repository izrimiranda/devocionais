/**
 * Analytics Tracking
 * Rastreia visitas automaticamente
 */

(function() {
    'use strict';
    
    // Detectar tipo de página
    function detectPageType() {
        const path = window.location.pathname;
        
        if (path === '/' || path.includes('/index.php')) {
            return 'home';
        }
        if (path.includes('/devocionais/')) {
            return 'devotional';
        }
        if (path.includes('/search')) {
            return 'search';
        }
        return 'other';
    }
    
    // Extrair ID do devocional da URL
    function getDevotionalId() {
        // Tentar pegar do meta tag
        const meta = document.querySelector('meta[name="devotional-id"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        return null;
    }
    
    // Enviar dados de tracking
    async function trackVisit() {
        try {
            const pageType = detectPageType();
            const devotionalId = getDevotionalId();
            const pageUrl = window.location.pathname + window.location.search;
            
            const data = {
                page_type: pageType,
                devotional_id: devotionalId,
                page_url: pageUrl
            };
            
            await fetch(`${SITE_URL}/api/track.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
        } catch (error) {
            console.error('Erro ao registrar visita:', error);
        }
    }
    
    // Executar tracking ao carregar a página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackVisit);
    } else {
        trackVisit();
    }
    
})();
