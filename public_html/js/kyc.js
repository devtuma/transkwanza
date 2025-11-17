// ============================================
// TRANSKWANZA - FASE 2: KYC FRONTEND
// ============================================

const API_URL = '/api/kyc.php';

// Pegar usuário do localStorage
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (!user.id) {
    window.location.href = 'login.html';
}

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

// ============================================
// VERIFICAR STATUS DO KYC
// ============================================
async function checkKYCStatus() {
    try {
        const response = await fetch(`${API_URL}?action=status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: user.id })
        });

        const result = await response.json();

        if (result.success && result.kyc_status === 'approved') {
            // Mostrar tela de aprovado
            document.getElementById('kycForm').style.display = 'none';
            document.getElementById('kycApproved').style.display = 'block';
            document.getElementById('approvedDocType').textContent = result.document_type || 'N/A';
            document.getElementById('approvedDocNumber').textContent = result.document_number || 'N/A';
        }
    } catch (error) {
        console.error('Erro ao verificar status:', error);
    }
}

// Verificar status ao carregar
checkKYCStatus();

// ============================================
// PREVIEW DE IMAGENS
// ============================================
function setupFilePreview(inputId, previewId, labelId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const label = document.getElementById(labelId);

    input.addEventListener('change', (e) => {
        const file = e.target.files[0];

        if (file) {
            // Validar tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showMessage('Arquivo muito grande! Máximo: 5MB', 'error');
                input.value = '';
                return;
            }

            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('Tipo de arquivo não permitido. Use JPG, PNG ou PDF', 'error');
                input.value = '';
                return;
            }

            // Atualizar label
            label.classList.add('has-file');

            // Mostrar preview se for imagem
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <div class="file-info">✓ ${file.name} (${(file.size / 1024).toFixed(1)} KB)</div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = `<div class="file-info">✓ ${file.name} (PDF)</div>`;
            }
        }
    });
}

// Configurar previews
setupFilePreview('documentFront', 'frontPreview', 'frontLabel');
setupFilePreview('documentBack', 'backPreview', 'backLabel');
setupFilePreview('documentSelfie', 'selfiePreview', 'selfieLabel');

// ============================================
// UPLOAD DO FORMULÁRIO
// ============================================
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validar campos obrigatórios
        const documentType = document.getElementById('documentType').value;
        const documentNumber = document.getElementById('documentNumber').value;
        const documentFront = document.getElementById('documentFront').files[0];
        const documentSelfie = document.getElementById('documentSelfie').files[0];

        if (!documentType || !documentNumber || !documentFront || !documentSelfie) {
            showMessage('Preencha todos os campos obrigatórios', 'error');
            return;
        }

        // Criar FormData
        const formData = new FormData();
        formData.append('user_id', user.id);
        formData.append('document_type', documentType);
        formData.append('document_number', documentNumber);
        formData.append('document_front', documentFront);
        formData.append('document_selfie', documentSelfie);

        // Adicionar verso se existir
        const documentBack = document.getElementById('documentBack').files[0];
        if (documentBack) {
            formData.append('document_back', documentBack);
        }

        // Desabilitar botão
        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        try {
            const response = await fetch(`${API_URL}?action=upload`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage('Documentos aprovados! Redirecionando...', 'success');

                // Atualizar status do usuário no localStorage
                user.kyc_status = 'approved';
                localStorage.setItem('user', JSON.stringify(user));

                // Redirecionar para dashboard após 2 segundos
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);
            } else {
                showMessage(result.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Documentos';
            }
        } catch (error) {
            console.error('Erro no upload:', error);
            showMessage('Erro ao enviar documentos. Tente novamente.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar Documentos';
        }
    });
}
