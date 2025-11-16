/**
 * TRANSKWANZA - Currency Conversion
 * Calculadora de conversão e taxas de câmbio
 */

// ============================================
// TAXAS DE CÂMBIO (Simuladas - em produção usar API real)
// ============================================
const EXCHANGE_RATES = {
    'BRL': { 'AOA': 150.25, 'EUR': 0.18, 'USD': 0.20, 'CUP': 4.80, 'RUB': 18.50, 'ZAR': 3.75, 'NAD': 3.75, 'MZN': 12.80 },
    'AOA': { 'BRL': 0.0067, 'EUR': 0.0012, 'USD': 0.0013, 'CUP': 0.032, 'RUB': 0.123, 'ZAR': 0.025, 'NAD': 0.025, 'MZN': 0.085 },
    'EUR': { 'BRL': 5.50, 'AOA': 850.00, 'USD': 1.10, 'CUP': 26.50, 'RUB': 102.00, 'ZAR': 20.70, 'NAD': 20.70, 'MZN': 70.50 },
    'USD': { 'BRL': 5.00, 'AOA': 773.50, 'EUR': 0.91, 'CUP': 24.00, 'RUB': 92.50, 'ZAR': 18.80, 'NAD': 18.80, 'MZN': 64.00 },
    'CUP': { 'BRL': 0.21, 'AOA': 31.25, 'EUR': 0.038, 'USD': 0.042, 'RUB': 3.85, 'ZAR': 0.78, 'NAD': 0.78, 'MZN': 2.67 },
    'RUB': { 'BRL': 0.054, 'AOA': 8.13, 'EUR': 0.0098, 'USD': 0.011, 'CUP': 0.26, 'ZAR': 0.203, 'NAD': 0.203, 'MZN': 0.69 },
    'ZAR': { 'BRL': 0.267, 'AOA': 40.00, 'EUR': 0.048, 'USD': 0.053, 'CUP': 1.28, 'RUB': 4.93, 'NAD': 1.00, 'MZN': 3.40 },
    'NAD': { 'BRL': 0.267, 'AOA': 40.00, 'EUR': 0.048, 'USD': 0.053, 'CUP': 1.28, 'RUB': 4.93, 'ZAR': 1.00, 'MZN': 3.40 },
    'MZN': { 'BRL': 0.078, 'AOA': 11.76, 'EUR': 0.014, 'USD': 0.016, 'CUP': 0.375, 'RUB': 1.45, 'ZAR': 0.294, 'NAD': 0.294 }
};

