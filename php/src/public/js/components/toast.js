class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.toastId = 0;
        this.init();
    }

    init() {
        // Create toast container
        this.container = document.getElementById('toastContainer');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    }

    // show a toast notification
    show({ type = 'info', title = '', message = '', duration = 5000, closable = true } = {}) {
        const toastId = `toast_${++this.toastId}`;
        
        // Create toast element
        const toast = this.createToastElement(toastId, type, title, message, closable);
        
        // Add to container
        this.container.appendChild(toast);
        
        // Store toast reference
        this.toasts.set(toastId, {
            element: toast,
            timer: null
        });

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto-hide if duration is set
        if (duration > 0) {
            this.setAutoHide(toastId, duration);
        }

        return toastId;
    }

    // Create toast HTML element
    createToastElement(toastId, type, title, message, closable) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('data-toast-id', toastId);

        let progressBar = '';
        if (type !== 'error') {
            progressBar = '<div class="toast-progress"></div>';
        }

        toast.innerHTML = `
            <div class="toast-icon"></div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${this.escapeHtml(title)}</div>` : ''}
                ${message ? `<div class="toast-message">${this.escapeHtml(message)}</div>` : ''}
            </div>
            ${closable ? '<button class="toast-close" type="button">&times;</button>' : ''}
            ${progressBar}
        `;

        // Add close functionality
        if (closable) {
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => {
                this.hide(toastId);
            });
        }

        return toast;
    }

    // Hide a specific toast
    hide(toastId) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;

        const { element, timer } = toastData;

        // Clear auto-hide timer
        if (timer) {
            clearTimeout(timer);
        }

        // Start hide animation
        element.classList.remove('show');
        element.classList.add('hide');

        // Remove from DOM after animation
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
            this.toasts.delete(toastId);
        }, 300);
    }

    // Set auto-hide timer and progress bar animation
    setAutoHide(toastId, duration) {
        const toastData = this.toasts.get(toastId);
        if (!toastData) return;

        const { element } = toastData;
        const progressBar = element.querySelector('.toast-progress');

        if (progressBar) {
            // Animate progress bar
            progressBar.style.width = '100%';
            progressBar.style.transitionDuration = `${duration}ms`;
            
            setTimeout(() => {
                if (progressBar) {
                    progressBar.style.width = '0%';
                }
            }, 10);
        }

        // Set auto-hide timer
        toastData.timer = setTimeout(() => {
            this.hide(toastId);
        }, duration);
    }

    // Hide all toasts
    hideAll() {
        const toastIds = Array.from(this.toasts.keys());
        toastIds.forEach(id => this.hide(id));
    }

    // Show success toast
    success(title, message, options = {}) {
        return this.show({
            type: 'success',
            title,
            message,
            duration: 4000,
            ...options
        });
    }

    error(title, message, options = {}) {
        return this.show({
            type: 'error',
            title,
            message,
            duration: 0,
            ...options
        });
    }

    warning(title, message, options = {}) {
        return this.show({
            type: 'warning',
            title,
            message,
            duration: 6000,
            ...options
        });
    }

    info(title, message, options = {}) {
        return this.show({
            type: 'info',
            title,
            message,
            duration: 5000,
            ...options
        });
    }

    // Utility to escape HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global instance
const Toast = new ToastManager();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = Toast;
}

// Make available globally
window.Toast = Toast;