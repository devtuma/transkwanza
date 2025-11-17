// ============================================
// TRANSKWANZA - FASE 3: PROPOSALS FRONTEND
// ============================================

const API_URL = '/api/proposals.php';

// Pegar usuário do localStorage
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (!user.id) {
    window.location.href = 'login.html';
}

let currencies = [];

// Função auxiliar para exibir mensagens
function showMessage(message, type = 'error') {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;

    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';

    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// ============================================
// CARREGAR MOEDAS
// ============================================
async function loadCurrencies() {
    try {
        const response = await fetch(`${API_URL}?action=currencies`);
        const result = await response.json();

        if (result.success) {
            currencies = result.currencies;

            // Preencher selects
            const selects = ['currencyFrom', 'currencyTo', 'filterFrom', 'filterTo'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    currencies.forEach(currency => {
                        const option = document.createElement('option');
                        option.value = currency.code;
                        option.textContent = `${currency.symbol} ${currency.name} (${currency.code})`;
                        select.appendChild(option);
                    });
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar moedas:', error);
    }
}

// ============================================
// TABS
// ============================================
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        // Remover active de todos
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));

        // Ativar clicado
        tab.classList.add('active');
        const tabId = tab.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    });
});

// ============================================
// LISTAR PROPOSTAS
// ============================================
async function loadProposals(filters = {}) {
    try {
        let url = `${API_URL}?action=list`;

        if (filters.currency_from) url += `&currency_from=${filters.currency_from}`;
        if (filters.currency_to) url += `&currency_to=${filters.currency_to}`;
        if (filters.type) url += `&type=${filters.type}`;

        const response = await fetch(url);
        const result = await response.json();

        const container = document.getElementById('proposalsList');

        if (!result.success || result.proposals.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>Nenhuma proposta encontrada</h3>
                    <p>Tente ajustar os filtros ou seja o primeiro a criar uma proposta!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = result.proposals.map(proposal => {
            const typeLabel = proposal.type === 'buy' ? 'Comprar' : 'Vender';
            const typeClass = proposal.type;

            return `
                <div class="proposal-card">
                    <div class="proposal-header">
                        <div>
                            <span class="proposal-type ${typeClass}">${typeLabel}</span>
                            <div style="font-size: 0.85rem; color: #666; margin-top: 5px;">
                                por ${proposal.user_name} - ${proposal.country}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; color: #666;">Criada em</div>
                            <div style="font-size: 0.9rem;">${new Date(proposal.created_at).toLocaleDateString('pt-BR')}</div>
                        </div>
                    </div>

                    <div class="currency-pair">
                        ${proposal.currency_from} → ${proposal.currency_to}
                    </div>

                    <div class="proposal-details">
                        <div class="detail-item">
                            <div class="detail-label">Valor</div>
                            <div class="detail-value">${parseFloat(proposal.amount_from).toLocaleString('pt-BR')} ${proposal.currency_from}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Você Recebe</div>
                            <div class="detail-value">${parseFloat(proposal.amount_to).toLocaleString('pt-BR')} ${proposal.currency_to}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Taxa de Câmbio</div>
                            <div class="detail-value">${parseFloat(proposal.exchange_rate).toFixed(6)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Método de Pagamento</div>
                            <div class="detail-value">${proposal.payment_method}</div>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block" style="margin-top: 15px;" onclick="alert('Funcionalidade de aceitar proposta virá na FASE 4!')">
                        Aceitar Proposta
                    </button>
                </div>
            `;
        }).join('');

    } catch (error) {
        console.error('Erro ao carregar propostas:', error);
        showMessage('Erro ao carregar propostas', 'error');
    }
}

// Aplicar filtros
document.getElementById('applyFilters').addEventListener('click', () => {
    const filters = {
        currency_from: document.getElementById('filterFrom').value,
        currency_to: document.getElementById('filterTo').value,
        type: document.getElementById('filterType').value
    };
    loadProposals(filters);
});

// ============================================
// PREVIEW DE CONVERSÃO
// ============================================
let previewTimeout;
function updateConversionPreview() {
    clearTimeout(previewTimeout);

    previewTimeout = setTimeout(async () => {
        const from = document.getElementById('currencyFrom').value;
        const to = document.getElementById('currencyTo').value;
        const amount = parseFloat(document.getElementById('amount').value);
        const rate = parseFloat(document.getElementById('exchangeRate').value);

        if (from && to && amount > 0 && rate > 0) {
            const convertedAmount = amount * rate;
            const fee = convertedAmount * 0.03;
            const finalAmount = convertedAmount - fee;

            document.getElementById('conversionPreview').style.display = 'block';
            document.getElementById('previewResult').innerHTML = `
                ${amount.toLocaleString('pt-BR')} ${from} → ${finalAmount.toLocaleString('pt-BR')} ${to}<br>
                <small style="font-size: 0.9rem; color: #666;">
                    (Convertido: ${convertedAmount.toLocaleString('pt-BR')} - Taxa 3%: ${fee.toLocaleString('pt-BR')})
                </small>
            `;
        } else {
            document.getElementById('conversionPreview').style.display = 'none';
        }
    }, 500);
}

// Listeners para preview
['currencyFrom', 'currencyTo', 'amount', 'exchangeRate'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('input', updateConversionPreview);
        element.addEventListener('change', updateConversionPreview);
    }
});

// ============================================
// CRIAR PROPOSTA
// ============================================
document.getElementById('createProposalForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const data = {
        user_id: user.id,
        type: document.getElementById('proposalType').value,
        currency_from: document.getElementById('currencyFrom').value,
        currency_to: document.getElementById('currencyTo').value,
        amount_from: parseFloat(document.getElementById('amount').value),
        exchange_rate: parseFloat(document.getElementById('exchangeRate').value),
        payment_method: document.getElementById('paymentMethod').value
    };

    const submitBtn = document.querySelector('#createProposalForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Criando...';

    try {
        const response = await fetch(`${API_URL}?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Proposta criada com sucesso!', 'success');
            document.getElementById('createProposalForm').reset();
            document.getElementById('conversionPreview').style.display = 'none';

            // Voltar para aba de busca e recarregar
            document.querySelector('.tab[data-tab="browse"]').click();
            loadProposals();
        } else {
            // Se KYC for necessário
            if (result.kyc_required) {
                document.getElementById('kycAlert').style.display = 'block';
                showMessage(result.message, 'error');
            } else {
                showMessage(result.message, 'error');
            }
        }
    } catch (error) {
        console.error('Erro ao criar proposta:', error);
        showMessage('Erro ao criar proposta', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Criar Proposta';
    }
});

// ============================================
// VERIFICAR STATUS KYC
// ============================================
function checkKYCStatus() {
    if (user.kyc_status !== 'approved') {
        document.getElementById('kycAlert').style.display = 'block';
    }
}

// Logout
document.getElementById('logoutBtn').addEventListener('click', () => {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
});

// Inicializar
loadCurrencies();
loadProposals();
checkKYCStatus();
