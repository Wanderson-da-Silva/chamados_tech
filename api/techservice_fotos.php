<?php
header('Content-Type: application/json');

// Recebe o ID do chamado
$idChamado = isset($_POST['idChamado']) ? $_POST['idChamado'] : null;

// Valida se o ID foi enviado
if (empty($idChamado)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do chamado não foi informado.'
    ]);
    exit;
}

// Pasta onde as fotos serão salvas
$pastaDestino = 'fotos/';

// Cria a pasta se não existir
if (!file_exists($pastaDestino)) {
    mkdir($pastaDestino, 0777, true);
}

// Verifica se há arquivos enviados
if (!isset($_FILES['fotos']) || empty($_FILES['fotos']['name'][0])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhuma foto foi enviada.'
    ]);
    exit;
}

// Extensões permitidas
$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$tamanhoMaximo = 5 * 1024 * 1024; // 5MB em bytes

$arquivosEnviados = [];
$erros = [];

// Processa cada arquivo enviado
$totalArquivos = count($_FILES['fotos']['name']);

for ($i = 0; $i < $totalArquivos; $i++) {
    // Pula arquivos vazios
    if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    
    // Verifica se houve erro no upload
    if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) {
        $erros[] = "Erro ao fazer upload de '{$_FILES['fotos']['name'][$i]}'.";
        continue;
    }
    
    $nomeOriginal = $_FILES['fotos']['name'][$i];
    $tmpName = $_FILES['fotos']['tmp_name'][$i];
    $tamanho = $_FILES['fotos']['size'][$i];
    
    // Verifica a extensão
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoesPermitidas)) {
        $erros[] = "'{$nomeOriginal}': formato não permitido. Use JPG, PNG, GIF ou WEBP.";
        continue;
    }
    
    // Verifica se é realmente uma imagem
    $verificaImagem = getimagesize($tmpName);
    if ($verificaImagem === false) {
        $erros[] = "'{$nomeOriginal}': não é uma imagem válida.";
        continue;
    }
    
    // Verifica o tamanho
    if ($tamanho > $tamanhoMaximo) {
        $erros[] = "'{$nomeOriginal}': excede o tamanho máximo de 5MB.";
        continue;
    }
    
    // Gera um nome único para o arquivo
    // Formato: idChamado_dataAtual_nomeGerado.extensao
    $dataAtual = date('Y-m-d_H-i-s'); // Formato: 2026-01-22_14-30-45
    $nomeGerado = uniqid() . '_' . time() . '_' . $i;
    $nomeArquivo = $idChamado . '_' . $dataAtual . '_' . $nomeGerado . '.' . $extensao;
    $caminhoCompleto = $pastaDestino . $nomeArquivo;
    
    // Move o arquivo para a pasta de destino
    if (move_uploaded_file($tmpName, $caminhoCompleto)) {
        $arquivosEnviados[] = $nomeArquivo;
    } else {
        $erros[] = "'{$nomeOriginal}': erro ao salvar no servidor.";
    }
}

// Prepara a resposta
if (count($arquivosEnviados) > 0) {
    $resposta = [
        'success' => true,
        'message' => count($arquivosEnviados) . ' foto(s) enviada(s) com sucesso!',
        'total_enviadas' => count($arquivosEnviados),
        'arquivos' => $arquivosEnviados
    ];
    
    // Adiciona erros se houver, mas mantém success como true
    if (count($erros) > 0) {
        $resposta['avisos'] = $erros;
        $resposta['message'] .= ' Alguns arquivos não foram enviados.';
    }
    
    echo json_encode($resposta);
} else {
    // Nenhum arquivo foi enviado com sucesso
    echo json_encode([
        'success' => false,
        'message' => 'Nenhuma foto pôde ser enviada.',
        'erros' => $erros
    ]);
}
?>