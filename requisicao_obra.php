<?php
session_start();

include("conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Busca informações do cliente logado
$cliente_id = $_SESSION['usuario_id'];
$cliente = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM db_cliente WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar dados do cliente: " . $e->getMessage());
}

// Processamento do formulário de requisição
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos dados
        $descricao = htmlspecialchars($_POST['descricao']);
        $prioridade = $_POST['prioridade'];
        $data_prevista = !empty($_POST['data_prevista']) ? $_POST['data_prevista'] : null;
        $observacoes = isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : null;
        
        if (empty($descricao)) {
            throw new Exception("A descrição da obra é obrigatória.");
        }
        
        // Inserção no banco de dados
        $stmt = $pdo->prepare("INSERT INTO db_requisicoes_obra (
            cliente_id, descricao, prioridade, data_prevista, observacoes, data_criacao, status
        ) VALUES (
            :cliente_id, :descricao, :prioridade, :data_prevista, :observacoes, NOW(), 'Pendente'
        )");
        
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':descricao' => $descricao,
            ':prioridade' => $prioridade,
            ':data_prevista' => $data_prevista,
            ':observacoes' => $observacoes
        ]);
        
        $mensagem = "Requisição de obra cadastrada com sucesso!";
    } catch (Exception $e) {
        $mensagem = "Erro ao cadastrar requisição: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição de Obra/Reforma</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .cliente-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .cliente-info h3 {
            margin-top: 0;
            color: #444;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        textarea {
            height: 150px;
            resize: vertical;
        }
        .prioridade {
            display: flex;
            gap: 15px;
        }
        .prioridade-option {
            display: flex;
            align-items: center;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .sucesso {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .erro {
            background-color: #f2dede;
            color: #a94442;
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Requisição de Obra/Reforma</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="cliente-info">
            <h3>Dados do Cliente</h3>
            <p><strong>Nome/Razão Social:</strong> <?php echo htmlspecialchars($cliente['nome_razao_social']); ?></p>
            <p><strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($cliente['cpf_cnpj']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
            <p><strong>Endereço:</strong> <?php 
                echo htmlspecialchars($cliente['endereco']) . ', ' . 
                    htmlspecialchars($cliente['numero']) . ' - ' . 
                    htmlspecialchars($cliente['bairro']) . ', ' . 
                    htmlspecialchars($cliente['cidade']) . '/' . 
                    htmlspecialchars($cliente['estado']);
            ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="descricao" class="required">Descrição da Obra/Reforma</label>
                <textarea id="descricao" name="descricao" required placeholder="Descreva detalhadamente a obra ou reforma que deseja realizar..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="prioridade">Prioridade</label>
                <select id="prioridade" name="prioridade">
                    <option value="baixa">Baixa</option>
                    <option value="media" selected>Média</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">Urgente</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_prevista">Data Prevista para Início</label>
                <input type="date" id="data_prevista" name="data_prevista">
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações Adicionais</label>
                <textarea id="observacoes" name="observacoes" placeholder="Informações complementares, restrições de horário, etc."></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit">Enviar Requisição</button>
            </div>
        </form>
    </div>
</body>
</html>