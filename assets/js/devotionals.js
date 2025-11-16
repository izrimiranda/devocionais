/**
 * Likes System
 * Sistema de curtidas para devocionais
 */

document.addEventListener('DOMContentLoaded', function() {
    const likeBtn = document.querySelector('.btn-like');
    
    if (!likeBtn) return;
    
    const devotionalId = likeBtn.dataset.devotionalId;
    const heartIcon = likeBtn.querySelector('.heart-icon');
    const likeCount = likeBtn.querySelector('.like-count');
    
    // Carregar curtidas ao iniciar
    loadLikes();
    
    // Event listener para curtir/descurtir
    likeBtn.addEventListener('click', toggleLike);
    
    async function loadLikes() {
        try {
            const response = await fetch(`${SITE_URL}/api/get-likes.php?devotional_id=${devotionalId}`);
            const data = await response.json();
            
            if (data.success) {
                likeCount.textContent = data.total_likes;
                
                if (data.user_liked) {
                    likeBtn.classList.add('liked');
                    heartIcon.textContent = '♥';
                } else {
                    likeBtn.classList.remove('liked');
                    heartIcon.textContent = '♡';
                }
            }
        } catch (error) {
            console.error('Erro ao carregar curtidas:', error);
        }
    }
    
    async function toggleLike(e) {
        e.preventDefault();
        
        // Animação otimista
        const wasLiked = likeBtn.classList.contains('liked');
        likeBtn.classList.toggle('liked');
        heartIcon.textContent = wasLiked ? '♡' : '♥';
        
        const currentCount = parseInt(likeCount.textContent) || 0;
        likeCount.textContent = wasLiked ? currentCount - 1 : currentCount + 1;
        
        try {
            const response = await fetch(`${SITE_URL}/api/like.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    devotional_id: devotionalId,
                    action: 'toggle'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Atualizar com dados reais do servidor
                likeCount.textContent = data.total_likes;
                
                if (data.liked) {
                    likeBtn.classList.add('liked');
                    heartIcon.textContent = '♥';
                } else {
                    likeBtn.classList.remove('liked');
                    heartIcon.textContent = '♡';
                }
            } else {
                // Reverter em caso de erro
                likeBtn.classList.toggle('liked');
                heartIcon.textContent = wasLiked ? '♥' : '♡';
                likeCount.textContent = currentCount;
                console.error('Erro ao processar curtida:', data.message);
            }
        } catch (error) {
            // Reverter em caso de erro
            likeBtn.classList.toggle('liked');
            heartIcon.textContent = wasLiked ? '♥' : '♡';
            likeCount.textContent = currentCount;
            console.error('Erro na requisição:', error);
        }
    }
});

/**
 * Copy to Clipboard
 * Copiar texto para área de transferência
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showNotification('Link copiado!', 'success');
            })
            .catch(err => {
                fallbackCopy(text);
            });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Link copiado!', 'success');
    } catch (err) {
        console.error('Erro ao copiar:', err);
        showNotification('Erro ao copiar link', 'error');
    }
    
    document.body.removeChild(textarea);
}

/**
 * Show Notification
 * Exibir notificação toast
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
