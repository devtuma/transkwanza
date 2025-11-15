/**
 * TRANSKWANZA - Authentication
 * Login e Cadastro tradicional
 */

// ============================================
// LOGIN FORM
// ============================================
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (!validateEmail(email)) {
            NotificationUtil.error('Email inválido');
            return;
        }

        if (password.length < 6) {
            NotificationUtil.error('Senha deve ter no mínimo 6 caracteres');
            return;
        }

        try {
            Loading.show();

            const response = await API.post('/auth.php?action=login', {
                email,
                password
            });

            if (response.success) {
                Auth.setAuth(response.data.token, response.data.user);
                NotificationUtil.success('Login realizado com sucesso!');

                setTimeout(() => {
                    if (response.data.requires_kyc) {
                        window.location.href = '/kyc.html';
                    } else if (response.data.user.is_admin) {
                        window.location.href = '/admin.html';
                    } else {
                        window.location.href = '/dashboard.html';
                    }
                }, 1000);
            }
        } catch (error) {
            NotificationUtil.error(error.message || 'Erro ao fazer login');
        } finally {
            Loading.hide();
        }
    });
}

// ============================================
// REGISTER FORM
// ============================================
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const country = document.getElementById('country').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validações
        if (name.length < 3) {
            NotificationUtil.error('Nome deve ter no mínimo 3 caracteres');
            return;
        }

        if (!validateEmail(email)) {
            NotificationUtil.error('Email inválido');
            return;
        }

        if (!country) {
            NotificationUtil.error('Selecione um país');
            return;
        }

        if (password.length < 6) {
            NotificationUtil.error('Senha deve ter no mínimo 6 caracteres');
            return;
        }

        if (password !== confirmPassword) {
            NotificationUtil.error('As senhas não coincidem');
            return;
        }

        try {
            Loading.show();

            const response = await API.post('/auth.php?action=register', {
                name,
                email,
                phone,
                country,
                password
            });

            if (response.success) {
                Auth.setAuth(response.data.token, response.data.user);
                NotificationUtil.success('Cadastro realizado com sucesso!');

                setTimeout(() => {
                    window.location.href = '/kyc.html';
                }, 1000);
            }
        } catch (error) {
            NotificationUtil.error(error.message || 'Erro ao fazer cadastro');
        } finally {
            Loading.hide();
        }
    });
}

// ============================================
// AUTO-REDIRECT IF ALREADY LOGGED IN
// ============================================
if (window.location.pathname.includes('login.html') || window.location.pathname.includes('cadastro.html')) {
    if (Auth.isAuthenticated()) {
        const user = Auth.getUser();
        if (user.kyc_status === 'approved') {
            window.location.href = '/dashboard.html';
        } else {
            window.location.href = '/kyc.html';
        }
    }
}
