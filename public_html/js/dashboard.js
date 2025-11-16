/**
 * TRANSKWANZA - Dashboard
 * Painel do usuário com propostas e transações
 */

// Verificar autenticação e KYC
if (!Auth.requireAuth()) {
    window.location.href = '/login.html';
}

const user = Auth.getUser();

// Verificar se KYC foi aprovado
if (user.kyc_status !== 'approved') {
    NotificationUtil.warning('Complete sua verificação KYC primeiro');
    setTimeout(() => {
        window.location.href = '/kyc.html';
    }, 2000);
}

// ============================================
// POPULAR DADOS DO USUÁRIO
// ============================================
document.getElementById('userName').textContent = user.name;
document.getElementById('userEmail').textContent = user.email;
document.getElementById('userAvatar').textContent = getInitials(user.name);
document.getElementById('totalTransactions').textContent = user.total_transactions || 0;
document.getElementById('userReputation').textContent = (user.reputation || 0).toFixed(1);

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

        // Carregar dados ao abrir tab
        if (tabName === 'proposals') loadProposals();
        if (tabName === 'my-proposals') loadMyProposals();
        if (tabName === 'transactions') loadTransactions();
    });
});

// ============================================
// POPULAR SELECTS DE MOEDA
// ============================================
function populateCurrencySelects() {
    const currencies = converter.getCurrencies();
    const selects = ['filterFrom', 'filterTo', 'currencyFrom', 'currencyTo'];

    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (!select) return;

        currencies.forEach(code => {
            const name = converter.getCurrencyName(code);
            const symbol = converter.getCurrencySymbol(code);
            select.innerHTML += `<option value="${code}">${code} - ${name} (${symbol})</option>`;
        });
    });
}

// ============================================
// CARREGAR PROPOSTAS DISPONÍVEIS
// ============================================
async function loadProposals() {
    const proposalsList = document.getElementById('proposalsList');
    proposalsList.innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.6);"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const from = document.getElementById('filterFrom').value;
        const to = document.getElementById('filterTo').value;

        let url = '/proposals.php?action=search';
        if (from) url += `&currency_from=${from}`;
        if (to) url += `&currency_to=${to}`;

        const response = await API.get(url);

        if (response.success && response.data.proposals.length > 0) {
            proposalsList.innerHTML = response.data.proposals.map(p => `
                <div class="proposal-card">
                    <div class="proposal-header">
                        <div>
                            <span class="currency-pair">${p.currency_from} → ${p.currency_to}</span>
                            <span class="badge badge-${p.type === 'sell' ? 'success' : 'info'}">${p.type === 'sell' ? 'Venda' : 'Compra'}</span>
                        </div>
                        <div>
                            <span class="proposal-amount">${formatCurrency(p.amount_from, p.currency_from)}</span>
                        </div>
                    </div>
                    <div style="margin: 1rem 0; color: rgba(255,255,255,0.8);">
                        <p style="margin: 0.5rem 0;"><i class="fas fa-chart-line"></i> Taxa: 1 ${p.currency_from} = ${p.exchange_rate.toFixed(6)} ${p.currency_to}</p>
                        <p style="margin: 0.5rem 0;"><i class="fas fa-arrow-right"></i> Recebe: ${formatCurrency(p.amount_to, p.currency_to)}</p>
                        <p style="margin: 0.5rem 0;"><i class="fas fa-credit-card"></i> Pagamento: ${p.payment_method || 'Não especificado'}</p>
                        <p style="margin: 0.5rem 0; font-size: 0.9rem;"><i class="fas fa-user"></i> Por: ${p.user_name} (${p.total_transactions || 0} transações)</p>
                    </div>
                    ${p.user_id != user.id ? `<button onclick="acceptProposal(${p.id})" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-check"></i> Aceitar Proposta
                    </button>` : '<p style="color: rgba(255,255,255,0.5); text-align: center; margin: 0;">Sua proposta</p>'}
                </div>
            `).join('');
        } else {
            proposalsList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma proposta disponível no momento</p>
                    <button onclick="document.querySelector('[data-tab=create]').click()" class="btn-primary">
                        Criar Proposta
                    </button>
                </div>
            `;
        }
    } catch (error) {
        proposalsList.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

// ============================================
// ACEITAR PROPOSTA
// ============================================
window.acceptProposal = async function(proposalId) {
    if (!confirm('Deseja realmente aceitar esta proposta?')) return;

    try {
        Loading.show();
        const response = await API.post('/proposals.php?action=accept', { proposal_id: proposalId });

        if (response.success) {
            NotificationUtil.success('Proposta aceita! Transação criada.');
            setTimeout(() => {
                document.querySelector('[data-tab="transactions"]').click();
            }, 1500);
        }
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// CARREGAR MINHAS PROPOSTAS
// ============================================
async function loadMyProposals() {
    const list = document.getElementById('myProposalsList');
    list.innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.6);"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    try {
        const response = await API.get('/proposals.php?action=my_proposals');

        if (response.success && response.data.proposals.length > 0) {
            list.innerHTML = response.data.proposals.map(p => `
                <div class="proposal-card">
                    <div class="proposal-header">
                        <div>
                            <span class="currency-pair">${p.currency_from} → ${p.currency_to}</span>
                            <span class="badge badge-${p.status === 'active' ? 'success' : p.status === 'completed' ? 'info' : 'warning'}">${p.status}</span>
                        </div>
                        <div>
                            <span class="proposal-amount">${formatCurrency(p.amount_from, p.currency_from)}</span>
                        </div>
                    </div>
                    <div style="margin: 1rem 0; color: rgba(255,255,255,0.8);">
                        <p style="margin: 0.5rem 0;"><i class="fas fa-chart-line"></i> Taxa: ${p.exchange_rate.toFixed(6)}</p>
                        <p style="margin: 0.5rem 0;"><i class="fas fa-calendar"></i> Criada em: ${formatDate(p.created_at)}</p>
                    </div>
                    ${p.status === 'active' ? `
                        <button onclick="deleteProposal(${p.id})" class="btn-secondary" style="width: 100%;">
                            <i class="fas fa-trash"></i> Deletar
                        </button>
                    ` : ''}
                </div>
            `).join('');
        } else {
            list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Você ainda não criou nenhuma proposta</p>
                </div>
            `;
        }
    } catch (error) {
        list.innerHTML = `<p style="text-align: center; color: #e74c3c;">${error.message}</p>`;
    }
}

