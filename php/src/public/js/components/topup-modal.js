class TopupModal {
    constructor(modalId = 'unifiedTopupModal', options = {}) {
        this.modalId = modalId;
        this.modal = null;
        this.isProcessing = false;
        
        // Default options
        this.options = {
            csrfToken: options.csrfToken || '',
            apiEndpoint: options.apiEndpoint || '/api/user/topup',
            onSuccess: options.onSuccess || this.defaultSuccessHandler.bind(this),
            onError: options.onError || this.defaultErrorHandler.bind(this),
            minAmount: options.minAmount || 10000,
            quickAmounts: options.quickAmounts || [10000, 25000, 50000, 100000, 250000, 500000],
            ...options
        };
        
        this.init();
    }
    
    init() {
        this.modal = document.getElementById(this.modalId);
        if (!this.modal) {
            console.error(`Topup modal with ID '${this.modalId}' not found`);
            return;
        }
        
        this.setupEventListeners();
        this.createQuickAmountButtons();
    }
    
    setupEventListeners() {
        // Close modal on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        // Close modal on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('show')) {
                this.close();
            }
        });
        
        // Input validation
        const input = this.modal.querySelector('.topup-input');
        if (input) {
            input.addEventListener('input', (e) => {
                this.validateAmount(e.target.value);
            });
        }
    }
    
    createQuickAmountButtons() {
        const quickAmountsContainer = this.modal.querySelector('.topup-quick-amounts');
        if (!quickAmountsContainer) return;
        
        quickAmountsContainer.innerHTML = '';
        
        this.options.quickAmounts.forEach(amount => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'topup-quick-amount';
            button.textContent = this.formatCurrency(amount);
            button.onclick = () => this.setAmount(amount);
            quickAmountsContainer.appendChild(button);
        });
    }
    
    open() {
        if (this.modal) {
            this.modal.classList.add('show');
            this.clearMessages();
            document.body.classList.add('modal-open');
        }
    }
    
    close() {
        if (this.modal) {
            this.modal.classList.remove('show');
            this.clearForm();
            this.clearMessages();
            document.body.classList.remove('modal-open');
        }
    }
    
    setAmount(amount) {
        const input = this.modal.querySelector('.topup-input');
        if (input) {
            input.value = amount;
            this.validateAmount(amount);
        }
    }
    
    validateAmount(value) {
        const amount = parseInt(value);
        const confirmBtn = this.modal.querySelector('.topup-btn-confirm');
        
        if (isNaN(amount) || amount < this.options.minAmount) {
            if (confirmBtn) confirmBtn.disabled = true;
            return false;
        }
        
        if (confirmBtn) confirmBtn.disabled = false;
        return true;
    }
    
    async process() {
        if (this.isProcessing) return;
        
        const input = this.modal.querySelector('.topup-input');
        const amount = parseInt(input?.value || 0);
        
        if (!this.validateAmount(amount)) {
            this.showMessage(`Minimal top-up ${this.formatCurrency(this.options.minAmount)}`, 'error');
            return;
        }
        
        this.isProcessing = true;
        this.setProcessingState(true);
        
        try {
            const formData = new FormData();
            formData.append('amount', amount);
            if (this.options.csrfToken) {
                formData.append('_token', this.options.csrfToken);
            }
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.options.apiEndpoint, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            const data = await new Promise((resolve, reject) => {
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const contentType = xhr.getResponseHeader('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            reject(new Error(`Server tidak mengembalikan JSON response. Response: ${xhr.responseText.substring(0, 200)}...`));
                            return;
                        }
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch (e) {
                            reject(new Error('Invalid JSON response'));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(formData);
            });
            
            if (data.success) {
                await this.options.onSuccess(data, amount);
                this.close();
            } else {
                this.options.onError(data.message || 'Terjadi kesalahan saat top-up');
            }
            
        } catch (error) {
            console.error('Topup error:', error);
            this.options.onError(error.message || 'Gagal terhubung ke server');
        } finally {
            this.isProcessing = false;
            this.setProcessingState(false);
        }
    }
    
    setProcessingState(processing) {
        const confirmBtn = this.modal.querySelector('.topup-btn-confirm');
        const cancelBtn = this.modal.querySelector('.topup-btn-cancel');
        
        if (confirmBtn) {
            confirmBtn.disabled = processing;
            confirmBtn.textContent = processing ? 'Memproses...' : 'Top-up';
        }
        
        if (cancelBtn) {
            cancelBtn.disabled = processing;
        }
    }
    
    showMessage(message, type = 'error') {
        let messageEl = this.modal.querySelector('.topup-message');
        
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = 'topup-message';
            const body = this.modal.querySelector('.topup-modal-body');
            if (body) body.appendChild(messageEl);
        }
        
        messageEl.className = `topup-message ${type}`;
        messageEl.textContent = message;
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            if (messageEl && messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }
    
    clearMessages() {
        const messages = this.modal.querySelectorAll('.topup-message');
        messages.forEach(msg => msg.remove());
    }
    
    clearForm() {
        const input = this.modal.querySelector('.topup-input');
        if (input) input.value = '';
        
        const confirmBtn = this.modal.querySelector('.topup-btn-confirm');
        if (confirmBtn) confirmBtn.disabled = true;
    }
    
    formatCurrency(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }
    
    defaultSuccessHandler(data, amount) {
        // Update navbar balance if exists
        const balanceElement = document.getElementById('navbar-balance');
        if (balanceElement && data.data?.new_balance) {
            balanceElement.textContent = this.formatCurrency(data.data.new_balance);
        }
        
        // Show success toast
        this.showToast('Top-up berhasil! Saldo Anda telah bertambah.', 'success');
        
        // Reload page after short delay to ensure all UI is updated
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
    
    defaultErrorHandler(message) {
        this.showMessage(message, 'error');
    }
    
    showToast(message, type = 'success') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `topup-toast topup-toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('topup-toast-show');
        });
        
        // Remove after delay
        setTimeout(() => {
            toast.classList.add('topup-toast-hide');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
}

// Global functions for backward compatibility
window.TopupModal = TopupModal;

// Initialize default topup modal when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from various sources
    const getCsrfToken = () => {
        // Try global variable first
        if (typeof csrfToken !== 'undefined') return csrfToken;
        if (typeof CSRF_TOKEN !== 'undefined') return CSRF_TOKEN;
        
        // Try meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) return metaToken.getAttribute('content');
        
        // Try hidden input in forms
        const hiddenToken = document.querySelector('input[name="_token"]');
        if (hiddenToken) return hiddenToken.value;
        
        // Try session token
        if (typeof $_SESSION !== 'undefined' && $_SESSION.csrf_token) {
            return $_SESSION.csrf_token;
        }
        
        return '';
    };
    
    // Initialize topup modal if it exists
    const topupModal = document.getElementById('unifiedTopupModal');
    if (topupModal) {
        window.topupModalInstance = new TopupModal('unifiedTopupModal', {
            csrfToken: getCsrfToken()
        });
    }
});

// Global helper functions
window.openTopupModal = function() {
    if (window.topupModalInstance) {
        window.topupModalInstance.open();
    }
};

window.closeTopupModal = function() {
    if (window.topupModalInstance) {
        window.topupModalInstance.close();
    }
};

window.processTopup = function() {
    if (window.topupModalInstance) {
        window.topupModalInstance.process();
    }
};