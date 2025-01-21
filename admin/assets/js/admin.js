// Funções utilitárias
function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarHora(hora) {
    return hora.substring(0, 5);
}

function formatarTelefone(telefone) {
    telefone = telefone.replace(/\D/g, '');
    return telefone.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
}

// Loading
function mostrarLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading';
    loading.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loading);
}

function ocultarLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
}

// Notificações
function mostrarNotificacao(mensagem, tipo = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${mensagem}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.getElementById('toast-container') || document.body;
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Confirmações
function confirmar(mensagem) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${mensagem}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmar">Confirmar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        modal.querySelector('#confirmar').addEventListener('click', () => {
            modalInstance.hide();
            resolve(true);
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
            resolve(false);
        });
    });
}

// Requisições AJAX
async function fetchApi(url, options = {}) {
    try {
        mostrarLoading();
        
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erro ao processar requisição');
        }
        
        return data;
    } catch (error) {
        mostrarNotificacao(error.message, 'danger');
        throw error;
    } finally {
        ocultarLoading();
    }
}

// Validação de formulários
function validarFormulario(form) {
    const inputs = form.querySelectorAll('input, select, textarea');
    let valido = true;
    
    inputs.forEach(input => {
        if (input.hasAttribute('required') && !input.value) {
            input.classList.add('is-invalid');
            valido = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('is-invalid');
                valido = false;
            }
        }
        
        if (input.type === 'tel' && input.value) {
            const telefoneRegex = /^\(\d{2}\) \d{5}-\d{4}$/;
            if (!telefoneRegex.test(input.value)) {
                input.classList.add('is-invalid');
                valido = false;
            }
        }
    });
    
    return valido;
}

// Máscaras de input
function aplicarMascaras() {
    const telefones = document.querySelectorAll('input[type="tel"]');
    telefones.forEach(input => {
        input.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            if (valor.length > 11) valor = valor.substring(0, 11);
            if (valor.length >= 11) {
                valor = valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            }
            e.target.value = valor;
        });
    });
}

// Tema escuro
function alternarTema() {
    const html = document.documentElement;
    const temaAtual = html.getAttribute('data-bs-theme');
    const novoTema = temaAtual === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-bs-theme', novoTema);
    localStorage.setItem('tema', novoTema);
    
    const icone = document.querySelector('#tema-icone');
    if (icone) {
        icone.className = `bi bi-${novoTema === 'dark' ? 'moon' : 'sun'}-fill`;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    // Aplica tema salvo
    const temaSalvo = localStorage.getItem('tema');
    if (temaSalvo) {
        document.documentElement.setAttribute('data-bs-theme', temaSalvo);
    }
    
    // Aplica máscaras
    aplicarMascaras();
    
    // Inicializa tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    
    // Inicializa popovers
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(popover => new bootstrap.Popover(popover));
    
    // Handler para links de confirmação
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('[data-confirmar]');
        if (link) {
            e.preventDefault();
            
            const confirmado = await confirmar(link.dataset.confirmar);
            if (confirmado) {
                window.location.href = link.href;
            }
        }
    });
    
    // Handler para formulários
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.hasAttribute('data-validar')) {
            if (!validarFormulario(form)) {
                e.preventDefault();
                mostrarNotificacao('Por favor, preencha todos os campos obrigatórios corretamente.', 'danger');
            }
        }
    });
}); 