// Símbolos de moedas
const CURRENCY_SYMBOLS = {
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

// Nomes das moedas
const CURRENCY_NAMES = {
    'BRL': 'Real Brasileiro',
    'AOA': 'Kwanza Angolano',
    'EUR': 'Euro',
    'USD': 'Dólar Americano',
    'CUP': 'Peso Cubano',
    'RUB': 'Rublo Russo',
    'ZAR': 'Rand Sul-Africano',
    'NAD': 'Dólar Namíbio',
    'MZN': 'Metical Moçambicano'
};

// ============================================
// CALCULADORA DE CONVERSÃO
// ============================================
class CurrencyConverter {
    constructor() {
        this.rates = EXCHANGE_RATES;
        this.symbols = CURRENCY_SYMBOLS;
        this.names = CURRENCY_NAMES;
    }

    /**
     * Converte valor entre duas moedas
     */
    convert(amount, fromCurrency, toCurrency) {
        if (fromCurrency === toCurrency) {
            return parseFloat(amount);
        }

        if (!this.rates[fromCurrency] || !this.rates[fromCurrency][toCurrency]) {
            throw new Error('Conversão não suportada');
        }

        const rate = this.rates[fromCurrency][toCurrency];
        return parseFloat(amount) * rate;
    }

    /**
     * Obtém taxa de câmbio entre duas moedas
     */
    getRate(fromCurrency, toCurrency) {
        if (fromCurrency === toCurrency) {
            return 1;
        }

        if (!this.rates[fromCurrency] || !this.rates[fromCurrency][toCurrency]) {
            return 0;
        }

        return this.rates[fromCurrency][toCurrency];
    }

    /**
     * Formata valor com símbolo da moeda
     */
    format(amount, currency) {
        const symbol = this.symbols[currency] || currency;
        const formatted = parseFloat(amount).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return `${symbol} ${formatted}`;
    }

    /**
     * Calcula taxa da plataforma (3%)
     */
    calculateFee(amount) {
        return parseFloat(amount) * 0.03;
    }

    /**
     * Calcula valor total (com taxa)
     */
    calculateTotal(amount, includeFee = true) {
        const baseAmount = parseFloat(amount);
        if (!includeFee) return baseAmount;

        const fee = this.calculateFee(baseAmount);
        return baseAmount + fee;
    }

    /**
     * Obtém todas as moedas disponíveis
     */
    getCurrencies() {
        return Object.keys(this.rates);
    }

    /**
     * Obtém nome da moeda
     */
    getCurrencyName(code) {
        return this.names[code] || code;
    }

    /**
     * Obtém símbolo da moeda
     */
    getCurrencySymbol(code) {
        return this.symbols[code] || code;
    }
}

// Instância global
const converter = new CurrencyConverter();

// ============================================
// CALCULADORA INTERATIVA (Landing Page)
// ============================================
function initCalculator() {
    const calculator = document.getElementById('currencyCalculator');
    if (!calculator) return;

    const amountInput = document.getElementById('calcAmount');
    const fromSelect = document.getElementById('calcFrom');
    const toSelect = document.getElementById('calcTo');
    const resultDiv = document.getElementById('calcResult');
    const rateDiv = document.getElementById('calcRate');
    const feeDiv = document.getElementById('calcFee');

    // Popular selects
    const currencies = converter.getCurrencies();
    currencies.forEach(code => {
        const name = converter.getCurrencyName(code);
        const symbol = converter.getCurrencySymbol(code);

        fromSelect.innerHTML += `<option value="${code}">${code} - ${name} (${symbol})</option>`;
        toSelect.innerHTML += `<option value="${code}">${code} - ${name} (${symbol})</option>`;
    });

    // Valores padrão
    fromSelect.value = 'BRL';
    toSelect.value = 'AOA';
    amountInput.value = '1000';

    // Função de cálculo
    function calculate() {
        const amount = parseFloat(amountInput.value) || 0;
        const from = fromSelect.value;
        const to = toSelect.value;

        if (amount <= 0) {
            resultDiv.innerHTML = '<p class="text-error">Digite um valor válido</p>';
            return;
        }

        try {
            // Conversão
            const converted = converter.convert(amount, from, to);
            const rate = converter.getRate(from, to);
            const fee = converter.calculateFee(amount);
            const total = amount + fee;

            // Exibir resultados
            resultDiv.innerHTML = `
                <div class="calc-result-box">
                    <p class="calc-label">Você envia:</p>
                    <p class="calc-value">${converter.format(total, from)}</p>
                    <p class="calc-label-small">(inclui taxa de ${converter.format(fee, from)})</p>
                </div>
                <div class="calc-arrow">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="calc-result-box highlight">
                    <p class="calc-label">Destinatário recebe:</p>
                    <p class="calc-value-large">${converter.format(converted, to)}</p>
                </div>
            `;

            rateDiv.innerHTML = `
                <i class="fas fa-chart-line"></i>
                Taxa de câmbio: <strong>1 ${from} = ${rate.toFixed(6)} ${to}</strong>
            `;

            feeDiv.innerHTML = `
                <i class="fas fa-info-circle"></i>
                Nossa taxa: <strong>3%</strong> (${converter.format(fee, from)})
            `;

        } catch (error) {
            resultDiv.innerHTML = `<p class="text-error">${error.message}</p>`;
        }
    }

    // Event listeners
    amountInput.addEventListener('input', calculate);
    fromSelect.addEventListener('change', calculate);
    toSelect.addEventListener('change', calculate);

    // Botão de trocar moedas
    const swapBtn = document.getElementById('swapCurrencies');
    if (swapBtn) {
        swapBtn.addEventListener('click', () => {
            const temp = fromSelect.value;
            fromSelect.value = toSelect.value;
            toSelect.value = temp;
            calculate();
        });
    }

    // Calcular inicialmente
    calculate();
}

// ============================================
// CONVERSÃO EM TEMPO REAL (Dashboard)
// ============================================
function initLiveConverter() {
    const liveConverter = document.getElementById('liveConverter');
    if (!liveConverter) return;

    const fromCurrency = document.getElementById('liveFrom');
    const toCurrency = document.getElementById('liveTo');
    const amountInput = document.getElementById('liveAmount');
    const resultDiv = document.getElementById('liveResult');

    function updateConversion() {
        const amount = parseFloat(amountInput.value) || 0;
        const from = fromCurrency.value;
        const to = toCurrency.value;

        if (amount > 0) {
            const converted = converter.convert(amount, from, to);
            const rate = converter.getRate(from, to);

            resultDiv.innerHTML = `
                <strong>${converter.format(converted, to)}</strong>
                <small>Taxa: 1 ${from} = ${rate.toFixed(6)} ${to}</small>
            `;
        }
    }

    amountInput.addEventListener('input', updateConversion);
    fromCurrency.addEventListener('change', updateConversion);
    toCurrency.addEventListener('change', updateConversion);

    updateConversion();
}

// ============================================
// ATUALIZAR TAXAS (Simulação)
// ============================================
async function updateExchangeRates() {
    // Em produção, fazer fetch de API real
    // Exemplo: https://api.exchangerate-api.com/v4/latest/USD

    console.log('Taxas de câmbio atualizadas (simulado)');

    // Adicionar pequena variação aleatória (±2%)
    Object.keys(EXCHANGE_RATES).forEach(from => {
        Object.keys(EXCHANGE_RATES[from]).forEach(to => {
            const variation = 1 + (Math.random() * 0.04 - 0.02); // -2% a +2%
            EXCHANGE_RATES[from][to] *= variation;
        });
    });
}

// Atualizar taxas a cada 5 minutos
setInterval(updateExchangeRates, 5 * 60 * 1000);

// ============================================
// EXPORTAR
// ============================================
window.CurrencyConverter = CurrencyConverter;
window.converter = converter;
window.initCalculator = initCalculator;
window.initLiveConverter = initLiveConverter;

// Auto-inicializar
document.addEventListener('DOMContentLoaded', () => {
    initCalculator();
    initLiveConverter();
});
