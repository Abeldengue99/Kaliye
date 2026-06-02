<!-- 
COMPONENTE: SINALIZAÇÃO DE CAMPOS OBRIGATÓRIOS
Implementado para lançamento crítico - Kaliye Platform
Data: 02 de Junho de 2026
-->

<?php
/**
 * Função helper para sinalizar campo obrigatório no HTML
 */
function campo_obrigatorio($label = '', $dica = '') {
    $html = '<span class="campo-obrigatorio-badge" title="Este campo é obrigatório">Obrigatório</span>';
    if ($label) {
        $html = "<label class='label-obrigatorio'>$label $html</label>";
    }
    if ($dica) {
        $html .= "<small class='hint'>$dica</small>";
    }
    return $html;
}

/**
 * Função helper para criar um campo com validação
 */
function campo_validado($nome, $tipo = 'text', $obrigatorio = true, $maximo = null, $placeholder = '') {
    $atributos = [
        'id' => $nome,
        'name' => $nome,
        'type' => $tipo,
        'class' => 'form-control',
        'placeholder' => $placeholder,
        'data-tipo' => $tipo
    ];
    
    if ($obrigatorio) {
        $atributos['required'] = 'required';
        $atributos['data-obrigatorio'] = 'true';
    }
    
    if ($maximo) {
        $atributos['maxlength'] = $maximo;
        $atributos['data-tamanho-maximo'] = $maximo;
    }
    
    $atributosHtml = '';
    foreach ($atributos as $chave => $valor) {
        $atributosHtml .= "$chave=\"$valor\" ";
    }
    
    if ($tipo === 'textarea') {
        return "<textarea $atributosHtml></textarea>";
    } else {
        return "<input $atributosHtml>";
    }
}
?>

<!-- EXEMPLO 1: Campo de Texto Simples com Sinalização -->
<div class="form-group">
    <label for="nome" class="label-obrigatorio">
        Nome Completo
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="text" 
        class="form-control" 
        id="nome" 
        name="nome" 
        placeholder="Digite seu nome completo"
        data-tipo="letras"
        required
    >
    <small class="invalid-feedback">O campo "Nome" é obrigatório e deve conter apenas letras.</small>
</div>

<!-- EXEMPLO 2: Campo Numérico -->
<div class="form-group">
    <label for="idade" class="label-obrigatorio">
        Idade
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="text" 
        class="form-control" 
        id="idade" 
        name="idade" 
        placeholder="Digite sua idade"
        data-tipo="numeros-apenas"
        required
    >
    <small class="invalid-feedback">O campo "Idade" deve conter apenas números.</small>
</div>

<!-- EXEMPLO 3: Textarea com Limite de Caracteres -->
<div class="form-group">
    <label for="motivacao" class="label-obrigatorio">
        Por que você quer fazer parte da Kaliye?
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <textarea 
        class="form-control textarea-limitado" 
        id="motivacao" 
        name="motivacao" 
        placeholder="Conte-nos em até 250 caracteres..."
        data-tipo="comentario"
        data-tamanho-maximo="250"
        required
        rows="4"
    ></textarea>
    <div class="contador-caracteres normal" id="motivacao_contador">0/250 caracteres</div>
    <div class="progress-contador">
        <div class="progress-contador-bar normal" id="motivacao_progresso" style="width: 0%"></div>
    </div>
    <small class="invalid-feedback">O campo "Motivação" é obrigatório e não pode ultrapassar 250 caracteres.</small>
</div>

<!-- EXEMPLO 4: Campo Alfanumérico -->
<div class="form-group">
    <label for="codigo">
        Código do Projeto
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="text" 
        class="form-control" 
        id="codigo" 
        name="codigo" 
        placeholder="Ex: PROJ-2026-001"
        data-tipo="alfanumerico"
        required
    >
    <small class="invalid-feedback">O campo "Código" contém caracteres não permitidos.</small>
</div>

