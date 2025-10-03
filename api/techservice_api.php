<?php
// ========================================
// API PHP - SISTEMA DE CHAMADOS E PREVENTIVAS
// Estrutura seguindo padrões REST e melhores práticas
// ========================================

// Configuração de Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
//junto com os headers a cima trata a origem e os metodos para verificar se é uma origem autorizada e um method listado
//alguns metodos chamam o options antes da sua execução real quando há uma origem diferente das listadas ou metodos nao autorizados o próprio navegador não deixa as requisicoes continuarem
// o que consegue passar dessa verificação é tratado por outras validações
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ========================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// ========================================
class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'localhost';
    private $database = 'chamado_prev';
    private $username = 'trevo';
    private $password = 'trevo';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// ========================================
// CLASSE DE AUTENTICAÇÃO JWT
// ========================================
class Auth {
    private static $secret_key = 'sua_chave_secreta_muito_forte_aqui_2025';
    private static $algorithm = 'HS256';
    
    public static function generateToken($user_id, $username, $perfil) {
        $payload = [
            'iss' => 'techservice_api',
            'aud' => 'techservice_app',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 horas
            'user_id' => $user_id,
            'username' => $username,
            'perfil' => $perfil
        ];
        
        return self::jwt_encode($payload);
    }
    
    public static function validateToken() {
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $authHeader);
        }
        
        if (!$token) {
            return false;
        }
        
        try {
            $decoded = self::jwt_decode($token);
            if ($decoded->exp < time()) {
                return false;
            }
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // JWT encode simples (em produção, use library como firebase/jwt)
    private static function jwt_encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64url_encode($header);
        $payloadEncoded = self::base64url_encode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$secret_key, true);
        $signatureEncoded = self::base64url_encode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    // JWT decode simples
    private static function jwt_decode($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new Exception('Token inválido');
        }
        
        $payload = json_decode(self::base64url_decode($parts[1]));
        
        // Verificar assinatura
        $signature = self::base64url_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::$secret_key, true));
        if (!hash_equals($signature, $parts[2])) {
            throw new Exception('Assinatura inválida');
        }
        
        return $payload;
    }
    
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

// ========================================
// CLASSE BASE PARA CONTROLLERS
// ========================================
abstract class BaseController {
    protected $db;
    protected $user;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Verificar autenticação (exceto para login)
        
        if ($this->requiresAuth()) {
            $this->user = Auth::validateToken();
            if (!$this->user) {
                $this->sendResponse(401, ['error' => 'Token inválido ou expirado']);
                exit;
            }
        }
    }
    
    protected function requiresAuth() {
        return true; // Override nos controllers que não precisam de auth
    }
    
    protected function sendResponse($status, $data = []) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    protected function getJsonInput() {
        return json_decode(file_get_contents('php://input'), true);
    }
    
    protected function validateRequired($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $this->sendResponse(400, ['error' => "Campo '{$field}' é obrigatório"]);
            }
        }
    }
}

// ========================================
// CONTROLLER DE AUTENTICAÇÃO
// ========================================
class AuthController extends BaseController {
    protected function requiresAuth() {
        return false;
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
        
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['username', 'senha']);
        
        $username = trim($data['username']);
        $senha = trim($data['senha']);
        
        // Buscar usuário
        $stmt = $this->db->prepare("
            SELECT id, username, senha_hash, nome_completo, perfil, ativo 
            FROM usuario 
            WHERE username = ? AND ativo = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($senha, $user['senha_hash'])) {
            $this->sendResponse(401, ['error' => 'Usuário ou senha inválidos']);
        }
        
        // Atualizar último login
        $stmt = $this->db->prepare("UPDATE usuario SET ultimo_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Gerar token
        $token = Auth::generateToken($user['id'], $user['username'], $user['perfil']);
        
        $this->sendResponse(200, [
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nome' => $user['nome_completo'],
                'perfil' => $user['perfil']
            ]
        ]);
    }
}

