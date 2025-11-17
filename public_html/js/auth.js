// ============================================
// TRANSKWANZA - FASE 1: AUTH FRONTEND
// ============================================

const API_URL = '/api/auth.php';

// Função auxiliar para exibir mensagens
function showMessage(message, type = 'error') {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;

    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';

    // Esconder após 5 segundos
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// Função auxiliar para requisições
async function apiRequest(action, data) {
    try {
        const response = await fetch(`${API_URL}?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        return await response.json();
    } catch (error) {
        console.error('Erro na requisição:', error);
        return { success: false, message: 'Erro de conexão com o servidor' };
    }
}

// ============================================
// CADASTRO
// ============================================
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(registerForm);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            country: formData.get('country')
        };

        // Desabilitar botão
        const submitBtn = registerForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Cadastrando...';

        const result = await apiRequest('register', data);

        if (result.success) {
            showMessage('Cadastro realizado! Redirecionando...', 'success');

            // Salvar usuário no localStorage
            localStorage.setItem('user', JSON.stringify(result.user));

            // FASE 2: Redirecionar para KYC se status for 'pending'
            setTimeout(() => {
                if (result.user.kyc_status === 'pending') {
                    window.location.href = 'kyc.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }, 1500);
        } else {
            showMessage(result.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Criar Conta';
        }
    });
}

// ============================================
// LOGIN
// ============================================
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(loginForm);
        const data = {
            email: formData.get('email'),
            password: formData.get('password')
        };

        // Desabilitar botão
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Entrando...';

        const result = await apiRequest('login', data);

        if (result.success) {
            showMessage('Login realizado! Redirecionando...', 'success');

            // Salvar usuário no localStorage
            localStorage.setItem('user', JSON.stringify(result.user));

            // FASE 2: Redirecionar para KYC se status for 'pending'
            setTimeout(() => {
                if (result.user.kyc_status === 'pending') {
                    window.location.href = 'kyc.html';
                } else {
                    window.location.href = 'dashboard.html';
                }
            }, 1500);
        } else {
            showMessage(result.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Entrar';
        }
    });
}
