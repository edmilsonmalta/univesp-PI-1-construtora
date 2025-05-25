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

// Processamento da exclusão do agendamento
if (isset($_GET['excluir'])) {
    $agendamento_id = (int)$_GET['excluir'];
    
    try {
        // Verifica se o agendamento existe
        $stmt = $pdo->prepare("SELECT id FROM db_agendamento WHERE id = ?");
        $stmt->execute([$agendamento_id]);
        
        if ($stmt->fetch()) {
            // Se existir, exclui
            $stmt = $pdo->prepare("DELETE FROM db_agendamento WHERE id = ?");
            $stmt->execute([$agendamento_id]);
            
            $_SESSION['mensagem'] = "Agendamento excluído com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
            
            header("Location: lista_requisicoes.php");
            exit();
        } else {
            $_SESSION['mensagem'] = "Agendamento não encontrado!";
            $_SESSION['tipo_mensagem'] = "erro";
        }
    } catch (PDOException $e) {
        die("Erro ao excluir agendamento: " . $e->getMessage());
    }
}

// Verifica se foi passado um ID de requisição
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de requisição inválido");
}
$requisicao_id = (int)$_GET['id'];

// Busca os dados da requisição e do agendamento
try {
    $stmt = $pdo->prepare("SELECT r.*, c.nome_razao_social, a.id as agendamento_id, a.*
                          FROM db_requisicoes_obra r
                          JOIN db_cliente c ON r.cliente_id = c.id
                          JOIN db_agendamento a ON a.requisicao_id = r.id
                          WHERE r.id = ?");
    $stmt->execute([$requisicao_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dados) {
        die("Requisição ou agendamento não encontrado");
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Agendamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .detalhes-agendamento {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
        .detalhes-agendamento p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .detalhes-agendamento strong {
            display: inline-block;
            width: 150px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .btn-editar {
            background-color: #28a745;
        }
        .btn-editar:hover {
            background-color: #218838;
        }
        .btn-voltar {
            background-color: #6c757d;
        }
        .btn-voltar:hover {
            background-color: #5a6268;
        }
        .btn-excluir {
            background-color: #dc3545;
        }
        .btn-excluir:hover {
            background-color: #c82333;
        }
        .mensagem {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalhes do Agendamento</h1>
        
        <!-- Exibe mensagens de feedback -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem <?php echo $_SESSION['tipo_mensagem']; ?>">
                <?php echo $_SESSION['mensagem']; ?>
                <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            </div>
        <?php endif; ?>
        
        <div class="detalhes-agendamento">
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($dados['nome_razao_social']); ?></p>
            <p><strong>Descrição da Obra:</strong> <?php echo htmlspecialchars($dados['descricao']); ?></p>
            <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y', strtotime($dados['data_criacao'])); ?></p>
            <hr>
            <p><strong>Responsável:</strong> <?php echo htmlspecialchars($dados['responsavel']); ?></p>
            <p><strong>Data do Agendamento:</strong> <?php echo date('d/m/Y', strtotime($dados['data_inicio_obra'])); ?></p>
            <p><strong>Hora Início:</strong> <?php echo substr($dados['hora_inicio'], 0, 5); ?></p>
            <p><strong>Hora Fim:</strong> <?php echo substr($dados['hora_fim'], 0, 5); ?></p>
            <p><strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($dados['observacoes'])); ?></p>
            <p><strong>Status:</strong> <span style="text-transform: capitalize;"><?php echo htmlspecialchars($dados['status']); ?></span></p>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <a href="editar_agendamento.php?id=<?php echo $dados['agendamento_id']; ?>" class="btn btn-editar">Editar Agendamento</a>
            <a href="ver_agendamento.php?id=<?php echo $requisicao_id; ?>&excluir=<?php echo $dados['agendamento_id']; ?>" 
               class="btn btn-excluir" 
               onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">Excluir Agendamento</a>
            <a href="lista_requisicoes.php" class="btn btn-voltar">Voltar para Lista</a>
        </div>
    </div>
</body>
</html>