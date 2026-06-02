/**
 * BIBLIOTECA JAVASCRIPT DE VALIDAÇÕES DE FORMULÁRIOS
 * Implementada para lançamento crítico - Kaliye Platform
 * Data: 02 de Junho de 2026
 */

class ValidadorFormulario {
    // Constantes
    static TAMANHO_COMENTARIO = 250;
    static TAMANHO_TEXTO_LONGO = 300;
    
    // Cores para sinalização
    static COR_ERRO = '#dc3545';
    static COR_SUCESSO = '#28a745';
    static COR_OBRIGATORIO = '#ff6b6b';
    
    /**
     * Inicializa validações em um formulário
     */
    static inicializarFormulario(idFormulario) {
        const formulario = document.getElementById(idFormulario);
        if (!formulario) return;
        
        // Adiciona listener para validação ao sair do campo
        formulario.querySelectorAll('input, textarea, select').forEach(campo => {
            campo.addEventListener('blur', () => this.validarCampo(campo));
            campo.addEventListener('change', () => this.validarCampo(campo));
            
            // Para campos de texto, valida em tempo real o limite de caracteres
            if (campo.tagName === 'TEXTAREA' || campo.type === 'text') {
                campo.addEventListener('input', () => this.atualizarContadorCaracteres(campo));
            }
        });
        
        // Previne envio se houver erros
        formulario.addEventListener('submit', (e) => {
            if (!this.validarFormulario(formulario)) {
                e.preventDefault();
                this.mostrarNotificacao('Por favor, corrija os erros antes de enviar.', 'erro');
            }
        });
    }
    
    /**
     * Valida um campo individual
     */
    static validarCampo(campo) {
        const tipo = campo.dataset.tipo || campo.type;
        const obrigatorio = campo.hasAttribute('required') || campo.dataset.obrigatorio === 'true';
        let erro = null;
        
        // Se campo está vazio e não é obrigatório, remove erro
        if (!campo.value.trim() && !obrigatorio) {
            this.removerErro(campo);
            return true;
        }
        
        // Valida obrigatório
        if (obrigatorio && !campo.value.trim()) {
            erro = `O campo "${campo.placeholder || campo.name}" é obrigatório.`;
            this.mostrarErro(campo, erro);
            return false;
        }
        
        // Valida conforme tipo
        switch (tipo) {
            case 'numero':
            case 'numeros-apenas':
                if (!/^\d+$/.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" deve conter apenas números.`;
                }
                break;
                
            case 'letras':
            case 'letras-apenas':
                if (!/^[a-zA-ZáéíóúãõâêôàäöäöüçÁÉÍÓÚÃÕÂÊÔÀ\s]+$/u.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" deve conter apenas letras.`;
                }
                break;
                
            case 'alfanumerico':
                if (!/^[a-zA-Z0-9áéíóúãõâêôàäöäöüçÁÉÍÓÚÃÕÂÊÔÀ\s\-\.]+$/u.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" contém caracteres não permitidos.`;
                }
                break;
                
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" deve ser um email válido.`;
                }
                break;
                
            case 'telefone':
                if (!/^[\d\s\-\+\(\)]+$/.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" deve ser um telefone válido.`;
                }
                break;
                
            case 'url':
                if (!/^https?:\/\/.+/i.test(campo.value)) {
                    erro = `O campo "${campo.placeholder || campo.name}" deve ser uma URL válida.`;
                }
                break;
                
            case 'comentario':
            case 'textarea':
                if (campo.value.length > this.TAMANHO_COMENTARIO) {
                    erro = `O campo "${campo.placeholder || campo.name}" não pode ter mais de ${this.TAMANHO_COMENTARIO} caracteres.`;
                }
                break;
        }
        