// ========================================
// CONTROLLER DE LOJAS
// ========================================
class LojaController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (isset($params[0])) {
                    $this->getById($params[0]);
                } else {
                    $this->getAll();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID da loja é obrigatório']);
                }
                $this->update($params[0]);
                break;
            case 'DELETE':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID da loja é obrigatório']);
                }
                $this->delete($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    
    private function getAll() {
        $stmt = $this->db->query("
            SELECT * FROM loja ORDER BY nome ASC
        ");
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getById($id) {
        $stmt = $this->db->prepare("
            SELECT l.*, 
                   COUNT(m.id) as total_maquinas,
                   COUNT(CASE WHEN m.status_operacional = 'ativo' THEN 1 END) as maquinas_ativas
            FROM loja l
            LEFT JOIN maquina m ON l.id = m.loja_id
            WHERE l.id = ?
            GROUP BY l.id
        ");
        $stmt->execute([$id]);
        $loja = $stmt->fetch();
        
        if (!$loja) {
            $this->sendResponse(402, ['error' => 'Loja não encontrada']);
        }
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $loja
        ]);
    }
    
    private function create() {
        
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['nome', 'codigo']);
        
        // Verificar se código já existe
        $stmt = $this->db->prepare("SELECT id FROM loja WHERE codigo = ?");
        $stmt->execute([$data['codigo']]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'Código da loja já existe']);
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loja (nome, codigo, endereco, cidade, estado, cep, telefone, email, responsavel, ativa) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                strtoupper($data['codigo']),
                $data['endereco'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $data['cep'] ?? null,
                $data['telefone'] ?? null,
                $data['email'] ?? null,
                $data['responsavel'] ?? null,
                $data['ativa'] ?? true
            ]);
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Loja criada com sucesso',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao criar loja 2: ' . $e->getMessage()]);
        }
    }
    
    private function update($id) {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['nome', 'codigo']);
        
        // Verificar se loja existe
        $stmt = $this->db->prepare("SELECT id FROM loja WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->sendResponse(404, ['error' => 'Loja não encontrada']);
        }
        
        // Verificar se código já existe em outra loja
        $stmt = $this->db->prepare("SELECT id FROM loja WHERE codigo = ? AND id != ?");
        $stmt->execute([$data['codigo'], $id]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'Código da loja já existe']);
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE loja SET 
                    nome = ?, codigo = ?, endereco = ?, cidade = ?, estado = ?, 
                    cep = ?, telefone = ?, email = ?, responsavel = ?, ativa = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['nome'],
                strtoupper($data['codigo']),
                $data['endereco'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $data['cep'] ?? null,
                $data['telefone'] ?? null,
                $data['email'] ?? null,
                $data['responsavel'] ?? null,
                $data['ativa'] ?? true,
                $id
            ]);
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Loja atualizada com sucesso'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao atualizar loja: ' . $e->getMessage()]);
        }
    }
    
    private function delete($id) {
        // Verificar se existem máquinas vinculadas
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM maquina WHERE loja_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            $this->sendResponse(409, ['error' => 'Não é possível excluir loja com máquinas vinculadas']);
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM loja WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $this->sendResponse(404, ['error' => 'Loja não encontrada']);
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Loja excluída com sucesso'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao excluir loja: ' . $e->getMessage()]);
        }
    }
}

