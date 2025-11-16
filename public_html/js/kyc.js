/**
 * TRANSKWANZA - KYC Upload
 * Verificação de identidade com upload de documentos
 */

// Verificar autenticação
if (!Auth.requireAuth()) {
    window.location.href = '/login.html';
}

const user = Auth.getUser();

// ============================================
// VERIFICAR STATUS DO KYC
// ============================================
async function checkKYCStatus() {
    const statusDisplay = document.getElementById('kycStatusDisplay');
    const statusMessage = document.getElementById('kycStatusMessage');
    const kycForm = document.getElementById('kycForm');

    if (!user) return;

    if (user.kyc_status === 'approved') {
        statusDisplay.style.display = 'block';
        statusDisplay.innerHTML = `
            <div style="background: linear-gradient(135deg, #2ecc71, #27ae60); padding: 2rem; border-radius: 1rem; text-align: center; margin-bottom: 2rem;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #fff; margin-bottom: 1rem;"></i>
                <h2 style="color: #fff; margin-bottom: 0.5rem;">KYC Aprovado!</h2>
                <p style="color: rgba(255,255,255,0.9); margin-bottom: 1.5rem;">
                    Sua identidade foi verificada com sucesso.
                </p>
                <a href="dashboard.html" class="btn-secondary" style="display: inline-block; background: #fff; color: #2ecc71;">
                    Ir para Dashboard
                </a>
            </div>
        `;
        kycForm.style.display = 'none';
        statusMessage.style.display = 'none';
    } else if (user.kyc_status === 'under_review') {
        statusDisplay.style.display = 'block';
        statusDisplay.innerHTML = `
            <div style="background: rgba(52, 152, 219, 0.2); padding: 2rem; border-radius: 1rem; text-align: center; margin-bottom: 2rem; border: 1px solid rgba(52, 152, 219, 0.5);">
                <i class="fas fa-clock" style="font-size: 4rem; color: #3498db; margin-bottom: 1rem;"></i>
                <h2 style="color: #fff; margin-bottom: 0.5rem;">Em Análise</h2>
                <p style="color: rgba(255,255,255,0.8);">
                    Seus documentos estão sendo analisados pela nossa equipe. Você receberá uma notificação em breve.
                </p>
            </div>
        `;
        kycForm.style.display = 'none';
        statusMessage.style.display = 'none';
    } else if (user.kyc_status === 'rejected') {
        statusDisplay.style.display = 'block';
        statusDisplay.innerHTML = `
            <div style="background: rgba(231, 76, 60, 0.2); padding: 2rem; border-radius: 1rem; margin-bottom: 2rem; border: 1px solid rgba(231, 76, 60, 0.5);">
                <i class="fas fa-times-circle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 1rem;"></i>
                <h2 style="color: #fff; margin-bottom: 0.5rem;">KYC Rejeitado</h2>
                <p style="color: rgba(255,255,255,0.9); margin-bottom: 1rem;">
                    ${user.kyc_rejection_reason || 'Seus documentos foram rejeitados.'}
                </p>
                <p style="color: rgba(255,255,255,0.7);">
                    Por favor, envie novamente com documentos válidos e legíveis.
                </p>
            </div>
        `;
        statusMessage.textContent = 'Reenvie seus documentos';
        kycForm.style.display = 'block';
    }
}

// ============================================
// PREVIEW DE IMAGENS
// ============================================
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    input.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) {
            preview.innerHTML = '';
            return;
        }

        // Validar tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
            NotificationUtil.error('Arquivo muito grande! Máximo: 5MB');
            input.value = '';
            return;
        }

        // Validar tipo
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
            NotificationUtil.error('Tipo de arquivo inválido! Use JPG, PNG ou PDF');
            input.value = '';
            return;
        }

        // Mostrar preview (apenas para imagens)
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `
                    <img src="${e.target.result}" style="max-width: 100%; border-radius: 8px; margin-top: 1rem;">
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-top: 0.5rem;">
                        <i class="fas fa-check-circle"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                    </p>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `
                <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-top: 0.5rem;">
                    <i class="fas fa-file-pdf"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                </p>
            `;
        }
    });
}

// ============================================
// SUBMIT KYC FORM
// ============================================
const kycForm = document.getElementById('kycForm');
if (kycForm) {
    kycForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const documentType = document.getElementById('documentType').value;
        const documentNumber = document.getElementById('documentNumber').value;
        const documentFront = document.getElementById('documentFront').files[0];
        const documentSelfie = document.getElementById('documentSelfie').files[0];
        const documentBack = document.getElementById('documentBack').files[0];

        if (!documentType || !documentNumber || !documentFront || !documentSelfie) {
            NotificationUtil.error('Preencha todos os campos obrigatórios');
            return;
        }

        try {
            Loading.show();

            // Criar FormData
            const formData = new FormData();
            formData.append('document_type', documentType);
            formData.append('document_number', documentNumber);
            formData.append('document_front', documentFront);
            formData.append('document_selfie', documentSelfie);
            if (documentBack) {
                formData.append('document_back', documentBack);
            }

            // Enviar para API
            const response = await API.uploadFile('/kyc.php', formData);

            if (response.success) {
                NotificationUtil.success('Documentos enviados com sucesso! Aguarde a análise.');

                // Atualizar status do usuário localmente
                const updatedUser = { ...user, kyc_status: 'under_review' };
                Auth.setAuth(localStorage.getItem('token'), updatedUser);

                // Recarregar em 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (error) {
            NotificationUtil.error(error.message || 'Erro ao enviar documentos');
        } finally {
            Loading.hide();
        }
    });
}

// ============================================
// LOGOUT
// ============================================
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        Auth.logout();
    });
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    checkKYCStatus();
    setupImagePreview('documentFront', 'previewFront');
    setupImagePreview('documentSelfie', 'previewSelfie');
    setupImagePreview('documentBack', 'previewBack');
});
