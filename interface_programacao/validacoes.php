<?php
/**
 * BIBLIOTECA CENTRALIZADA DE VALIDAÇÕES
 * Implementada para lançamento crítico - Kaliye Platform
 * Data: 02 de Junho de 2026
 * 
 * Contém validações para:
 * - Campos obrigatórios
 * - Tipos de dados (número, texto, misto)
 * - Tamanho máximo de caracteres
 * - Padrões regex customizados
 * - Mensagens de erro elegantes
 */

class ValidadorCampos {
    
    // Constantes de tamanho máximo
    const TAMANHO_COMENTARIO_MOTIVACAO = 250;
    const TAMANHO_TEXTO_LONGO_MAXIMO = 300;
    const TAMANHO_TEXTAREA_PADRAO = 500;
    
    // Padrões de validação
    const REGEX_NUMEROS_APENAS = '/^\d+$/';
    const REGEX_LETRAS_APENAS = '/^[a-zA-ZáéíóúãõâêôàäöäöüçÁÉÍÓÚÃÕÂÊÔÀ\s]+$/u';
    const REGEX_ALFANUMERICO = '/^[a-zA-Z0-9áéíóúãõâêôàäöäöüçÁÉÍÓÚÃÕÂÊÔÀ\s\-\.]+$/u';
    const REGEX_EMAIL = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    const REGEX_TELEFONE = '/^[\d\s\-\+\(\)]+$/';
    const REGEX_URL = '/^https?:\/\/.+/i';
    
