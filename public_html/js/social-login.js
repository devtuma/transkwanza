/**
 * TRANSKWANZA - Social Login
 * OAuth: Google, Facebook, Instagram, Apple
 */

// ============================================
// CONFIGURATION (ATUALIZAR COM SEUS IDs)
// ============================================
const SOCIAL_CONFIG = {
    google: {
        clientId: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'
    },
    facebook: {
        appId: 'YOUR_FACEBOOK_APP_ID'
    },
    apple: {
        clientId: 'com.transkwanza.signin',
        redirectURI: window.location.origin + '/login.html'
    }
};

// ============================================
// GOOGLE SIGN IN
// ============================================
function initGoogleSignIn() {
    if (typeof google === 'undefined') {
        console.warn('Google SDK not loaded');
        return;
    }

    google.accounts.id.initialize({
        client_id: SOCIAL_CONFIG.google.clientId,
        callback: handleGoogleCallback
    });

    const googleBtn = document.getElementById('googleLoginBtn');
    if (googleBtn) {
        googleBtn.addEventListener('click', () => {
            google.accounts.id.prompt();
        });
    }
}

async function handleGoogleCallback(response) {
    try {
        Loading.show();

        const result = await API.post('/social_login.php?provider=google', {
            access_token: response.credential
        });

        if (result.success) {
            handleSocialLoginSuccess(result.data);
        }
    } catch (error) {
        NotificationUtil.error('Erro ao fazer login com Google');
    } finally {
        Loading.hide();
    }
}

// ============================================
// FACEBOOK LOGIN
// ============================================
function initFacebookLogin() {
    if (typeof FB === 'undefined') {
        console.warn('Facebook SDK not loaded');
        return;
    }

    window.fbAsyncInit = function() {
        FB.init({
            appId: SOCIAL_CONFIG.facebook.appId,
            cookie: true,
            xfbml: true,
            version: 'v18.0'
        });
    };

    const facebookBtn = document.getElementById('facebookLoginBtn');
    if (facebookBtn) {
        facebookBtn.addEventListener('click', facebookLogin);
    }
}

function facebookLogin() {
    FB.login(function(response) {
        if (response.authResponse) {
            handleFacebookCallback(response.authResponse.accessToken);
        }
    }, { scope: 'public_profile,email' });
}

async function handleFacebookCallback(accessToken) {
    try {
        Loading.show();

        const result = await API.post('/social_login.php?provider=facebook', {
            access_token: accessToken
        });

        if (result.success) {
            handleSocialLoginSuccess(result.data);
        }
    } catch (error) {
        NotificationUtil.error('Erro ao fazer login com Facebook');
    } finally {
        Loading.hide();
    }
}

// ============================================
// INSTAGRAM LOGIN (usa Facebook)
// ============================================
const instagramBtn = document.getElementById('instagramLoginBtn');
if (instagramBtn) {
    instagramBtn.addEventListener('click', () => {
        NotificationUtil.info('Instagram login usa Facebook. Clique no botão do Facebook.');
        // Instagram usa mesmo fluxo do Facebook
        if (typeof FB !== 'undefined') {
            facebookLogin();
        }
    });
}

// ============================================
// APPLE SIGN IN
// ============================================
function initAppleSignIn() {
    if (typeof AppleID === 'undefined') {
        console.warn('Apple SDK not loaded');
        return;
    }

    AppleID.auth.init({
        clientId: SOCIAL_CONFIG.apple.clientId,
        scope: 'name email',
        redirectURI: SOCIAL_CONFIG.apple.redirectURI,
        usePopup: true
    });

    const appleBtn = document.getElementById('appleLoginBtn');
    if (appleBtn) {
        appleBtn.addEventListener('click', appleLogin);
    }
}

async function appleLogin() {
    try {
        const response = await AppleID.auth.signIn();
        await handleAppleCallback(response);
    } catch (error) {
        console.error('Apple login error:', error);
    }
}

async function handleAppleCallback(response) {
    try {
        Loading.show();

        const result = await API.post('/social_login.php?provider=apple', {
            id_token: response.authorization.id_token,
            name: response.user?.name
        });

        if (result.success) {
            handleSocialLoginSuccess(result.data);
        }
    } catch (error) {
        NotificationUtil.error('Erro ao fazer login com Apple');
    } finally {
        Loading.hide();
    }
}

// ============================================
// HANDLE SUCCESS
// ============================================
function handleSocialLoginSuccess(data) {
    Auth.setAuth(data.token, data.user);
    NotificationUtil.success('Login realizado com sucesso!');

    setTimeout(() => {
        if (data.requires_kyc) {
            window.location.href = '/kyc.html';
        } else if (data.user.is_admin) {
            window.location.href = '/admin.html';
        } else {
            window.location.href = '/dashboard.html';
        }
    }, 1000);
}

// ============================================
// INITIALIZE ALL
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Aguardar SDKs carregarem
    setTimeout(() => {
        initGoogleSignIn();
        initFacebookLogin();
        initAppleSignIn();
    }, 1000);
});