// ========================================
// CONTROLLER DE MÁQUINAS
// ========================================
class MaquinaController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (isset($params[0])) {
                    $this->getById($params[0]);
                } else {
                    $this->getAll();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID da máquina é obrigatório']);
                }
                $this->update($params[0]);
                break;
            case 'DELETE':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID da máquina é obrigatório']);
                }
                $this->delete($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    
    private function getAll() {
        $where = "1=1";
        $params = [];
        
        // Filtros
        if (isset($_GET['loja_id']) && !empty($_GET['loja_id'])) {
            $where .= " AND m.loja_id = ?";
            $params[] = $_GET['loja_id'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where .= " AND m.status_operacional = ?";
            $params[] = $_GET['status'];
        }
        
        if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
            $where .= " AND m.tipo_equipamento = ?";
            $params[] = $_GET['tipo'];
        }
        
        $stmt = $this->db->prepare("
            SELECT m.*, l.nome as loja_nome, l.codigo as loja_codigo,
                   DATEDIFF(m.data_proxima_preventiva, CURDATE()) as dias_restantes_preventiva
            FROM maquina m
            INNER JOIN loja l ON m.loja_id = l.id
            WHERE $where
            ORDER BY m.patrimonio ASC
        ");
        $stmt->execute($params);
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getById($id) {
        $stmt = $this->db->prepare("
            SELECT m.*, l.nome as loja_nome, l.codigo as loja_codigo,
                   DATEDIFF(m.data_proxima_preventiva, CURDATE()) as dias_restantes_preventiva,
                   COUNT(c.id) as total_chamados,
                   COUNT(CASE WHEN c.status IN ('pendente', 'em_andamento') THEN 1 END) as chamados_abertos
            FROM maquina m
            INNER JOIN loja l ON m.loja_id = l.id
            LEFT JOIN chamado c ON m.id = c.maquina_id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$id]);
        $maquina = $stmt->fetch();
        
        if (!$maquina) {
            $this->sendResponse(404, ['error' => 'Máquina não encontrada']);
        }
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $maquina
        ]);
    }
    
    private function create() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['loja_id', 'patrimonio', 'numero_serie']);
        
        // Verificar se patrimônio já existe
        $stmt = $this->db->prepare("SELECT id FROM maquina WHERE patrimonio = ?");
        $stmt->execute([$data['patrimonio']]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'Patrimônio já existe']);
        }
        
        // Verificar se número de série já existe
        $stmt = $this->db->prepare("SELECT id FROM maquina WHERE numero_serie = ?");
        $stmt->execute([$data['numero_serie']]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'Número de série já existe']);
        }
        
        // Calcular próxima preventiva se periodicidade foi informada
        $dataProximaPreventiva = null;
        if (isset($data['periodicidade_preventiva']) && $data['periodicidade_preventiva'] > 0) {
            $dataProximaPreventiva = date('Y-m-d', strtotime("+{$data['periodicidade_preventiva']} days"));
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO maquina (
                    loja_id, patrimonio, numero_serie, modelo, marca, tipo_equipamento,
                    data_aquisicao, valor_aquisicao, status_operacional, localizacao, observacoes,
                    periodicidade_preventiva, data_proxima_preventiva
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['loja_id'],
                strtoupper($data['patrimonio']),
                $data['numero_serie'],
                $data['modelo'] ?? null,
                $data['marca'] ?? null,
                $data['tipo_equipamento'] ?? null,
                $data['data_aquisicao'] ?? null,
                $data['valor_aquisicao'] ?? null,
                $data['status_operacional'] ?? 'ativo',
                $data['localizacao'] ?? null,
                $data['observacoes'] ?? null,
                $data['periodicidade_preventiva'] ?? null,
                $dataProximaPreventiva
            ]);
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Máquina cadastrada com sucesso',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao cadastrar máquina: ' . $e->getMessage()]);
        }
    }
    
    private function update($id) {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['patrimonio', 'numero_serie']);
        
        // Verificar se máquina existe
        $stmt = $this->db->prepare("SELECT id FROM maquina WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $this->sendResponse(404, ['error' => 'Máquina não encontrada']);
        }
        
        // Calcular próxima preventiva se periodicidade mudou
        $dataProximaPreventiva = null;
        if (isset($data['periodicidade_preventiva']) && $data['periodicidade_preventiva'] > 0) {
            $dataProximaPreventiva = date('Y-m-d', strtotime("+{$data['periodicidade_preventiva']} days"));
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE maquina SET 
                    patrimonio = ?, numero_serie = ?, modelo = ?, marca = ?, tipo_equipamento = ?,
                    data_aquisicao = ?, valor_aquisicao = ?, status_operacional = ?, localizacao = ?, 
                    observacoes = ?, periodicidade_preventiva = ?, data_proxima_preventiva = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                strtoupper($data['patrimonio']),
                $data['numero_serie'],
                $data['modelo'] ?? null,
                $data['marca'] ?? null,
                $data['tipo_equipamento'] ?? null,
                $data['data_aquisicao'] ?? null,
                $data['valor_aquisicao'] ?? null,
                $data['status_operacional'] ?? 'ativo',
                $data['localizacao'] ?? null,
                $data['observacoes'] ?? null,
                $data['periodicidade_preventiva'] ?? null,
                $dataProximaPreventiva,
                $id
            ]);
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Máquina atualizada com sucesso'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao atualizar máquina: ' . $e->getMessage()]);
        }
    }
    
    private function delete($id) {
        // Verificar se existem chamados vinculados
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM chamado WHERE maquina_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            $this->sendResponse(409, ['error' => 'Não é possível excluir máquina com chamados vinculados']);
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM maquina WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $this->sendResponse(404, ['error' => 'Máquina não encontrada']);
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Máquina excluída com sucesso'
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao excluir máquina: ' . $e->getMessage()]);
        }
    }
}

