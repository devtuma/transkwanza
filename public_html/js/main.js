/**
 * TRANSKWANZA - Main JavaScript
 * Utilidades globais e funções auxiliares
 */

// Configuração da API
const API_BASE_URL = '/api';

// ============================================
// NOTIFICATION UTILITY
// ============================================
class NotificationUtil {
    static show(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        // Estilos inline (se CSS não estiver pronto)
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '8px',
            color: '#fff',
            zIndex: '9999',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            minWidth: '250px',
            maxWidth: '400px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            animation: 'slideIn 0.3s ease',
            background: type === 'success' ? '#2ecc71' :
                       type === 'error' ? '#e74c3c' :
                       type === 'warning' ? '#f39c12' : '#3498db'
        });

        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = 'background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; margin-left: auto;';
        closeBtn.onclick = () => notification.remove();

        document.body.appendChild(notification);

        // Auto-remove após 4 segundos
        setTimeout(() => notification.remove(), 4000);
    }

    static success(message) {
        this.show(message, 'success');
    }

    static error(message) {
        this.show(message, 'error');
    }

    static warning(message) {
        this.show(message, 'warning');
    }

    static info(message) {
        this.show(message, 'info');
    }
}

// ============================================
// API HELPER
// ============================================
class API {
    static async request(endpoint, options = {}) {
        const token = localStorage.getItem('token');

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` })
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        // Merge headers
        if (options.headers) {
            finalOptions.headers = { ...defaultOptions.headers, ...options.headers };
        }

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, finalOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erro na requisição');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    static post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    }

    static put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    }

    static delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    static async uploadFile(endpoint, formData) {
        const token = localStorage.getItem('token');

        const response = await fetch(`${API_BASE_URL}${endpoint}`, {
            method: 'POST',
            headers: {
                ...(token && { 'Authorization': `Bearer ${token}` })
            },
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Erro no upload');
        }

        return data;
    }
}

// ============================================
// AUTHENTICATION HELPER
// ============================================
class Auth {
    static isAuthenticated() {
        return !!localStorage.getItem('token');
    }

    static getUser() {
        const userStr = localStorage.getItem('user');
        return userStr ? JSON.parse(userStr) : null;
    }

    static setAuth(token, user) {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
    }

    static logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login.html';
    }

    static async checkAuth() {
        if (!this.isAuthenticated()) {
            return false;
        }

        try {
            const response = await API.get('/auth.php?action=check');
            if (response.success) {
                this.setAuth(localStorage.getItem('token'), response.data.user);
                return true;
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            this.logout();
        }

        return false;
    }

    static requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    }

    static requireKYC() {
        const user = this.getUser();
        if (user && user.kyc_status !== 'approved') {
            window.location.href = '/kyc.html';
            return false;
        }
        return true;
    }

    static requireAdmin() {
        const user = this.getUser();
        if (!user || !user.is_admin) {
            NotificationUtil.error('Acesso restrito a administradores');
            window.location.href = '/dashboard.html';
            return false;
        }
        return true;
    }
}

// ============================================
// LOADING SPINNER
// ============================================
class Loading {
    static show() {
        if (document.getElementById('global-loading')) return;

        const loading = document.createElement('div');
        loading.id = 'global-loading';
        loading.innerHTML = `
            <div class="spinner"></div>
        `;

        Object.assign(loading.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            background: 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: '99999'
        });

        const spinner = loading.querySelector('.spinner');
        Object.assign(spinner.style, {
            width: '50px',
            height: '50px',
            border: '4px solid rgba(255, 255, 255, 0.3)',
            borderTop: '4px solid #fff',
            borderRadius: '50%',
            animation: 'spin 1s linear infinite'
        });

        // Add animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        document.body.appendChild(loading);
    }

    static hide() {
        const loading = document.getElementById('global-loading');
        if (loading) loading.remove();
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Format currency
function formatCurrency(amount, currency) {
    const symbols = {
        'BRL': 'R$',
        'AOA': 'Kz',
        'EUR': '€',
        'USD': '$',
        'CUP': '$',
        'RUB': '₽',
        'ZAR': 'R',
        'NAD': '$',
        'MZN': 'MT'
    };

    const symbol = symbols[currency] || currency;
    return `${symbol} ${parseFloat(amount).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Get initials for avatar
function getInitials(name) {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

// Validate email
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Export to global scope
window.NotificationUtil = NotificationUtil;
window.API = API;
window.Auth = Auth;
window.Loading = Loading;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.getInitials = getInitials;
window.validateEmail = validateEmail;
