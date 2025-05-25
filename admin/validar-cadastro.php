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

// Processamento da validação e geração de senha
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['cliente_id'])) {
            $cliente_id = $_POST['cliente_id'];
            $senha_personalizada = $_POST['senha_personalizada'] ?? '';
            
            // Busca os dados do cliente
            $stmt = $pdo->prepare("SELECT * FROM db_cliente WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                throw new Exception("Cliente não encontrado.");
            }
            
            // Gera senha aleatória se não for informada
            $senha = !empty($senha_personalizada) ? $senha_personalizada : substr(md5(uniqid(rand(), true)), 0, 8);
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $login = preg_replace('/[^0-9]/', '', $cliente['cpf_cnpj']);
            
            // Verifica se já existe um usuário para este cliente
            $stmt = $pdo->prepare("SELECT * FROM db_usuarios WHERE cliente_id = ?");
            $stmt->execute([$cliente_id]);
            $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario_existente) {
                // Atualiza a senha se o usuário já existir
                $stmt = $pdo->prepare("UPDATE db_usuarios SET senha_hash = ? WHERE cliente_id = ?");
                $stmt->execute([$senha_hash, $cliente_id]);
            } else {
                // Cria um novo usuário se não existir
                $stmt = $pdo->prepare("INSERT INTO db_usuarios (cliente_id, login, senha_hash) VALUES (?, ?, ?)");
                $stmt->execute([$cliente_id, $login, $senha_hash]);
            }
            
            $mensagem = "Acesso configurado para " . htmlspecialchars($cliente['nome_razao_social']) . 
                       "<br><strong>Login:</strong> " . htmlspecialchars($login) .
                       "<br><strong>Senha:</strong> " . htmlspecialchars($senha);
            
            if (isset($_POST['enviar_email']) && $_POST['enviar_email'] == '1') {
                // Simulação do envio de email
                $mensagem .= "<br><strong>Email enviado para:</strong> " . htmlspecialchars($cliente['email']);
            }
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro ao configurar acesso: " . $e->getMessage();
    }
}

// Configuração da paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Conta o total de clientes
$total_clientes = $pdo->query("SELECT COUNT(*) FROM db_cliente")->fetchColumn();
$total_paginas = ceil($total_clientes / $registros_por_pagina);

// Busca clientes com paginação
try {
    $stmt = $pdo->prepare("SELECT c.*, u.id as usuario_id FROM db_cliente c 
                         LEFT JOIN db_usuarios u ON c.id = u.cliente_id 
                         ORDER BY c.nome_razao_social
                         LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .btn {
            display: inline-block;
            padding: 8px 12px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 2px;
            font-size: 14px;
        }
        .btn-validar {
            background-color: #2196F3;
        }
        .btn-validar:hover {
            background-color: #0b7dda;
        }
        .btn-email {
            background-color: #ff9800;
        }
        .btn-email:hover {
            background-color: #e68a00;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status-ativo {
            background-color: #4CAF50;
            color: white;
        }
        .status-inativo {
            background-color: #f44336;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group input[type="checkbox"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group.checkbox {
            display: flex;
            align-items: center;
        }
        .form-group.checkbox input {
            width: auto;
            margin-right: 10px;
        }
        .paginacao {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .paginacao a {
            padding: 8px 16px;
            margin: 0 4px;
            text-decoration: none;
            color: #2196F3;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .paginacao a.active {
            background-color: #2196F3;
            color: white;
            border: 1px solid #2196F3;
        }
        .paginacao a:hover:not(.active) {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciamento de Clientes</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'Erro') === false ? 'sucesso' : 'erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome/Razão Social</th>
                    <th>CPF/CNPJ</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cliente['id']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['nome_razao_social']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['cpf_cnpj']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['tipo']); ?></td>
                    <td>
                        <span class="status <?php echo $cliente['usuario_id'] ? 'status-ativo' : 'status-inativo'; ?>">
                            <?php echo $cliente['usuario_id'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td>
                        <button onclick="openModal(<?php echo $cliente['id']; ?>)" class="btn btn-validar">
                            <?php echo $cliente['usuario_id'] ? 'Alterar Senha' : 'Ativar Cadastro'; ?>
                        </button>
                        
                        <?php if ($cliente['usuario_id']): ?>
                        <button onclick="openEmailModal(<?php echo $cliente['id']; ?>)" class="btn btn-email">
                            Enviar Email
                        </button>
                        <?php endif; ?>
                        
                        <!-- Modal para senha -->
                        <div id="modal-<?php echo $cliente['id']; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="closeModal(<?php echo $cliente['id']; ?>)">&times;</span>
                                <h2>Configurar Acesso para <?php echo htmlspecialchars($cliente['nome_razao_social']); ?></h2>
                                <form method="POST" action="">
                                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                    <div class="form-group">
                                        <label for="senha_personalizada-<?php echo $cliente['id']; ?>">
                                            Senha (deixe em branco para gerar automaticamente):
                                        </label>
                                        <input type="text" id="senha_personalizada-<?php echo $cliente['id']; ?>" 
                                               name="senha_personalizada" placeholder="Digite a senha ou deixe em branco">
                                    </div>
                                    <?php if (!$cliente['usuario_id']): ?>
                                    <div class="form-group checkbox">
                                        <input type="checkbox" id="enviar_email-<?php echo $cliente['id']; ?>" 
                                               name="enviar_email" value="1" checked>
                                        <label for="enviar_email-<?php echo $cliente['id']; ?>">Enviar email com os dados de acesso</label>
                                    </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-validar">Confirmar</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Modal para enviar email -->
                        <div id="modal-email-<?php echo $cliente['id']; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="closeEmailModal(<?php echo $cliente['id']; ?>)">&times;</span>
                                <h2>Enviar Email para <?php echo htmlspecialchars($cliente['nome_razao_social']); ?></h2>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                                <form method="POST" action="">
                                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                    <input type="hidden" name="enviar_email" value="1">
                                    <div class="form-group">
                                        <label>Será enviado um email com os dados de acesso atuais.</label>
                                    </div>
                                    <button type="submit" class="btn btn-email">Confirmar Envio</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Paginação -->
        <div class="paginacao">
            <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=1">&laquo; Primeira</a>
                <a href="?pagina=<?php echo $pagina_atual - 1; ?>">&lsaquo; Anterior</a>
            <?php endif; ?>
            
            <?php 
            $inicio = max(1, $pagina_atual - 2);
            $fim = min($total_paginas, $pagina_atual + 2);
            
            for ($i = $inicio; $i <= $fim; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>" <?php echo $i == $pagina_atual ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_atual + 1; ?>">Próxima &rsaquo;</a>
                <a href="?pagina=<?php echo $total_paginas; ?>">Última &raquo;</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openModal(clienteId) {
            document.getElementById('modal-' + clienteId).style.display = 'block';
        }
        
        function closeModal(clienteId) {
            document.getElementById('modal-' + clienteId).style.display = 'none';
        }
        
        function openEmailModal(clienteId) {
            document.getElementById('modal-email-' + clienteId).style.display = 'block';
        }
        
        function closeEmailModal(clienteId) {
            document.getElementById('modal-email-' + clienteId).style.display = 'none';
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>