// ========================================
// CONTROLLER DE CHAMADOS
// ========================================
class ChamadoController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (isset($params[0])) {
                    if ($params[0] === 'dashboard') {
                        $this->getDashboard();
                    } else {
                        $this->getById($params[0]);
                    }
                } else {
                    $this->getAll();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID do chamado é obrigatório']);
                }
                $this->update($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    
    private function getDashboard() {
        $stmt = $this->db->query("
            SELECT 
                l.nome as loja_nome,
                COUNT(CASE WHEN c.status = 'pendente' THEN 1 END) as pendentes,
                COUNT(CASE WHEN c.status = 'em_andamento' THEN 1 END) as em_andamento,
                COUNT(CASE WHEN c.status = 'concluido' THEN 1 END) as concluidos,
                COUNT(*) as total
            FROM loja l
            LEFT JOIN chamado c ON l.id = c.loja_id 
                AND c.data_abertura >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE l.ativa = 1
            GROUP BY l.id, l.nome
        ");
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getAll() {
        $where = "1=1";
        $params = [];
        
        // Filtros
        if (isset($_GET['loja_id']) && !empty($_GET['loja_id'])) {
            $where .= " AND c.loja_id = ?";
            $params[] = $_GET['loja_id'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where .= " AND c.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (isset($_GET['prioridade']) && !empty($_GET['prioridade'])) {
            $where .= " AND c.prioridade = ?";
            $params[] = $_GET['prioridade'];
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, l.nome as loja_nome, m.patrimonio, ua.nome_completo as usuario_abertura,
                   ut.nome_completo as tecnico_responsavel,
                   TIMESTAMPDIFF(HOUR, c.data_abertura, COALESCE(c.data_conclusao, NOW())) as horas_em_aberto
            FROM chamado c
            INNER JOIN loja l ON c.loja_id = l.id
            INNER JOIN maquina m ON c.maquina_id = m.id
            INNER JOIN usuario ua ON c.usuario_abertura_id = ua.id
            LEFT JOIN usuario ut ON c.usuario_tecnico_id = ut.id
            WHERE $where
            ORDER BY c.data_abertura DESC
        ");
        $stmt->execute($params);
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, l.nome as loja_nome, m.patrimonio, m.numero_serie,
                   ua.nome_completo as usuario_abertura, ut.nome_completo as tecnico_responsavel,
                   TIMESTAMPDIFF(HOUR, c.data_abertura, COALESCE(c.data_conclusao, NOW())) as horas_em_aberto
            FROM chamado c
            INNER JOIN loja l ON c.loja_id = l.id
            INNER JOIN maquina m ON c.maquina_id = m.id
            INNER JOIN usuario ua ON c.usuario_abertura_id = ua.id
            LEFT JOIN usuario ut ON c.usuario_tecnico_id = ut.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $chamado = $stmt->fetch();
        
        if (!$chamado) {
            $this->sendResponse(404, ['error' => 'Chamado não encontrado']);
        }
        
        // Buscar anexos
        $stmt = $this->db->prepare("
            SELECT id, nome_original, tipo_arquivo, caminho_arquivo, tipo_anexo, descricao, data_upload
            FROM chamado_anexo 
            WHERE chamado_id = ?
            ORDER BY data_upload ASC
        ");
        $stmt->execute([$id]);
        $chamado['anexos'] = $stmt->fetchAll();
        
        // Buscar histórico
        $stmt = $this->db->prepare("
            SELECT h.*, u.nome_completo as usuario_nome
            FROM chamado_historico h
            INNER JOIN usuario u ON h.usuario_id = u.id
            WHERE h.chamado_id = ?
            ORDER BY h.data_alteracao DESC
        ");
        $stmt->execute([$id]);
        $chamado['historico'] = $stmt->fetchAll();
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $chamado
        ]);
    }
    
    private function create() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['loja_id', 'maquina_id', 'titulo', 'descricao']);
        
        try {
            $this->db->beginTransaction();
            
            // Inserir chamado
            $stmt = $this->db->prepare("
                INSERT INTO chamado (
                    loja_id, maquina_id, usuario_abertura_id, titulo, descricao, 
                    categoria, prioridade, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')
            ");
            
            $stmt->execute([
                $data['loja_id'],
                $data['maquina_id'],
                $this->user->user_id,
                $data['titulo'],
                $data['descricao'],
                $data['categoria'] ?? null,
                $data['prioridade'] ?? 'media'
            ]);
            
            $chamadoId = $this->db->lastInsertId();
            
            // Processar anexos se existirem
            if (isset($data['anexos']) && !empty($data['anexos'])) {
                foreach ($data['anexos'] as $anexo) {
                    $stmt = $this->db->prepare("
                        INSERT INTO chamado_anexo (
                            chamado_id, nome_arquivo, nome_original, tipo_arquivo, 
                            tamanho_bytes, caminho_arquivo, tipo_anexo, descricao
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $chamadoId,
                        $anexo['nome_arquivo'],
                        $anexo['nome_original'],
                        $anexo['tipo_arquivo'],
                        $anexo['tamanho_bytes'],
                        $anexo['caminho_arquivo'],
                        $anexo['tipo_anexo'],
                        $anexo['descricao'] ?? null
                    ]);
                }
            }
            
            $this->db->commit();
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Chamado criado com sucesso',
                'data' => ['id' => $chamadoId]
            ]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao criar chamado: ' . $e->getMessage()]);
        }
    }
    
    private function update($id) {
        $data = $this->getJsonInput();
        
        // Verificar se chamado existe
        $stmt = $this->db->prepare("SELECT id, status FROM chamado WHERE id = ?");
        $stmt->execute([$id]);
        $chamadoAtual = $stmt->fetch();
        
        if (!$chamadoAtual) {
            $this->sendResponse(404, ['error' => 'Chamado não encontrado']);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Campos que podem ser atualizados
            $campos = [];
            $valores = [];
            
            if (isset($data['status'])) {
                $campos[] = 'status = ?';
                $valores[] = $data['status'];
                
                // Atualizar datas específicas baseadas no status
                if ($data['status'] === 'em_andamento' && $chamadoAtual['status'] === 'pendente') {
                    $campos[] = 'data_inicio_atendimento = NOW()';
                    $campos[] = 'usuario_tecnico_id = ?';
                    $valores[] = $this->user->user_id;
                } elseif ($data['status'] === 'concluido') {
                    $campos[] = 'data_conclusao = NOW()';
                }
            }
            
            if (isset($data['diagnostico'])) {
                $campos[] = 'diagnostico = ?';
                $valores[] = $data['diagnostico'];
            }
            
            if (isset($data['solucao'])) {
                $campos[] = 'solucao = ?';
                $valores[] = $data['solucao'];
            }
            
            if (isset($data['tempo_gasto'])) {
                $campos[] = 'tempo_gasto = ?';
                $valores[] = $data['tempo_gasto'];
            }
            
            if (isset($data['custo_servico'])) {
                $campos[] = 'custo_servico = ?';
                $valores[] = $data['custo_servico'];
            }
            
            if (isset($data['custo_pecas'])) {
                $campos[] = 'custo_pecas = ?';
                $valores[] = $data['custo_pecas'];
            }
            
            if (!empty($campos)) {
                $valores[] = $id;
                $sql = "UPDATE chamado SET " . implode(', ', $campos) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($valores);
            }
            
            $this->db->commit();
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Chamado atualizado com sucesso'
            ]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao atualizar chamado: ' . $e->getMessage()]);
        }
    }
}

// ========================================
// CONTROLLER DE PREVENTIVAS
// ========================================
class PreventivaController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (isset($params[0]) && $params[0] === 'proximas') {
                    $this->getProximas();
                } elseif (isset($params[0])) {
                    $this->getById($params[0]);
                } else {
                    $this->getAll();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID da preventiva é obrigatório']);
                }
                $this->update($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    private function getById($id) {}
    private function update($params) {}
    private function getProximas() {
        $stmt = $this->db->query("
            SELECT l.nome as loja_nome,
                   m.patrimonio, m.numero_serie, m.modelo,
                   m.data_proxima_preventiva,
                   DATEDIFF(m.data_proxima_preventiva, CURDATE()) as dias_restantes,
                   CASE 
                       WHEN DATEDIFF(m.data_proxima_preventiva, CURDATE()) <= 0 THEN 'Vencida'
                       WHEN DATEDIFF(m.data_proxima_preventiva, CURDATE()) <= 7 THEN 'Urgente'
                       WHEN DATEDIFF(m.data_proxima_preventiva, CURDATE()) <= 15 THEN 'Próxima'
                       ELSE 'Normal'
                   END as status_preventiva
            FROM maquina m
            INNER JOIN loja l ON m.loja_id = l.id
            WHERE m.status_operacional = 'ativo' 
                AND m.data_proxima_preventiva IS NOT NULL
                AND l.ativa = 1
            ORDER BY m.data_proxima_preventiva ASC
        ");
        
        // Agrupar por loja
        $preventivas = $stmt->fetchAll();
        $resultado = [];
        
        foreach ($preventivas as $preventiva) {
            $loja = $preventiva['loja_nome'];
            if (!isset($resultado[$loja])) {
                $resultado[$loja] = [
                    'nome' => $loja,
                    'maquinas' => []
                ];
            }
            $resultado[$loja]['maquinas'][] = $preventiva;
        }
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => array_values($resultado)
        ]);
    }
    
    private function getAll() {
        $where = "1=1";
        $params = [];
        
        if (isset($_GET['maquina_id']) && !empty($_GET['maquina_id'])) {
            $where .= " AND p.maquina_id = ?";
            $params[] = $_GET['maquina_id'];
        }
        
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where .= " AND p.status = ?";
            $params[] = $_GET['status'];
        }
        
        $stmt = $this->db->prepare("
            SELECT p.*, m.patrimonio, l.nome as loja_nome,
                   u.nome_completo as tecnico_nome
            FROM preventiva p
            INNER JOIN maquina m ON p.maquina_id = m.id
            INNER JOIN loja l ON m.loja_id = l.id
            LEFT JOIN usuario u ON p.usuario_tecnico_id = u.id
            WHERE $where
            ORDER BY p.data_programada DESC
        ");
        $stmt->execute($params);
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function create() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['maquina_id', 'tipo', 'data_programada']);
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO preventiva (
                    maquina_id, usuario_tecnico_id, tipo, data_programada, 
                    observacoes, status
                ) VALUES (?, ?, ?, ?, ?, 'programada')
            ");
            
            $stmt->execute([
                $data['maquina_id'],
                $data['usuario_tecnico_id'] ?? null,
                $data['tipo'],
                $data['data_programada'],
                $data['observacoes'] ?? null
            ]);
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Preventiva programada com sucesso',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao programar preventiva: ' . $e->getMessage()]);
        }
    }
}

