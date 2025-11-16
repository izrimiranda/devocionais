/**
 * Main JavaScript
 * Devocionais - Pr. Luciano Miranda
 */

document.addEventListener('DOMContentLoaded', function() {
    // Lazy loading de imagens
    initLazyLoading();
    
    // Auto-hide flash messages
    autoHideFlashMessages();
    
    // Smooth scroll para âncoras
    initSmoothScroll();
});

/**
 * Lazy Loading de Imagens
 */
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[loading="lazy"]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

/**
 * Auto-hide Flash Messages
 */
function autoHideFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            message.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });
}

/**
 * Smooth Scroll
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Copiar para Clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Link copiado para a área de transferência!', 'success');
        }).catch(err => {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

/**
 * Fallback para copiar (navegadores antigos)
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('Link copiado!', 'success');
    } catch (err) {
        showToast('Erro ao copiar. Use Ctrl+C.', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Mostrar Toast Notification
 */
function showToast(message, type = 'info') {
    // Remove toasts anteriores
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getToastBg(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
        font-weight: 500;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastBg(type) {
    const colors = {
        success: '#48bb78',
        error: '#f56565',
        warning: '#ed8936',
        info: '#4299e1'
    };
    return colors[type] || colors.info;
}

// Adicionar animações CSS dinamicamente
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Busca instantânea (se houver campo de busca)
 */
function initInstantSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    let timeout = null;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        
        const query = this.value.trim();
        
        if (query.length < 3) return;
        
        timeout = setTimeout(() => {
            // Aqui você pode implementar busca AJAX se desejar
            console.log('Buscando:', query);
        }, 500);
    });
}

/**
 * Contador de visualizações (opcional - analytics)
 */
function trackView(devotionalId) {
    if (!devotionalId) return;
    
    // Evitar contar múltiplas vezes na mesma sessão
    const viewedKey = `viewed_${devotionalId}`;
    if (sessionStorage.getItem(viewedKey)) return;
    
    fetch('/api/track-view.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ devotional_id: devotionalId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            sessionStorage.setItem(viewedKey, 'true');
        }
    })
    .catch(err => console.error('Erro ao rastrear visualização:', err));
}

/**
 * Formatação de player de áudio customizado (opcional)
 */
function customizeAudioPlayer() {
    const audioElements = document.querySelectorAll('.audio-element');
    
    audioElements.forEach(audio => {
        audio.addEventListener('play', function() {
            // Pausar outros players
            audioElements.forEach(other => {
                if (other !== audio) {
                    other.pause();
                }
            });
        });
        
        // Adicionar evento de erro
        audio.addEventListener('error', function() {
            showToast('Erro ao carregar áudio. Tente novamente.', 'error');
        });
    });
}

// Inicializar player customizado quando carregar
document.addEventListener('DOMContentLoaded', customizeAudioPlayer);

/**
 * Compartilhamento via WhatsApp
 */
function shareOnWhatsApp(url, title) {
    const text = encodeURIComponent(`${title}\n\n${url}`);
    const whatsappUrl = `https://wa.me/?text=${text}`;
    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
}

/**
 * Validação de formulários
 */
function validateForm(formElement) {
    let isValid = true;
    const requiredFields = formElement.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            
            // Remover classe de erro ao digitar
            field.addEventListener('input', function() {
                this.classList.remove('error');
            }, { once: true });
        }
    });
    
    if (!isValid) {
        showToast('Por favor, preencha todos os campos obrigatórios.', 'error');
    }
    
    return isValid;
}

/**
 * Loading state para botões
 */
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.textContent = 'Carregando...';
        button.disabled = true;
        button.classList.add('loading');
    } else {
        button.textContent = button.dataset.originalText || button.textContent;
        button.disabled = false;
        button.classList.remove('loading');
    }
}

// Expor funções globalmente para uso inline
window.copyToClipboard = copyToClipboard;
window.shareOnWhatsApp = shareOnWhatsApp;
window.showToast = showToast;
window.validateForm = validateForm;
window.setButtonLoading = setButtonLoading;
window.trackView = trackView;