window.deleteProposal = async function(id) {
    if (!confirm('Deseja realmente deletar esta proposta?')) return;

    try {
        Loading.show();
        await API.post('/proposals.php?action=delete', { proposal_id: id });
        NotificationUtil.success('Proposta deletada');
        loadMyProposals();
    } catch (error) {
        NotificationUtil.error(error.message);
    } finally {
        Loading.hide();
    }
};

// ============================================
// CARREGAR TRANSAÇÕES
// ============================================
async function loadTransactions() {
    const list = document.getElementById('transactionsList');
    list.innerHTML = '<p style="text-align: center; color: rgba(255,255,255,0.6);"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';

    // Simulação - em produção, criar endpoint específico
    list.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <p>Nenhuma transação ainda</p>
        </div>
    `;
}

// ============================================
// CRIAR PROPOSTA
// ============================================
const createForm = document.getElementById('createProposalForm');
if (createForm) {
    const amountInput = document.getElementById('amountFrom');
    const rateInput = document.getElementById('exchangeRate');
    const preview = document.getElementById('conversionPreview');

    function updatePreview() {
        const from = document.getElementById('currencyFrom').value;
        const to = document.getElementById('currencyTo').value;
        const amount = parseFloat(amountInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;

        if (from && to && amount > 0 && rate > 0) {
            const converted = amount * rate;
            const fee = converter.calculateFee(amount);
            preview.innerHTML = `
                <strong>Resumo:</strong> Você oferece ${formatCurrency(amount, from)}
                e receberá ${formatCurrency(converted, to)}
                <br><small>Taxa TransKwanza: ${formatCurrency(fee, from)} (3%)</small>
            `;
        } else {
            preview.textContent = 'Preencha os campos para ver o resumo';
        }
    }

    amountInput.addEventListener('input', updatePreview);
    rateInput.addEventListener('input', updatePreview);
    document.getElementById('currencyFrom').addEventListener('change', updatePreview);
    document.getElementById('currencyTo').addEventListener('change', updatePreview);

    createForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const data = {
            type: document.getElementById('proposalType').value,
            currency_from: document.getElementById('currencyFrom').value,
            currency_to: document.getElementById('currencyTo').value,
            amount_from: parseFloat(amountInput.value),
            exchange_rate: parseFloat(rateInput.value),
            payment_method: document.getElementById('paymentMethod').value
        };

        if (data.currency_from === data.currency_to) {
            NotificationUtil.error('Selecione moedas diferentes');
            return;
        }

        try {
            Loading.show();
            const response = await API.post('/proposals.php?action=create', data);

            if (response.success) {
                NotificationUtil.success('Proposta criada com sucesso!');
                createForm.reset();
                setTimeout(() => {
                    document.querySelector('[data-tab="my-proposals"]').click();
                }, 1500);
            }
        } catch (error) {
            NotificationUtil.error(error.message);
        } finally {
            Loading.hide();
        }
    });
}

// ============================================
// BUSCAR PROPOSTAS
// ============================================
document.getElementById('searchProposals').addEventListener('click', loadProposals);

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
    populateCurrencySelects();
    loadProposals();
});