// ========================================
// CONTROLLER DE USUÁRIOS
// ========================================
class UsuarioController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (isset($params[0])) {
                    $this->getById($params[0]);
                } else {
                    $this->getAll();
                }
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID do usuário é obrigatório']);
                }
                $this->update($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    private function getById($id) {}
    private function update($params) {}
    private function getAll() {
        // Apenas admins podem listar todos os usuários
        if ($this->user->perfil !== 'admin') {
            $this->sendResponse(403, ['error' => 'Acesso negado']);
        }
        
        $stmt = $this->db->query("
            SELECT id, username, nome_completo, email, telefone, perfil, ativo, ultimo_login,
                   data_criacao
            FROM usuario
            ORDER BY nome_completo ASC
        ");
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function create() {
        // Apenas admins podem criar usuários
        if ($this->user->perfil !== 'admin') {
            $this->sendResponse(403, ['error' => 'Acesso negado']);
        }
        
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['username', 'email', 'senha', 'nome_completo', 'perfil']);
        
        // Verificar se username já existe
        $stmt = $this->db->prepare("SELECT id FROM usuario WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'Nome de usuário já existe']);
        }
        
        // Verificar se email já existe
        $stmt = $this->db->prepare("SELECT id FROM usuario WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $this->sendResponse(409, ['error' => 'E-mail já existe']);
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO usuario (username, email, senha_hash, nome_completo, telefone, perfil, ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $data['email'],
                password_hash($data['senha'], PASSWORD_BCRYPT),
                $data['nome_completo'],
                $data['telefone'] ?? null,
                $data['perfil'],
                $data['ativo'] ?? true
            ]);
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao criar usuário: ' . $e->getMessage()]);
        }
    }
}

