// ============================================
// TRANSKWANZA - FASE 3: SOCIAL LOGIN
// SDK do Google e Facebook
// ============================================

const SOCIAL_API_URL = '/api/social_login.php';

// ============================================
// CONFIGURAÇÃO
// ============================================

// ⚠️ IMPORTANTE: Substitua com seus Client IDs reais
const GOOGLE_CLIENT_ID = 'SEU_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
const FACEBOOK_APP_ID = 'SEU_FACEBOOK_APP_ID';

// ============================================
// GOOGLE SIGN-IN
// ============================================

// Carregar SDK do Google
function loadGoogleSDK() {
    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);

    script.onload = () => {
        initializeGoogleSignIn();
    };
}

// Inicializar Google Sign-In
function initializeGoogleSignIn() {
    if (typeof google === 'undefined') return;

    // Renderizar botão do Google
    const googleBtnContainer = document.getElementById('googleSignInBtn');
    if (googleBtnContainer) {
        google.accounts.id.initialize({
            client_id: GOOGLE_CLIENT_ID,
            callback: handleGoogleCallback,
            auto_select: false
        });

        google.accounts.id.renderButton(
            googleBtnContainer,
            {
                theme: 'outline',
                size: 'large',
                width: '100%',
                text: 'continue_with',
                shape: 'rectangular'
            }
        );
    }
}

// Callback do Google
async function handleGoogleCallback(response) {
    try {
        // Decodificar JWT token
        const credential = response.credential;
        const payload = parseJwt(credential);

        const result = await fetch(`${SOCIAL_API_URL}?provider=google`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_token: credential,
                google_id: payload.sub,
                email: payload.email,
                name: payload.name,
                picture: payload.picture
            })
        });

        const data = await result.json();

        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = 'dashboard.html';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Erro no login do Google:', error);
        alert('Erro ao fazer login com Google');
    }
}

// Decodificar JWT
function parseJwt(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    } catch (e) {
        return {};
    }
}

// ============================================
// FACEBOOK LOGIN
// ============================================

// Carregar SDK do Facebook
function loadFacebookSDK() {
    window.fbAsyncInit = function() {
        FB.init({
            appId: FACEBOOK_APP_ID,
            cookie: true,
            xfbml: true,
            version: 'v18.0'
        });
    };

    // Carregar script
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s);
        js.id = id;
        js.src = 'https://connect.facebook.net/pt_BR/sdk.js';
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
}

// Login com Facebook
function loginWithFacebook() {
    if (typeof FB === 'undefined') {
        alert('SDK do Facebook não carregado. Verifique sua conexão.');
        return;
    }

    FB.login(function(response) {
        if (response.authResponse) {
            // Usuário autenticado
            FB.api('/me', { fields: 'id,name,email,picture' }, async function(user) {
                try {
                    const result = await fetch(`${SOCIAL_API_URL}?provider=facebook`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            access_token: response.authResponse.accessToken,
                            user_id: response.authResponse.userID,
                            email: user.email,
                            name: user.name,
                            picture: user.picture?.data?.url
                        })
                    });

                    const data = await result.json();

                    if (data.success) {
                        localStorage.setItem('user', JSON.stringify(data.user));
                        window.location.href = 'dashboard.html';
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error('Erro no login do Facebook:', error);
                    alert('Erro ao fazer login com Facebook');
                }
            });
        } else {
            console.log('Usuário cancelou o login ou não autorizou.');
        }
    }, { scope: 'public_profile,email' });
}

// ============================================
// INICIALIZAÇÃO
// ============================================

// Carregar SDKs quando página carregar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        loadGoogleSDK();
        loadFacebookSDK();
    });
} else {
    loadGoogleSDK();
    loadFacebookSDK();
}

// Adicionar evento ao botão do Facebook
document.addEventListener('DOMContentLoaded', function() {
    const facebookBtn = document.getElementById('facebookLoginBtn');
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loginWithFacebook();
        });
    }
});
