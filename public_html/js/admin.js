/**
 * TRANSKWANZA - Admin Panel
 * Painel administrativo
 */

// Verificar autenticação e permissão admin
if (!Auth.requireAuth()) {
    window.location.href = '/login.html';
}

if (!Auth.requireAdmin()) {
    window.location.href = '/dashboard.html';
}

const admin = Auth.getUser();
document.getElementById('adminEmail').textContent = admin.email;

// ============================================
// CARREGAR ESTATÍSTICAS
// ============================================
async function loadStats() {
    try {
        const response = await API.get('/admin.php?action=stats');

        if (response.success) {
            const { users, transactions, fraud } = response.data;

            document.getElementById('totalUsers').textContent = users.total_users || 0;
            document.getElementById('verifiedUsers').textContent = users.verified_users || 0;
            document.getElementById('pendingKYC').textContent = users.kyc_pending || 0;
            document.getElementById('pendingTransactions').textContent = transactions.pending_approval || 0;
            document.getElementById('fraudAlerts').textContent = fraud.unresolved_alerts || 0;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

// ============================================
// TABS
// ============================================
const tabs = document.querySelectorAll('.tab');
const tabContents = document.querySelectorAll('.tab-content');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.dataset.tab;

        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(tc => tc.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(`tab-${tabName}`).classList.add('active');

        if (tabName === 'kyc') loadKYC();
        if (tabName === 'transactions') loadTransactions();
        if (tabName === 'currencies') loadCurrencies();
        if (tabName === 'fraud') loadFraud();
    });
});