// ========================================
// ROUTER PRINCIPAL
// ========================================
class ApiRouter {
    private $routes = [
        'authlogin' => ['controller' => 'AuthController', 'method' => 'login'],
        'lojas' => ['controller' => 'LojaController', 'method' => 'handleRequest'],
        'maquinas' => ['controller' => 'MaquinaController', 'method' => 'handleRequest'],
        'chamados' => ['controller' => 'ChamadoController', 'method' => 'handleRequest'],
        'preventivas' => ['controller' => 'PreventivaController', 'method' => 'handleRequest'],
        'usuarios' => ['controller' => 'UsuarioController', 'method' => 'handleRequest']
    ];
    
    public function route() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // ✅ Remover base do projeto (chamados_tech)
        $uri = preg_replace('#^chamados_tech/#', '', $uri);
        
        // ✅ Remover pasta api/
        $uri = preg_replace('#^api/#', '', $uri);
        
        // ✅ Remover nome do arquivo PHP
        $uri = preg_replace('#^techservice_api\.php/?#', '', $uri);
        
        // ✅ Remover prefixo api/vX se existir
        $uri = preg_replace('#^api(/v\d+)?/#', '', $uri);
        
        // Debug (remover depois)
        error_log("URI processada: " . $uri);
        
        $segments = explode('/', $uri);
        $resource = $segments[0] ?? '';
        $params = array_slice($segments, 1);
        