    /**
     * Valida campo obrigatório
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo para mensagem
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarObrigatorio($valor, $nomeCampo) {
        $valor = trim($valor ?? '');
        
        if (empty($valor)) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' é obrigatório."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida tamanho máximo de campo
     * 
     * @param string $valor Valor do campo
     * @param int $maximo Tamanho máximo permitido
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarTamanhoMaximo($valor, $maximo, $nomeCampo) {
        $tamanho = strlen($valor ?? '');
        
        if ($tamanho > $maximo) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' não pode ter mais de {$maximo} caracteres (você tem {$tamanho})."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida tamanho mínimo de campo
     * 
     * @param string $valor Valor do campo
     * @param int $minimo Tamanho mínimo permitido
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarTamanhoMinimo($valor, $minimo, $nomeCampo) {
        $tamanho = strlen($valor ?? '');
        
        if ($tamanho < $minimo) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve ter pelo menos {$minimo} caracteres."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida se contém apenas números
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarApenasNumeros($valor, $nomeCampo) {
        if (!preg_match(self::REGEX_NUMEROS_APENAS, $valor ?? '')) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve conter apenas números."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida se contém apenas letras
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarApenasLetras($valor, $nomeCampo) {
        if (!preg_match(self::REGEX_LETRAS_APENAS, $valor ?? '')) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve conter apenas letras e espaços."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida alfanumérico (letras, números e alguns caracteres especiais)
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarAlfanumerico($valor, $nomeCampo) {
        if (!preg_match(self::REGEX_ALFANUMERICO, $valor ?? '')) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' contém caracteres não permitidos."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida email
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarEmail($valor, $nomeCampo) {
        if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve ser um email válido."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida telefone
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarTelefone($valor, $nomeCampo) {
        if (!preg_match(self::REGEX_TELEFONE, $valor ?? '')) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve ser um telefone válido."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida URL
     * 
     * @param string $valor Valor do campo
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarURL($valor, $nomeCampo) {
        if (!filter_var($valor, FILTER_VALIDATE_URL)) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' deve ser uma URL válida."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida comentário/texto longo com limite
     * 
     * @param string $valor Valor do campo
     * @param int $maximo Tamanho máximo (padrão 250)
     * @param string $nomeCampo Nome do campo
     * @return array ['valido' => bool, 'erro' => string|null]
     */
    public static function validarComentario($valor, $maximo = self::TAMANHO_COMENTARIO_MOTIVACAO, $nomeCampo = 'Comentário') {
        $valor = trim($valor ?? '');
        
        // Verifica se está vazio
        if (empty($valor)) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' é obrigatório."
            ];
        }
        
        // Verifica tamanho máximo
        if (strlen($valor) > $maximo) {
            return [
                'valido' => false,
                'erro' => "O campo '{$nomeCampo}' não pode ter mais de {$maximo} caracteres."
            ];
        }
        
        return ['valido' => true, 'erro' => null];
    }
    
    /**
     * Valida múltiplos campos com regras customizadas
     * 
     * @param array $campos Array com ['nome_campo' => ['valor' => '', 'regras' => []]]
     * @return array ['valido' => bool, 'erros' => array]
     */
    public static function validarMultiplos($campos) {
        $erros = [];
        
        foreach ($campos as $nomeCampo => $dados) {
            $valor = $dados['valor'] ?? '';
            $regras = $dados['regras'] ?? [];
            
            foreach ($regras as $regra => $parametros) {
                $resultado = null;
                
                switch ($regra) {
                    case 'obrigatorio':
                        $resultado = self::validarObrigatorio($valor, $nomeCampo);
                        break;
                    case 'maximo':
                        $resultado = self::validarTamanhoMaximo($valor, $parametros, $nomeCampo);
                        break;
                    case 'minimo':
                        $resultado = self::validarTamanhoMinimo($valor, $parametros, $nomeCampo);
                        break;
                    case 'numeros':
                        if (!empty($valor)) {
                            $resultado = self::validarApenasNumeros($valor, $nomeCampo);
                        }
                        break;
                    case 'letras':
                        if (!empty($valor)) {
                            $resultado = self::validarApenasLetras($valor, $nomeCampo);
                        }
                        break;
                    case 'alfanumerico':
                        if (!empty($valor)) {
                            $resultado = self::validarAlfanumerico($valor, $nomeCampo);
                        }
                        break;
                    case 'email':
                        if (!empty($valor)) {
                            $resultado = self::validarEmail($valor, $nomeCampo);
                        }
                        break;
                    case 'telefone':
                        if (!empty($valor)) {
                            $resultado = self::validarTelefone($valor, $nomeCampo);
                        }
                        break;
                    case 'url':
                        if (!empty($valor)) {
                            $resultado = self::validarURL($valor, $nomeCampo);
                        }
                        break;
                }
                
                if ($resultado && !$resultado['valido']) {
                    $erros[$nomeCampo] = $resultado['erro'];
                    break;
                }
            }
        }
        
        return [
            'valido' => empty($erros),
            'erros' => $erros
        ];
    }
    
    /**
     * Sanitiza entrada para segurança
     * 
     * @param string $valor Valor a sanitizar
     * @return string Valor sanitizado
     */
    public static function sanitizar($valor) {
        return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Retorna resposta JSON padronizada para erros
 */
function retornarErroValidacao($mensagem, $campo = null) {
    http_response_code(422);
    header('Content-Type: application/json');
    
    $resposta = [
        'sucesso' => false,
        'mensagem' => $mensagem,
        'tipo' => 'validacao_erro'
    ];
    
    if ($campo) {
        $resposta['campo'] = $campo;
    }
    
    echo json_encode($resposta);
    exit;
}

/**
 * Retorna resposta JSON de sucesso
 */
function retornarSucesso($mensagem, $dados = []) {
    http_response_code(200);
    header('Content-Type: application/json');
    
    $resposta = [
        'sucesso' => true,
        'mensagem' => $mensagem,
        'dados' => $dados
    ];
    
    echo json_encode($resposta);
    exit;
}

/**
 * Retorna erro genérico SEM mostrar detalhes da BD
 */
function retornarErroGenerico($mensagem = "Ocorreu um erro ao processar sua solicitação.") {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $resposta = [
        'sucesso' => false,
        'mensagem' => $mensagem,
        'tipo' => 'erro_generico'
    ];
    
    echo json_encode($resposta);
    exit;
}
?>