        if (erro) {
            this.mostrarErro(campo, erro);
            return false;
        } else {
            this.removerErro(campo);
            return true;
        }
    }
    
    /**
     * Valida todo o formulário
     */
    static validarFormulario(formulario) {
        let valido = true;
        
        formulario.querySelectorAll('input, textarea, select').forEach(campo => {
            if (!this.validarCampo(campo)) {
                valido = false;
            }
        });
        
        return valido;
    }
    
    /**
     * Mostra erro em um campo
     */
    static mostrarErro(campo, mensagem) {
        campo.classList.add('is-invalid');
        campo.classList.remove('is-valid');
        
        // Remove feedback anterior
        let feedback = campo.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
        
        // Cria novo feedback
        const div = document.createElement('div');
        div.className = 'invalid-feedback d-block';
        div.style.color = this.COR_ERRO;
        div.style.fontSize = '12px';
        div.style.marginTop = '4px';
        div.textContent = mensagem;
        
        campo.parentNode.appendChild(div);
    }
    
    /**
     * Remove erro de um campo
     */
    static removerErro(campo) {
        campo.classList.remove('is-invalid');
        
        // Remove feedback
        let feedback = campo.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    }
    
    /**
     * Atualiza contador de caracteres em tempo real
     */
    static atualizarContadorCaracteres(campo) {
        let maximo = this.TAMANHO_COMENTARIO;
        
        if (campo.dataset.tamanhoMaximo) {
            maximo = parseInt(campo.dataset.tamanhoMaximo);
        } else if (campo.tagName === 'TEXTAREA') {
            maximo = this.TAMANHO_TEXTO_LONGO;
        }
        
        const atual = campo.value.length;
        let elementoContador = document.getElementById(campo.id + '_contador');
        
        if (!elementoContador) {
            elementoContador = document.createElement('small');
            elementoContador.id = campo.id + '_contador';
            elementoContador.style.display = 'block';
            elementoContador.style.marginTop = '4px';
            elementoContador.style.fontSize = '11px';
            campo.parentNode.appendChild(elementoContador);
        }
        
        const percentual = Math.round((atual / maximo) * 100);
        let cor = '#6c757d';
        
        if (percentual > 90) cor = this.COR_ERRO;
        else if (percentual > 75) cor = '#ffc107';
        
        elementoContador.textContent = `${atual}/${maximo} caracteres`;
        elementoContador.style.color = cor;
    }
    
    /**
     * Mostra notificação elegante
     */
    static mostrarNotificacao(mensagem, tipo = 'info') {
        const alertaDiv = document.createElement('div');
        alertaDiv.className = `alert alert-${tipo === 'erro' ? 'danger' : tipo === 'sucesso' ? 'success' : 'info'} alert-dismissible fade show`;
        alertaDiv.style.position = 'fixed';
        alertaDiv.style.top = '20px';
        alertaDiv.style.right = '20px';
        alertaDiv.style.zIndex = '9999';
        alertaDiv.style.minWidth = '300px';
        alertaDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        
        alertaDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertaDiv);
        
        // Remove após 5 segundos
        setTimeout(() => {
            alertaDiv.remove();
        }, 5000);
    }
    
    /**
     * Sinaliza campo obrigatório visualmente
     */
    static sinalizarObrigatorio(idCampo) {
        const campo = document.getElementById(idCampo);
        if (!campo) return;
        
        // Adiciona asterisco vermelho ao label
        const label = document.querySelector(`label[for="${idCampo}"]`);
        if (label && !label.innerHTML.includes('*')) {
            label.innerHTML += ' <span style="color: ' + this.COR_OBRIGATORIO + ';">*</span>';
        }
        
        // Marca o campo
        campo.dataset.obrigatorio = 'true';
        campo.setAttribute('required', 'required');
    }
    
    /**
     * Remove sinalização de obrigatório
     */
    static dessinalizarObrigatorio(idCampo) {
        const campo = document.getElementById(idCampo);
        if (!campo) return;
        
        const label = document.querySelector(`label[for="${idCampo}"]`);
        if (label) {
            label.innerHTML = label.innerHTML.replace(/<span[^>]*>\*<\/span>/g, '');
        }
        
        campo.removeAttribute('required');
        delete campo.dataset.obrigatorio;
    }
    
    /**
     * Limpa todos os erros de um formulário
     */
    static limparErrosFormulario(idFormulario) {
        const formulario = document.getElementById(idFormulario);
        if (!formulario) return;
        
        formulario.querySelectorAll('.is-invalid').forEach(campo => {
            this.removerErro(campo);
        });
    }
}

/**
 * Exemplo de uso no HTML:
 * 
 * <form id="meuFormulario">
 *     <input type="text" name="nome" placeholder="Nome" required data-tipo="letras">
 *     <input type="email" name="email" placeholder="Email" required data-tipo="email">
 *     <input type="number" name="idade" placeholder="Idade" data-tipo="numero">
 *     <textarea name="comentario" placeholder="Comentário" data-tipo="comentario" data-tamanho-maximo="250"></textarea>
 *     <button type="submit">Enviar</button>
 * </form>
 * 
 * <script>
 *     document.addEventListener('DOMContentLoaded', () => {
 *         ValidadorFormulario.inicializarFormulario('meuFormulario');
 *     });
 * </script>
 */
?>