        // Tratar rotas especiais
        if ($resource === 'auth' && isset($segments[1])) {
            $routeKey = $resource . '/' . $segments[1];
            $params = array_slice($segments, 2);
        } else {
            $routeKey = $resource;
        }
        
        if (!isset($this->routes[$routeKey])) {
            $this->sendErrorResponse(404, 'Endpoint não encontrado');
        }
        
        $route = $this->routes[$routeKey];
        $controllerClass = $route['controller'];
        $method = $route['method'];
        
        try {
            $controller = new $controllerClass();
            
            if ($method === 'handleRequest') {
                $controller->$method($_SERVER['REQUEST_METHOD'], $params);
            } else {
                $controller->$method();
            }
        } catch (Exception $e) {
            $this->sendErrorResponse(500, 'Erro interno do servidor: ' . $e->getMessage());
        }
    }
    
    private function sendErrorResponse($status, $message) {
        http_response_code($status);
        echo json_encode(['error' => $message]);
        exit;
    }
}

// ========================================
// TRATAMENTO DE ERROS GLOBAL
// ========================================
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $exception->getMessage(),
        'debug' => [
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]
    ]);
});

// ========================================
// INICIALIZAÇÃO DA API
// ========================================
try {
    $router = new ApiRouter();
    $router->route();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na inicialização da API: ' . $e->getMessage()]);
}

?>