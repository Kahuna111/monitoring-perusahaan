// ============================================
// MAIN JAVASCRIPT - Website Monitoring
// ============================================

document.addEventListener('DOMContentLoaded', () => {

    // === SIDEBAR TOGGLE ===
    const sidebar       = document.getElementById('sidebar');
    const mainContent   = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose  = document.getElementById('sidebarClose');
    const mobileOverlay = document.getElementById('mobileOverlay');

    const isMobile = () => window.innerWidth <= 768;

    const closeMobileMenu = () => {
        sidebar?.classList.remove('mobile-open');
        mobileOverlay?.classList.remove('active');
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            if (isMobile()) {
                // Ensure desktop classes are cleared on mobile
                sidebar?.classList.remove('collapsed');
                mainContent?.classList.remove('sidebar-collapsed');
                sidebar?.classList.toggle('mobile-open');
                mobileOverlay?.classList.toggle('active');
            } else {
                sidebar?.classList.toggle('collapsed');
                mainContent?.classList.toggle('sidebar-collapsed');
                // Save state
                const isCollapsed = sidebar?.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        });
    }

    // Restore sidebar state on load
    if (!isMobile() && sidebar) {
        const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (collapsed) {
            sidebar.classList.add('collapsed');
            mainContent?.classList.add('sidebar-collapsed');
        }
    }

    // Close sidebar on mobile overlay click
    mobileOverlay?.addEventListener('click', closeMobileMenu);

    // Close sidebar on close button click
    sidebarClose?.addEventListener('click', closeMobileMenu);

    // Handle screen resize/orientation changes to prevent menu class desync
    window.addEventListener('resize', () => {
        if (isMobile()) {
            sidebar?.classList.remove('collapsed');
            mainContent?.classList.remove('sidebar-collapsed');
        } else {
            closeMobileMenu();
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (collapsed) {
                sidebar?.classList.add('collapsed');
                mainContent?.classList.add('sidebar-collapsed');
            }
        }
    });

    // Handle back-forward cache (bfcache) pageshow event to reset mobile menu state
    window.addEventListener('pageshow', () => {
        closeMobileMenu();
    });

    // === DROPDOWN ===
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        trigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    });

    // === ALERT AUTO CLOSE ===
    document.querySelectorAll('.alert').forEach(alert => {
        // Auto dismiss after 5s
        setTimeout(() => dismissAlert(alert), 5000);

        const closeBtn = alert.querySelector('.alert-close');
        closeBtn?.addEventListener('click', () => dismissAlert(alert));
    });

    function dismissAlert(alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-8px)';
        alert.style.transition = 'all 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }

    // === MODAL ===
    // Open modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const modal = document.getElementById(modalId);
            modal?.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal
    function closeModal(modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) closeModal(modal);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // ESC key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(modal => closeModal(modal));
        }
    });

    // === DELETE CONFIRM ===
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // === FORM VALIDATION ===
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            if (!valid) {
                e.preventDefault();
                const firstInvalid = form.querySelector('.is-invalid');
                firstInvalid?.focus();
                showToast('Mohon lengkapi semua field yang wajib diisi.', 'error');
            }
        });
    });

    document.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('input', () => {
            if (field.value.trim()) field.classList.remove('is-invalid');
        });
    });

    // === RUPIAH FORMAT ===
    document.querySelectorAll('[data-rupiah]').forEach(input => {
        input.addEventListener('input', () => {
            let val = input.value.replace(/\D/g, '');
            input.value = val ? parseInt(val).toLocaleString('id-ID') : '';
        });
    });

    // === TOAST NOTIFICATION ===
    window.showToast = function(message, type = 'info', duration = 3500) {
        const container = getToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
            error:   `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`,
            warning: `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`,
            info:    `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
        };

        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-msg">${message}</span>
            <button class="toast-close" onclick="this.closest('.toast').remove()">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>`;

        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    function getToastContainer() {
        let c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            c.style.cssText = `
                position: fixed; bottom: 24px; right: 24px;
                z-index: 9999; display: flex; flex-direction: column; gap: 10px;
            `;
            document.body.appendChild(c);
        }
        return c;
    }

    // Toast styles (injected once)
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                display: flex; align-items: center; gap: 10px;
                padding: 13px 16px;
                border-radius: 11px;
                font-family: 'Inter', sans-serif;
                font-size: 13.5px; font-weight: 500;
                min-width: 280px; max-width: 380px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                opacity: 0; transform: translateX(20px);
                transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            }
            .toast.show { opacity: 1; transform: translateX(0); }
            .toast-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
            .toast-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
            .toast-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
            .toast-info    { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
            .toast-icon svg { width: 18px; height: 18px; flex-shrink: 0; }
            .toast-msg { flex: 1; }
            .toast-close { background: none; border: none; cursor: pointer; padding: 0; opacity: 0.6; display: flex; }
            .toast-close:hover { opacity: 1; }
            .toast-close svg { width: 15px; height: 15px; }
            .form-control.is-invalid { border-color: #ef4444 !important; }
            .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; }
            .mobile-overlay.active { display: block; }
        `;
        document.head.appendChild(style);
    }

    // === NUMBER FORMAT for display ===
    window.formatRupiah = function(num) {
        return 'Rp ' + parseInt(num || 0).toLocaleString('id-ID');
    };

    // === GLOBAL PASSWORD TOGGLE ===
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.toggle-password');
        if (!btn) return;
        const wrap = btn.closest('.password-input-wrap');
        if (!wrap) return;
        const input = wrap.querySelector('input');
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.innerHTML = isPassword
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    // === REGISTER SERVICE WORKER (PWA) ===
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker registered successfully with scope:', reg.scope))
                .catch(err => console.error('Service Worker registration failed:', err));
        });
    }

});