// ============================================
// CARREGAR KYC PENDENTES
// ============================================
async function loadKYC() {
    const list = document.getElementById('kycList');
    list.innerHTML = '<p style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const response = await API.get('/admin.php?action=pending_kyc');

        if (response.success && response.data.users.length > 0) {
            list.innerHTML = response.data.users.map(u => `
                <div class="proposal-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; margin-bottom: 1rem;">
                    <h4 style="margin-bottom: 1rem;">${u.name}</h4>
                    <div style="color: rgba(255,255,255,0.8); margin-bottom: 1rem;">
                        <p><i class="fas fa-envelope"></i> ${u.email}</p>
                        <p><i class="fas fa-flag"></i> ${u.country}</p>
                        <p><i class="fas fa-id-card"></i> ${u.document_type.toUpperCase()}: ${u.document_number}</p>
                        <p><i class="fas fa-calendar"></i> Enviado em: ${formatDate(u.created_at)}</p>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        ${u.document_front ? `<a href="/${u.document_front}" target="_blank" class="btn-secondary" style="text-align: center;">
                            <i class="fas fa-image"></i> Ver Frente
                        </a>` : ''}
                        ${u.document_selfie ? `<a href="/${u.document_selfie}" target="_blank" class="btn-secondary" style="text-align: center;">
                            <i class="fas fa-camera"></i> Ver Selfie
                        </a>` : ''}
                        ${u.document_back ? `<a href="/${u.document_back}" target="_blank" class="btn-secondary" style="text-align: center;">
                            <i class="fas fa-image"></i> Ver Verso
                        </a>` : ''}
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <button onclick="approveKYC(${u.id})" class="btn-primary">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                        <button onclick="rejectKYC(${u.id})" class="btn-secondary" style="background: #e74c3c;">
                            <i class="fas fa-times"></i> Rejeitar
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>Nenhum KYC pendente</p></div>';
        }
    } catch (error) {
        list.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

window.approveKYC = async function(userId) {
    if (!confirm('Aprovar KYC deste usuário?')) return;

    try {
        Loading.show();
        await API.post('/admin.php?action=approve_kyc', { user_id: userId });
        NotificationUtil.success('KYC aprovado!');
        loadKYC();
        loadStats();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

window.rejectKYC = async function(userId) {
    const reason = prompt('Motivo da rejeição:');
    if (!reason) return;

    try {
        Loading.show();
        await API.post('/admin.php?action=reject_kyc', { user_id: userId, reason });
        NotificationUtil.success('KYC rejeitado');
        loadKYC();
        loadStats();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// CARREGAR TRANSAÇÕES PENDENTES
// ============================================
async function loadTransactions() {
    const list = document.getElementById('transactionsList');
    list.innerHTML = '<p style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const response = await API.get('/admin.php?action=pending_transactions');

        if (response.success && response.data.transactions.length > 0) {
            list.innerHTML = response.data.transactions.map(t => `
                <div class="proposal-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; margin-bottom: 1rem;">
                    <h4>Transação #${t.id}</h4>
                    <div style="color: rgba(255,255,255,0.8); margin: 1rem 0;">
                        <p><i class="fas fa-user"></i> <strong>De:</strong> ${t.sender_name} (${t.sender_email})</p>
                        <p><i class="fas fa-user"></i> <strong>Para:</strong> ${t.receiver_name} (${t.receiver_email})</p>
                        <p><i class="fas fa-exchange-alt"></i> ${formatCurrency(t.sender_amount, t.sender_currency)} → ${formatCurrency(t.receiver_amount, t.receiver_currency)}</p>
                        <p><i class="fas fa-percentage"></i> Taxa: ${formatCurrency(t.fee_amount, t.sender_currency)}</p>
                        <p><i class="fas fa-calendar"></i> ${formatDate(t.created_at)}</p>
                    </div>
                    ${t.payment_proof ? `<a href="/${t.payment_proof}" target="_blank" class="btn-secondary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-receipt"></i> Ver Comprovante
                    </a>` : ''}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <button onclick="approveTransaction(${t.id})" class="btn-primary">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                        <button onclick="rejectTransaction(${t.id})" class="btn-secondary" style="background: #e74c3c;">
                            <i class="fas fa-times"></i> Rejeitar
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>Nenhuma transação pendente</p></div>';
        }
    } catch (error) {
        list.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

window.approveTransaction = async function(id) {
    if (!confirm('Aprovar esta transação?')) return;

    try {
        Loading.show();
        await API.post('/admin.php?action=approve_transaction', { transaction_id: id });
        NotificationUtil.success('Transação aprovada!');
        loadTransactions();
        loadStats();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

window.rejectTransaction = async function(id) {
    const reason = prompt('Motivo da rejeição:');
    if (!reason) return;

    try {
        Loading.show();
        await API.post('/admin.php?action=reject_transaction', { transaction_id: id, reason });
        NotificationUtil.success('Transação rejeitada');
        loadTransactions();
        loadStats();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// CARREGAR MOEDAS
// ============================================
async function loadCurrencies() {
    const list = document.getElementById('currenciesList');
    list.innerHTML = '<p style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const response = await API.get('/admin.php?action=currencies');

        if (response.success) {
            list.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
                    ${response.data.currencies.map(c => `
                        <div class="proposal-card" style="background: rgba(255,255,255,0.05); padding: 1.5rem; text-align: center;">
                            <h3>${c.symbol} ${c.code}</h3>
                            <p style="color: rgba(255,255,255,0.8); margin: 0.5rem 0;">${c.name}</p>
                            <p style="margin: 1rem 0;">
                                <span class="badge badge-${c.is_enabled ? 'success' : 'error'}">
                                    ${c.is_enabled ? 'Ativa' : 'Desativada'}
                                </span>
                            </p>
                            <button onclick="toggleCurrency('${c.code}', ${!c.is_enabled})" class="btn-${c.is_enabled ? 'secondary' : 'primary'}" style="width: 100%; ${c.is_enabled ? 'background: #e74c3c;' : ''}">
                                <i class="fas fa-power-off"></i> ${c.is_enabled ? 'Desativar' : 'Ativar'}
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    } catch (error) {
        list.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

window.toggleCurrency = async function(code, enable) {
    try {
        Loading.show();
        await API.post('/admin.php?action=toggle_currency', { code, enable });
        NotificationUtil.success(`Moeda ${enable ? 'ativada' : 'desativada'}`);
        loadCurrencies();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// CARREGAR ALERTAS DE FRAUDE
// ============================================
async function loadFraud() {
    const list = document.getElementById('fraudList');
    list.innerHTML = '<p style="text-align: center;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const response = await API.get('/admin.php?action=fraud_alerts');

        if (response.success && response.data.alerts.length > 0) {
            list.innerHTML = response.data.alerts.map(a => `
                <div class="proposal-card" style="background: rgba(231, 76, 60, 0.1); padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid #e74c3c;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h4>${a.user_name}</h4>
                            <p style="color: rgba(255,255,255,0.7);">${a.user_email}</p>
                        </div>
                        <span class="badge badge-${a.risk_level === 'critical' ? 'error' : a.risk_level === 'high' ? 'warning' : 'info'}">
                            ${a.risk_level.toUpperCase()}
                        </span>
                    </div>
                    <div style="color: rgba(255,255,255,0.9); margin: 1rem 0;">
                        <p><i class="fas fa-exclamation-triangle"></i> <strong>Tipo:</strong> ${a.check_type.replace('_', ' ')}</p>
                        <p><i class="fas fa-info-circle"></i> ${a.details}</p>
                        <p><i class="fas fa-chart-line"></i> <strong>Fraud Score:</strong> ${a.fraud_score}/100</p>
                        <p><i class="fas fa-calendar"></i> ${formatDate(a.created_at)}</p>
                    </div>
                    <button onclick="resolveFraud(${a.id})" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-check"></i> Marcar como Resolvido
                    </button>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<div class="empty-state"><i class="fas fa-shield-alt"></i><p>Nenhum alerta de fraude</p></div>';
        }
    } catch (error) {
        list.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

window.resolveFraud = async function(id) {
    if (!confirm('Marcar este alerta como resolvido?')) return;

    try {
        Loading.show();
        await API.post('/admin.php?action=resolve_fraud', { fraud_id: id });
        NotificationUtil.success('Alerta resolvido');
        loadFraud();
        loadStats();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// LOGOUT
// ============================================
document.getElementById('logoutBtn').addEventListener('click', () => {
    if (confirm('Deseja sair?')) {
        Auth.logout();
    }
});

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadKYC();

    // Atualizar stats a cada 30 segundos
    setInterval(loadStats, 30000);
});
