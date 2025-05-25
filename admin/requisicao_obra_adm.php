<?php
// Configurações do banco de dados
$host = 'localhost';
$dbname = 'construtora';
$username = 'admin';
$password = 'admin';

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Busca todos os clientes cadastrados
try {
    $stmt = $pdo->query("SELECT id, nome_razao_social, cpf_cnpj FROM db_cliente ORDER BY nome_razao_social");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar clientes: " . $e->getMessage());
}

// Processamento do formulário de requisição
$mensagem = '';
$cliente_selecionado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos dados
        $cliente_id = (int)$_POST['cliente_id'];
        $descricao = htmlspecialchars($_POST['descricao']);
        $prioridade = $_POST['prioridade'];
        $data_prevista = !empty($_POST['data_prevista']) ? $_POST['data_prevista'] : null;
        $observacoes = isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : null;
        
        if (empty($cliente_id)) {
            throw new Exception("Selecione um cliente.");
        }
        
        if (empty($descricao)) {
            throw new Exception("A descrição da obra é obrigatória.");
        }
        
        // Busca dados do cliente selecionado
        $stmt = $pdo->prepare("SELECT * FROM db_cliente WHERE id = ?");
        $stmt->execute([$cliente_id]);
        $cliente_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente_selecionado) {
            throw new Exception("Cliente selecionado não encontrado.");
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
        
        $mensagem = "Requisição de obra cadastrada com sucesso para " . htmlspecialchars($cliente_selecionado['nome_razao_social']) . "!";
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
    <title>Requisição de Obra/Reforma - Pública</title>
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
            display: <?php echo isset($cliente_selecionado) ? 'block' : 'none'; ?>;
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
        .search-box {
            margin-bottom: 15px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        
        <?php if (isset($cliente_selecionado)): ?>
            <div class="cliente-info">
                <h3>Dados do Cliente</h3>
                <p><strong>Nome/Razão Social:</strong> <?php echo htmlspecialchars($cliente_selecionado['nome_razao_social']); ?></p>
                <p><strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($cliente_selecionado['cpf_cnpj']); ?></p>
                <?php if (!empty($cliente_selecionado['telefone'])): ?>
                    <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente_selecionado['telefone']); ?></p>
                <?php endif; ?>
                <?php if (!empty($cliente_selecionado['email'])): ?>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente_selecionado['email']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="cliente_id" class="required">Selecione o Cliente</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Selecione um cliente...</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>" <?= isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nome_razao_social']) ?> (<?= htmlspecialchars($cliente['cpf_cnpj']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="descricao" class="required">Descrição da Obra/Reforma</label>
                <textarea id="descricao" name="descricao" required placeholder="Descreva detalhadamente a obra ou reforma que deseja realizar..."><?= isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : '' ?></textarea>
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
                <input type="date" id="data_prevista" name="data_prevista" value="<?= isset($_POST['data_prevista']) ? htmlspecialchars($_POST['data_prevista']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações Adicionais</label>
                <textarea id="observacoes" name="observacoes" placeholder="Informações complementares, restrições de horário, etc."><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit">Enviar Requisição</button>
            </div>
        </form>
    </div>

    <script>
        // Atualiza a seção de informações do cliente quando um cliente é selecionado
        document.getElementById('cliente_id').addEventListener('change', function() {
            // Limpa as informações do cliente anterior
            document.querySelector('.cliente-info').style.display = 'none';
        });
    </script>
</body>
</html>