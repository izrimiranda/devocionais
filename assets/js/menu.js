/**
 * Mobile Menu Toggle
 * Handles hamburger menu functionality for responsive navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Toggle aria-expanded attribute
            this.setAttribute('aria-expanded', !isExpanded);
            
            // Toggle menu visibility
            if (!isExpanded) {
                navMenu.style.display = 'flex';
            } else {
                navMenu.style.display = 'none';
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.main-nav')) {
                navToggle.setAttribute('aria-expanded', 'false');
                if (window.innerWidth <= 768) {
                    navMenu.style.display = 'none';
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navMenu.style.display = 'flex';
                navToggle.setAttribute('aria-expanded', 'false');
            } else {
                const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
                navMenu.style.display = isExpanded ? 'flex' : 'none';
            }
        });
    }
});