<!-- EXEMPLO 5: Campo Email -->
<div class="form-group">
    <label for="email" class="label-obrigatorio">
        Email
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="email" 
        class="form-control" 
        id="email" 
        name="email" 
        placeholder="seu.email@exemplo.com"
        data-tipo="email"
        required
    >
    <small class="invalid-feedback">O campo "Email" deve ser um email válido.</small>
</div>

<!-- EXEMPLO 6: Campo Telefone -->
<div class="form-group">
    <label for="telefone">
        Telefone
        <span class="campo-obrigatorio-badge">Obrigatório</span>
    </label>
    <input 
        type="tel" 
        class="form-control" 
        id="telefone" 
        name="telefone" 
        placeholder="+244 923 456 789"
        data-tipo="telefone"
        required
    >
    <small class="hint">Formato: +244 923 456 789</small>
    <small class="invalid-feedback">O campo "Telefone" deve ser um telefone válido.</small>
</div>

<!-- EXEMPLO 7: Alerta de Validação com Estilo -->
<div class="alerta-validacao erro">
    <div class="alerta-validacao-icone">⚠️</div>
    <div class="alerta-validacao-conteudo">
        <div class="alerta-validacao-titulo">Erro ao Enviar</div>
        <div class="alerta-validacao-mensagem">
            Por favor, corrija os erros abaixo antes de continuar.
        </div>
    </div>
    <button class="alerta-validacao-fechar" onclick="this.parentElement.style.display='none';">×</button>
</div>

<!-- EXEMPLO 8: Alerta de Sucesso -->
<div class="alerta-validacao sucesso">
    <div class="alerta-validacao-icone">✓</div>
    <div class="alerta-validacao-conteudo">
        <div class="alerta-validacao-titulo">Sucesso</div>
        <div class="alerta-validacao-mensagem">
            Os dados foram salvos com sucesso.
        </div>
    </div>
    <button class="alerta-validacao-fechar" onclick="this.parentElement.style.display='none';">×</button>
</div>

<!-- EXEMPLO 9: Formulário Completo com Validações -->
<form id="formularioCadastro">
    <h3>Cadastro de Usuário</h3>
    
    <div class="form-group">
        <label for="nomeCompleto" class="label-obrigatorio">
            Nome Completo
        </label>
        <input 
            type="text" 
            class="form-control" 
            id="nomeCompleto" 
            name="nomeCompleto" 
            placeholder="Digite seu nome"
            data-tipo="letras"
            required
        >
        <small class="invalid-feedback"></small>
    </div>
    
    <div class="form-group">
        <label for="emailCadastro" class="label-obrigatorio">
            Email
        </label>
        <input 
            type="email" 
            class="form-control" 
            id="emailCadastro" 
            name="email" 
            placeholder="seu.email@exemplo.com"
            data-tipo="email"
            required
        >
        <small class="invalid-feedback"></small>
    </div>
    
    <div class="form-group">
        <label for="telefoneCadastro" class="label-obrigatorio">
            Telefone
        </label>
        <input 
            type="tel" 
            class="form-control" 
            id="telefoneCadastro" 
            name="telefone" 
            placeholder="+244 923 456 789"
            data-tipo="telefone"
            required
        >
        <small class="invalid-feedback"></small>
    </div>
    
    <div class="form-group">
        <label for="descricao">
            Descrição (Opcional)
        </label>
        <textarea 
            class="form-control textarea-limitado" 
            id="descricao" 
            name="descricao" 
            placeholder="Máximo 300 caracteres"
            data-tipo="comentario"
            data-tamanho-maximo="300"
            rows="4"
        ></textarea>
        <div class="contador-caracteres normal" id="descricao_contador">0/300 caracteres</div>
        <small class="invalid-feedback"></small>
    </div>
    
    <button type="submit" class="btn btn-primary">Cadastrar</button>
    <button type="reset" class="btn btn-secondary">Limpar</button>
</form>

<script>
    // Inicializa validações quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', () => {
        ValidadorFormulario.inicializarFormulario('formularioCadastro');
    });
</script>
