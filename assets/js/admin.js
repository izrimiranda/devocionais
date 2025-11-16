/**
 * Admin JavaScript
 */

// Copiar URL do devocional
function copyDevotionalUrl(slug) {
    const url = window.location.origin + '/devocionais/' + slug + '.php';
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('URL copiada: ' + url, 'success');
        }).catch(err => {
            prompt('Copie a URL:', url);
        });
    } else {
        prompt('Copie a URL:', url);
    }
}

// Copiar mensagem WhatsApp para divulgação
function copyWhatsAppMessage(title, date, slug) {
    const url = window.location.origin + '/devocionais/' + slug + '.php';
    
    const message = `Olá, como vai?

Acabei de postar mais um *devocional*! Acesse agora mesmo e seja edificado com a mensagem.

*Tema do Devocional:* ${title}
*Data*: ${date}
*Acesse*: ${url}

_Cordialmente_,
_Pr. Luciano Miranda_
*Pastor Batista*`;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(message).then(() => {
            showToast('✅ Mensagem WhatsApp copiada!', 'success');
        }).catch(err => {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = message;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('✅ Mensagem WhatsApp copiada!', 'success');
        });
    } else {
        // Fallback para navegadores antigos
        const textarea = document.createElement('textarea');
        textarea.value = message;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('✅ Mensagem WhatsApp copiada!', 'success');
    }
}

// Toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#48bb78' : '#f56565'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Preview de imagem
document.addEventListener('DOMContentLoaded', function() {
    
    // =======================
    // Mobile Sidebar Toggle
    // =======================
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && sidebar) {
        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        
        // Toggle menu
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            this.setAttribute('aria-expanded', !isExpanded);
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        // Fechar ao clicar no overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            sidebarToggle.setAttribute('aria-expanded', 'false');
        });
        
        // Fechar ao clicar em um link (mobile)
        if (window.innerWidth <= 768) {
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    sidebarToggle.setAttribute('aria-expanded', 'false');
                });
            });
        }
        
        // Ajustar ao redimensionar janela
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                sidebarToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // =======================
    // Nav Section Toggle
    // =======================
    const navSections = document.querySelectorAll('.nav-section-header');
    
    navSections.forEach(header => {
        header.addEventListener('click', function() {
            const section = this.closest('.nav-section');
            section.classList.toggle('open');
        });
    });
    
    // Abrir seção automaticamente se tiver link ativo dentro
    document.querySelectorAll('.nav-section').forEach(section => {
        const currentPage = window.location.pathname.split('/').pop();
        if (section.querySelector('.nav-link-sub[href="' + currentPage + '"]')) {
            section.classList.add('open');
        }
    });
    
    // =======================
    // File Upload Enhancement
    // =======================
    enhanceFileInputs();
    
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'image-preview';
                        preview.style.cssText = 'max-width: 300px; margin-top: 10px; border-radius: 8px;';
                        imageInput.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Adicionar animação
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

function enhanceFileInputs() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        // Skip if already enhanced
        if (input.parentNode.classList.contains('file-input-wrapper')) return;
        
        const wrapper = document.createElement('div');
        wrapper.className = 'file-input-wrapper';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        // Criar label customizado
        const label = document.createElement('label');
        label.className = 'file-input-label';
        label.htmlFor = input.id;
        
        const icon = document.createElement('div');
        const isImage = input.accept && input.accept.includes('image');
        icon.className = isImage ? 'file-input-icon file-input-icon-image' : 'file-input-icon file-input-icon-audio';
        
        const text = document.createElement('span');
        text.className = 'file-input-text';
        text.textContent = input.accept && input.accept.includes('image') 
            ? 'Clique ou arraste uma imagem' 
            : 'Clique ou arraste um áudio';
        
        const fileName = document.createElement('span');
        fileName.className = 'file-input-filename';
        fileName.style.display = 'none';
        
        label.appendChild(icon);
        label.appendChild(text);
        label.appendChild(fileName);
        wrapper.appendChild(label);
        
        // Preview container
        const preview = document.createElement('div');
        preview.className = 'file-input-preview';
        preview.style.display = 'none';
        wrapper.appendChild(preview);
        
        // Change event
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileName.style.display = 'block';
                text.textContent = 'Arquivo selecionado:';
                
                // Preview
                if (input.accept && input.accept.includes('image')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (input.accept && input.accept.includes('audio')) {
                    preview.innerHTML = `
                        <audio controls style="width: 100%;">
                            <source src="${URL.createObjectURL(file)}" type="${file.type}">
                        </audio>
                    `;
                    preview.style.display = 'block';
                }
            }
        });
        
        // Drag and drop
        label.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });
        
        label.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });
        
        label.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}
