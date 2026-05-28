<?php
/**
 * config/base_dados.php
 * 
 * Este ficheiro é a fundação de dados de toda a plataforma KALIYE.
 * Ele configura a ligação central ao motor PostgreSQL, define o fuso horário oficial
 * de Angola/Luanda e estabelece os padrões de segurança e performance para as
 * comunicações via PDO (PHP Data Objects).
 */

// Sincronização Temporal: Definimos o fuso horário para garantir que todos os logs,
// timestamps de criação de projetos e datas de investimento batam certo com a hora local.
date_default_timezone_set('Africa/Luanda');

/**
 * Classe Database (PostgreSQL Signature)
 * Responsável por instanciar e gerir a ligação persistente ao banco de dados.
 */
class Database {
    // Parâmetros de Endereçamento do Servidor SQL.
    private string $host     = "127.0.0.1";
    private string $port     = "5432"; // Porta padrão do PostgreSQL.
    private string $db_name  = "Aksanti Referências"; // Nome exato do banco existente no PostgreSQL.
    private string $username = "postgres";
    private string $password = "5850"; // Segredo de acesso (Deve ser guardado em .env em produção).

    // Propriedade pública que guardará a instância ativa da conexão PDO.
    public $conn;

    /**
     * getConnection()
     * Tenta estabelecer a ligação e configura o comportamento do driver PDO.
     * @return PDO|null Retorna o objeto de conexão pronto a ser usado por outros ficheiros.
     */
    public function getConnection(): ?PDO {
        // Reset da propriedade para garantir que não reutilizamos conexões mortas.
        $this->conn = null;

        try {
            /**
             * CONSTRUÇÃO DO DSN (Data Source Name)
             * Nota Técnica: Para o PostgreSQL, usamos o prefixo 'pgsql:'.
             * Envolvemos o 'dbname' em plicas simples ('') para suportar o espaço no nome do banco físico.
             */
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname='" . $this->db_name . "'";
            
            // Instanciação do objeto PDO. É o método mais seguro e moderno de acesso a dados em PHP.
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Forçamos a codificação UTF-8 a nível de cliente para suportar caracteres acentuados angolanos.
            $this->conn->exec("SET client_encoding TO 'UTF8'");
            
            /**
             * ATRIBUTOS DE PERFORMANCE E SEGURANÇA:
             * 1. ATTR_ERRMODE: Definido para lançar Exceções (PDOException). Isto permite que o nosso analista
             * veja erros SQL detalhados durante o desenvolvimento e que o nosso catch() capture problemas em produção.
             * 2. ATTR_DEFAULT_FETCH_MODE: Usamos FETCH_ASSOC para que os resultados venham como arrays associativos 
             * (ex: ['title' => 'meu projecto']), o que torna o código muito mais legível.
             */
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) { 
            /**
             * TRATAMENTO DE ERRO CRÍTICO
             * Se a base de dados estiver offline, o sistema não deve continuar.
             * O die() mostra a mensagem técnica exata para que o DevOps possa corrigir a ligação rapidamente.
             */
            die("<strong>Falha crítica na ligação à base de dados (PostgreSQL):</strong> " . $exception->getMessage());
        }

        // Retornamos a conexão ativa que será injetada em todos os serviços (post_project, my_projects, etc).
        return $this->conn;
    }
}
?>
