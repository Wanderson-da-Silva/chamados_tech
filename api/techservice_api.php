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

//horario brazil/brasil
date_default_timezone_set('America/Sao_Paulo');

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

    protected function moverImagens($idChamado, $arquivos = null){
        // Valida se o ID foi enviado
        if (empty($idChamado)) {
            return [
                'success' => false,
                'message' => 'foto(s) - idChamado inexistente!'
            ];
        }

        // Se não passou arquivos, tenta pegar do $_FILES
        if ($arquivos === null) {
            //$arquivos = $_FILES['fotos'] ?? null;


            return [
                'success' => false,
                'error' => 'foto(s) inexistente io nula !'
            ];
        }

        // Verifica se há arquivos enviados
        if (empty($arquivos) || empty($arquivos['name'][0])) {
            return [
                'success' => false,
                'error' => 'foto(s) inexistente io !'
            ];
        }

        // Resto do código continua igual...
        $pastaDestino = 'fotos/';
        
        if (!file_exists($pastaDestino)) {
            mkdir($pastaDestino, 0777, true);
        }

        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $tamanhoMaximo = 5 * 1024 * 1024;
        
        $arquivosEnviados = [];
        $erros = [];
        
        $totalArquivos = count($arquivos['name']);

        for ($i = 0; $i < $totalArquivos; $i++) {
            if ($arquivos['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            if ($arquivos['error'][$i] !== UPLOAD_ERR_OK) {
                $erros[] = "Erro ao fazer upload de '{$arquivos['name'][$i]}'.";
                continue;
            }
            
            $nomeOriginal = $arquivos['name'][$i];
            $tmpName = $arquivos['tmp_name'][$i];
            $tamanho = $arquivos['size'][$i];
            $tipoArquivo = $arquivos['type'][$i];
            $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            
            if (!in_array($extensao, $extensoesPermitidas)) {
                $erros[] = "'{$nomeOriginal}': formato não permitido.";
                continue;
            }
            
            $verificaImagem = getimagesize($tmpName);
            if ($verificaImagem === false) {
                $erros[] = "'{$nomeOriginal}': não é uma imagem válida.";
                continue;
            }
            
            if ($tamanho > $tamanhoMaximo) {
                $erros[] = "'{$nomeOriginal}': excede o tamanho máximo de 5MB.";
                continue;
            }
            
            $dataAtual = date('Y-m-d_H-i-s');
            $nomeGerado = uniqid() . '_' . time() . '_' . $i;
            $nomeArquivo = $idChamado . '_' . $dataAtual . '_' . $nomeGerado . '.' . $extensao;
            $caminhoCompleto = $pastaDestino . $nomeArquivo;
            
            if (move_uploaded_file($tmpName, $caminhoCompleto)) {
            // ✅ Monta array com TODOS os dados necessários para INSERT
                $arquivosEnviados[] = [
                    'nome_arquivo' => $nomeArquivo,           // Nome no servidor
                    'nome_original' => $nomeOriginal,          // Nome original do usuário
                    'tipo_arquivo' => $tipoArquivo,            // MIME type (image/jpeg)
                    'tamanho_bytes' => $tamanho,               // Tamanho em bytes
                    'caminho_arquivo' => $caminhoCompleto,     // Caminho completo
                    'tipo_anexo' => 'foto',                    // Tipo (foto/video/documento)
                    'descricao' => null                        // Descrição (opcional)
                ];
            } else {
                $erros[] = "'{$nomeOriginal}': erro ao salvar no servidor.";
            }
        }

        if (count($arquivosEnviados) > 0) {
            $resposta = [
                'success' => true,
                'message' => count($arquivosEnviados) . ' foto(s) enviada(s) com sucesso!',
                'total_enviadas' => count($arquivosEnviados),
                'arquivos' => $arquivosEnviados
            ];
            
            if (count($erros) > 0) {
                $resposta['avisos'] = $erros;
                $resposta['message'] .= ' Alguns arquivos não foram enviados.';
            }
            
            return $resposta;
        } else {
            return [
                'success' => false,
                'error' => 'Nenhuma foto foi enviada!',
                'avisos' => $erros
            ];
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
// CONTROLLER DE LOJAS
// ========================================
class AnexoController extends BaseController {
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                if (empty($params[0]) && empty($_GET)) { 
                    $this->getAll();
                } 
                else {
                    if ($params[0] === 'deChamado') {
                        // Pega o ID do próximo parâmetro
                        $chamadoId = isset($params[1]) ? $params[1] : null;
                        
                        if ($chamadoId) {
                            $this->getAnexoChamado($chamadoId);
                        } else {
                            $this->sendResponse(400, ['error' => 'ID do chamado não fornecido']);
                        }
                    } elseif($params[0] === 'dePrevent'){
                         // Pega o ID do próximo parâmetro
                        $preventivaId = isset($params[1]) ? $params[1] : null;
                        
                        if ($preventivaId) {
                            $this->getAnexoPreventiva($preventivaId);
                        } else {
                            $this->sendResponse(400, ['error' => 'ID da preventiva não fornecido']);
                        }
                    }
                    else {
                        $this->getById($params[0]);
                    }
                } 
                break;
                
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID do anexo é obrigatório']);
                }
                $this->update($params[0]);
                break;
            case 'DELETE':
                if (!isset($params[0])) {
                    $this->sendResponse(400, ['error' => 'ID do anexo é obrigatório']);
                }
                $this->delete($params[0]);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    
    private function getAll() {
        $stmt = $this->db->query("
            SELECT * FROM chamado_anexo ORDER BY nome_arquivo ASC
        ");
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getAnexoChamado($chamadoId){

    try{
                $stmt = $this->db->prepare("SELECT chamado_id, nome_arquivo, caminho_arquivo, data_upload 
            FROM chamado_anexo 
            WHERE chamado_id = ? 
            ORDER BY data_upload DESC");
    
        $stmt->execute([$chamadoId]);
        $result = $stmt->fetchAll();
               
                if ($result === false ) {
                     $this->sendResponse(200, [
                        'success' => false,
                        'message' => 'Sem anexo para chamado'
                    ]);
                } 

        
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        

    } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao encontrar anexos: ' . $e->getMessage()]);
        }
        

    }
    private function getAnexoPreventiva($preventivaId){

        try{
                    $stmt = $this->db->prepare("SELECT preventiva_id, nome_arquivo, caminho_arquivo, data_upload 
                FROM preventiva_anexo 
                WHERE preventiva_id = ? 
                ORDER BY data_upload DESC");
        
            $stmt->execute([$preventivaId]);
            $result = $stmt->fetchAll();
                
                    if ($result === false ) {
                        $this->sendResponse(200, [
                            'success' => false,
                            'message' => 'Sem anexo para preventiva'
                        ]);
                    } 

            
                $this->sendResponse(200, [
                    'success' => true,
                    'data' => $result
                ]);
            

        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao encontrar anexos: ' . $e->getMessage()]);
        }
        
    }

    private function getById($id) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM chamado_anexo ca
            LEFT JOIN chamado c ON c.id = ca.id
            WHERE ca.id = ?
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
        $this->validateRequired($data, ['nome_arquivo', 'chamado_id']);
        
        // Verificar se código já existe
        $chamadoId = $data['chamadoId'];
        
        try {
        // ✅ PROCESSAR FOTOS
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $resultadoFotos = $this->moverImagens($chamadoId, $_FILES['fotos']);
                
                if (!$resultadoFotos['success']) {
                    $this->db->rollBack();
                    $this->sendResponse(500, $resultadoFotos);
                    return;
                }
                
                // ✅ INSERIR ANEXOS NO BANCO - apenas os que foram enviados com sucesso
                if (!empty($resultadoFotos['arquivos'])) {
                    foreach ($resultadoFotos['arquivos'] as $anexo) {
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
            }
            
            $this->db->commit();
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Chamado criado com sucesso',
                'data' => [
                    'id' => $chamadoId,
                    'fotos_enviadas' => $resultadoFotos['total_enviadas'] ?? 0,
                    'avisos' => $resultadoFotos['avisos'] ?? []
                ]
            ]);

        } catch (PDOException $e) {
            $this->db->rollBack();
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
                    if ($params[0] === 'semPrev') {
                        $this->getMaquinasSemPreventiva();
                    } 
                    elseif ($params[0] === 'semChamad') {
                        $this->getMaquinasSemChamado();
                    }
                    else {
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
    private function getMaquinasSemPreventiva() {
        try {        
            $stmt = $this->db->prepare("
            SELECT DISTINCT m.id, m.patrimonio
            FROM maquina m
            LEFT JOIN preventiva p_ativas ON m.id = p_ativas.maquina_id 
                AND p_ativas.status IN ('programada', 'em_andamento')
            WHERE p_ativas.id IS NULL
            ORDER BY m.patrimonio ASC
            ");
            $stmt->execute();
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $stmt->fetchAll(),
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao buscar máquina sem preventiva: ' . $e->getMessage()]);
        }

    }
    private function getMaquinasSemChamado() {
        try {        
            $stmt = $this->db->prepare("
            SELECT DISTINCT 
                m.id, 
                m.patrimonio,
                m.loja_id,
                l.nome AS loja_nome,
                l.codigo AS loja_codigo
            FROM maquina m
            INNER JOIN loja l ON m.loja_id = l.id
            LEFT JOIN chamado c_ativas ON m.id = c_ativas.maquina_id 
                AND c_ativas.status IN ('pendente','em_andamento','aguardando_peca','pausado')
            WHERE c_ativas.id IS NULL
            ORDER BY m.patrimonio ASC
            ");
            $stmt->execute();
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $stmt->fetchAll(),
            ]);
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao buscar máquina sem chamado: ' . $e->getMessage()]);
        }

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
                // Se não existir parâmetro específico na URL (ex: id ou 'dashboard'), use getAll()
                if (empty($params[0]) && empty($_GET)) { 
                    $this->getAll();
                } 
                elseif (isset($params[0])) {
                    if ($params[0] === 'dashboard') {
                        $this->getDashboard();
                    } else {
                        $this->getById($params[0]);
                    }
                } 
                else {
                    // Aqui, quando há parâmetros via $_GET mas sem $params[0], pode-se usar um método para filtros
                    $this->getAllFiltered($_GET);
                }
            break;

            case 'POST':
                // ✅ Verificar se é um POST verdadeiro ou um PUT simulado
                if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
                    // É um PUT simulado
                    if (!isset($params[0])) {
                        $this->sendResponse(400, ['error' => 'ID do chamado é obrigatório']);
                    }
                    $this->update($params[0]);
                } else {
                    // É um POST verdadeiro
                    $this->create();
                }
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
        try{
        $stmt = $this->db->query("
            SELECT * FROM vw_dashboard_chamados_por_tipo
        ");
        
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);

        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao gerar dash: ' . $e->getMessage()]);
        }

    }
    
    private function getAll() {
        
    
        $stmt = $this->db->prepare("
            SELECT c.*, l.nome as loja_nome, m.patrimonio, ua.nome_completo as usuario_abertura,
                   ut.nome_completo as tecnico_responsavel,
                   TIMESTAMPDIFF(HOUR, c.data_abertura, COALESCE(c.data_conclusao, NOW())) as horas_em_aberto
            FROM chamado c
            INNER JOIN loja l ON c.loja_id = l.id
            INNER JOIN maquina m ON c.maquina_id = m.id
            INNER JOIN usuario ua ON c.usuario_abertura_id = ua.id
            LEFT JOIN usuario ut ON c.usuario_tecnico_id = ut.id
            ORDER BY c.data_abertura DESC
        ");
        $stmt->execute();
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }
    
    private function getAllFiltered() {
    
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
            SELECT c.*, l.nome as loja_nome, m.patrimonio as patrimonio, m.numero_serie as serie, m.modelo,
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
        
        // // Buscar anexos
        // $stmt = $this->db->prepare("
        //     SELECT id, nome_original, tipo_arquivo, caminho_arquivo, tipo_anexo, descricao, data_upload
        //     FROM chamado_anexo 
        //     WHERE chamado_id = ?
        //     ORDER BY data_upload ASC
        // ");
        // $stmt->execute([$id]);
        // $chamado['anexos'] = $stmt->fetchAll();
        
        // // Buscar histórico
        // $stmt = $this->db->prepare("
        //     SELECT h.*, u.nome_completo as usuario_nome
        //     FROM chamado_historico h
        //     INNER JOIN usuario u ON h.usuario_id = u.id
        //     WHERE h.chamado_id = ?
        //     ORDER BY h.data_alteracao DESC
        // ");
        // $stmt->execute([$id]);
        // $chamado['historico'] = $stmt->fetchAll();
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $chamado
        ]);
    }
    
    private function create() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
        } else {
            $data = $this->getJsonInput();
        }
        
        $this->validateRequired($data, ['loja_id', 'maquina_id', 'problema']);
        
        try {
            $this->db->beginTransaction();
            
            // Inserir chamado
            $stmt = $this->db->prepare("
                INSERT INTO chamado (
                    loja_id, maquina_id, usuario_abertura_id, descricao, 
                    categoria, prioridade, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'pendente')
            ");
            
            $stmt->execute([
                $data['loja_id'],
                $data['maquina_id'],
                $this->user->user_id,
                $data['problema'],
                $data['categoria'] ?? null,
                $data['prioridade'] ?? 'media'
            ]);
            
            $chamadoId = $this->db->lastInsertId();
            
            // ✅ PROCESSAR FOTOS
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $resultadoFotos = $this->moverImagens($chamadoId, $_FILES['fotos']);
                
                if (!$resultadoFotos['success']) {
                    $this->db->rollBack();
                    $this->sendResponse(500, $resultadoFotos);
                    return;
                }
                
                // ✅ INSERIR ANEXOS NO BANCO - apenas os que foram enviados com sucesso
                if (!empty($resultadoFotos['arquivos'])) {
                    foreach ($resultadoFotos['arquivos'] as $anexo) {
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
            }
            
            $this->db->commit();
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Chamado criado com sucesso',
                'data' => [
                    'id' => $chamadoId,
                    'fotos_enviadas' => $resultadoFotos['total_enviadas'] ?? 0,
                    'avisos' => $resultadoFotos['avisos'] ?? []
                ]
            ]);
        
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao criar chamado: ' . $e->getMessage()]);
        }
    }
    
    private function update($id) {

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
        } else {
            $data = $this->getJsonInput();
        }
        
        try {
            // Verificar se chamado existe
            $stmt = $this->db->prepare("SELECT id, status FROM chamado WHERE id = ?");
            $stmt->execute([$id]);
            $chamadoAtual = $stmt->fetch();
            
            if (!$chamadoAtual) {
                $this->sendResponse(404, ['error' => 'Chamado não encontrado']);
            }
        
            $this->db->beginTransaction();
            
            // Campos que podem ser atualizados
            $campos = [];
            $valores = [];
        /*
            $sql = '';
            $sqlFinal = '';
            */
            if (isset($data['status'])) {
                $campos[] = 'status = ?';
                $valores[] = $data['status'];
                
                // Atualizar datas específicas baseadas no status
                if ($data['status'] === 'em_andamento' && $chamadoAtual['status'] === 'pendente') {
                    $campos[] = 'data_inicio_atendimento = NOW() ';
                    $campos[] = 'usuario_tecnico_id = ?';
                    $valores[] = $data['usuario_tecnico_id'];
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
            if (isset($this->user->user_id)) {
                $campos[] = 'usuario_ultima_atualizacao = ?';
                $valores[] = $this->user->user_id;
            }
           
            // ✅ PROCESSAR FOTOS
            if (isset($_FILES['conclusao-fotos']) && !empty($_FILES['conclusao-fotos']['name'][0])) {
                // $resultadoFotos = $this->moverImagens($chamadoAtual, $_FILES['conclusao-fotos']);
                $resultadoFotos = $this->moverImagens($chamadoAtual['id'], $_FILES['conclusao-fotos']);
                                                        
                if (!$resultadoFotos['success']) {
                    $this->db->rollBack();
                    $this->sendResponse(500, $resultadoFotos);
                    return;
                }
                
                // ✅ INSERIR ANEXOS NO BANCO - apenas os que foram enviados com sucesso
                if (!empty($resultadoFotos['arquivos'])) {
                    foreach ($resultadoFotos['arquivos'] as $anexo) {
                        $stmt = $this->db->prepare("
                            INSERT INTO chamado_anexo (
                                chamado_id, nome_arquivo, nome_original, tipo_arquivo, 
                                tamanho_bytes, caminho_arquivo, tipo_anexo, descricao
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $chamadoAtual['id'],
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
            }



            if (!empty($campos)) {
                $valores[] = $chamadoAtual['id'];
                $sql = "UPDATE chamado SET " . implode(', ', $campos) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($valores);


               // $sqlFinal = $sql;
                // foreach ($valores as $valor) {
                //     // Adiciona aspas em strings, mantém números sem aspas
                //     $valorFormatado = is_numeric($valor) ? $valor : "'" . $valor . "'";
                //     // Substitui o primeiro ? encontrado
                //     $sqlFinal = preg_replace('/\?/', $valorFormatado, $sqlFinal, 1);
                // }

            }else{ 
            $this->db->rollBack();    
            $this->sendResponse(500, ['error' => 'Erro ao atualizar chamado: sem valores env p atualizar ' 
                    ]);
            }
           
            $this->db->commit();

             /*   $mensagem = "=== DEBUG API ===\n\n";
                $mensagem .= "Content-Type: " . $contentType . "\n\n";
                $mensagem .= "Dados POST:\n" . print_r($data, true) . "\n\n";
                $mensagem .= "Campos FILES: " . implode(', ', array_keys($_FILES)) . "\n\n";
                $mensagem .= "Detalhes FILES:\n" . print_r($_FILES, true);
                
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => $mensagem
                ]);
                
                return; // Para não executar nada depois
*/

                  
            // Capturar os nomes dos campos de arquivos enviados
            

            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Chamado atualizado com sucesso !2!. Campos files: '
                ,
                'data' => [
                    'id' => $id,
                    'fotos_enviadas' => $resultadoFotos['total_enviadas'] ?? 0,
                    'avisos' => $resultadoFotos['avisos'] ?? []
                ]



                ]);
        } catch (PDOException $e) {
            
            $this->db->rollBack();
            $this->sendResponse(500, ['error' => 'Erro ao atualizar chamado: MENSAGEM ERRO: ' . $e->getMessage()
            
        ]);
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
                } elseif (empty($params[0]) && empty($_GET)) {
                    $this->getAll();
                }elseif (isset($params[0])) {
                    $this->getById($params[0]);
                } else {
                    // Aqui, quando há parâmetros via $_GET mas sem $params[0], pode-se usar um método para filtros
                    $this->getAllFiltered($_GET);
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
    private function getById($id) {
        
        $stmt = $this->db->prepare("
            SELECT m.numero_serie as serie, m.loja_id, l.nome as loja_nome, m.patrimonio, m.modelo, p.id, p.maquina_id, u.nome_completo as tecnico_responsavel, p.usuario_tecnico_id, p.tipo, p.data_programada, p.data_realizada, p.data_criacao,  p.status, p.checklist,
                   p.observacoes, p.pecas_substituidas, p.custos, p.tempo_execucao
            FROM preventiva p
            INNER JOIN maquina m on m.id = p.maquina_id
            INNER JOIN loja l on l.id = m.loja_id
            INNER JOIN usuario u on u.id = p.usuario_tecnico_id
            WHERE p.id = ?           
            ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        try{
            
            if ($result) {

                $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);

            }else{
                    $this->sendResponse(401, ['error' => 'Preventiva não encontrada']);

            }
        }catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao encontrar preventiva: ' . $e->getMessage()]);
        }

    }
    private function update($params) {

                // 1️⃣ ID vem da URL (via Router)
            $id = $params[0];
            
            // 2️⃣ Dados vêm do body (mesma forma que o create)
            $data = $this->getJsonInput();  // ← Igual ao create!
            
            // 3️⃣ Valida
            if (empty($data)) {
                $this->sendResponse(400, ['error' => 'Nenhum dado foi enviado']);
                return;
            }
            
            // 4️⃣ Campos permitidos
                   

            $allowedFields = ['maquina_id', 'usuario_tecnico_id', 'tipo', 'data_programada', 'data_realizada', 'status', 'checklist','observacoes', 'pecas_substituidas', 'custos', 'tempo_execucao'];
            $updateData = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updateData[$key] = $value;
                }
            }
                        
            try {
                // 6️⃣ Monta query
                $fields = [];
                $values = [];
                
                foreach ($updateData as $key => $value) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
                
                $values[] = $id;
                
                $sql = "UPDATE preventiva SET " . implode(', ', $fields) . " WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($values);
                
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Preventiva atualizada com sucesso'
                ]);
                
            } catch (PDOException $e) {
                $this->sendResponse(500, ['error' => 'Erro ao atualizar preventiva: ' . $e->getMessage()]);
            }

    }
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
                   END as status_preventiva,
                   p.id as preventiva_id,
                    p.data_programada
            FROM maquina m
            INNER JOIN loja l ON m.loja_id = l.id
            LEFT JOIN preventiva p ON p.maquina_id = m.id
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
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, m.patrimonio, l.nome as loja_nome,
                    u.nome_completo as tecnico_nome
                FROM preventiva p
                INNER JOIN maquina m ON p.maquina_id = m.id
                INNER JOIN loja l ON m.loja_id = l.id
                LEFT JOIN usuario u ON p.usuario_tecnico_id = u.id
                ORDER BY p.data_programada DESC
            ");

            $stmt->execute();
            $this->sendResponse(200, [
                'success' => true,
                'data' => $stmt->fetchAll()
            ]);




        } catch (PDOException $e) {
                $this->sendResponse(500, ['error' => 'Erro ao buscar preventiva: ' . $e->getMessage()]);
        }

    }
    
    private function create() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['maquina_id', 'usuario_tecnico_id', 'status']);
        
        try {
            // Remove campos vazios/null se necessário
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });
            
            // Separa as colunas e valores
            $colunas = array_keys($data);
            $valores = array_values($data);
            
            // Monta a query dinamicamente
            $colunasStr = implode(', ', $colunas);
            $placeholders = implode(', ', array_fill(0, count($colunas), '?'));
            
            $sql = "INSERT INTO preventiva ($colunasStr) VALUES ($placeholders)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($valores);
            
            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Preventiva programada com sucesso',
                'data' => ['id' => $this->db->lastInsertId()]
            ]);
            
        } catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao programar preventiva: ' . $e->getMessage()]);
        }
    }
    private function getAllFiltered() {
    
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
                
        $stmt = $this->db->prepare("
            SELECT c.*, l.nome as loja_nome, m.patrimonio, ua.nome_completo as usuario_abertura,
                   ut.nome_completo as tecnico_responsavel,
                   TIMESTAMPDIFF(HOUR, c.data_abertura, COALESCE(c.data_conclusao, NOW())) as horas_em_aberto
            FROM chamado c
            INNER JOIN loja l ON c.loja_id = l.id
            INNER JOIN maquina m ON c.maquina_id = m.id
            INNER JOIN usuario ua ON p.usuario_abertura_id = ua.id
            LEFT JOIN usuario ut ON p.usuario_tecnico_id = ut.id
            WHERE $where
            ORDER BY p.data_criacao DESC
        ");
        $stmt->execute($params);
        
        $this->sendResponse(200, [
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
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
                $this->update($params);
                break;
            default:
                $this->sendResponse(405, ['error' => 'Método não permitido']);
        }
    }
    private function getById($id) {

         if ($this->user->perfil !== 'admin') {
            $this->sendResponse(403, ['error' => 'Acesso negado']);
        }
        
        $stmt = $this->db->prepare("
            SELECT id, username, nome_completo, email, telefone, perfil, ativo, ultimo_login,
                   data_criacao
            FROM usuario WHERE id = ? ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        try{
            
            if ($result) {

                $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);

            }else{
                    $this->sendResponse(401, ['error' => 'Usuario não encontrado']);

            }
        }catch (PDOException $e) {
            $this->sendResponse(500, ['error' => 'Erro ao encontrar usuario: ' . $e->getMessage()]);
        }


        

    }
// private function update($params) {
//     // 1️⃣ Extrai o ID do array de params
//     $id = $params[0];
    
//     // 2️⃣ Captura os dados do body (JSON)
//     $data = json_decode(file_get_contents('php://input'), true);
    
//     // 3️⃣ Valida se há dados
//     if (empty($data)) {
//         $this->sendResponse(400, ['error' => 'Nenhum dado foi enviado']);
//         return;
//     }
    
//     // 4️⃣ Campos permitidos para atualização
//     $allowedFields = ['username', 'nome_completo', 'email', 'telefone', 'perfil', 'ativo', 'senha'];
//     $updateData = [];
    
//     foreach ($data as $key => $value) {
//         if (in_array($key, $allowedFields) && !empty($value)) {
//             $updateData[$key] = $value;
//         }
//     }
    
//     if (empty($updateData)) {
//         $this->sendResponse(400, ['error' => 'Nenhum campo válido para atualizar']);
//         return;
//     }
    
//     // 5️⃣ Hash da senha se foi enviada
//     if (isset($updateData['senha'])) {
//         $updateData['senha_hash'] = password_hash($updateData['senha'], PASSWORD_BCRYPT);
//         unset($updateData['senha']); // Remove 'senha' e usa 'senha_hash'
//     }
    
//     try {
//         // 6️⃣ Monta a query dinamicamente
//         $fields = [];
//         $values = [];
        
//         foreach ($updateData as $key => $value) {
//             $fields[] = "$key = ?";
//             $values[] = $value;
//         }
        
//         $values[] = $id; // Adiciona o ID no final
        
//         $sql = "UPDATE usuario SET " . implode(', ', $fields) . " WHERE id = ?";
        
//         $stmt = $this->db->prepare($sql);
//         $stmt->execute($values);
        
//         // 7️⃣ Retorna sucesso
//         $this->sendResponse(200, [
//             'success' => true,
//             'message' => 'Usuário atualizado com sucesso',
//             'id' => $id
//         ]);
        
//     } catch (PDOException $e) {
//         $this->sendResponse(500, ['error' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
//     }
// }

private function update($params) {
    // 1️⃣ ID vem da URL (via Router)
    $id = $params[0];
    
    // 2️⃣ Dados vêm do body (mesma forma que o create)
    $data = $this->getJsonInput();  // ← Igual ao create!
    
    // 3️⃣ Valida
    if (empty($data)) {
        $this->sendResponse(400, ['error' => 'Nenhum dado foi enviado']);
        return;
    }
    
    // 4️⃣ Campos permitidos
    $allowedFields = ['username', 'nome_completo', 'email', 'telefone', 'perfil', 'ativo', 'senha'];
    $updateData = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $updateData[$key] = $value;
        }
    }
    
    // 5️⃣ Hash senha se fornecida
    if (isset($updateData['senha'])) {
        $updateData['senha_hash'] = password_hash($updateData['senha'], PASSWORD_BCRYPT);
        unset($updateData['senha']);
    }
    
    try {
        // 6️⃣ Monta query
        $fields = [];
        $values = [];
        
        foreach ($updateData as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE usuario SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        $this->sendResponse(200, [
            'success' => true,
            'message' => 'Usuário atualizado com sucesso'
        ]);
        
    } catch (PDOException $e) {
        $this->sendResponse(500, ['error' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
    }
}



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
        'anexos' => ['controller' => 'AnexoController', 'method' => 'handleRequest'],
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