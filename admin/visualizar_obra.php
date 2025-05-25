<?php
// visualizar_obra.php

include("../conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    die("ID da obra não especificado");
}

$obra_id = $_GET['id'];

// Busca os dados da obra e do cliente
try {
    $stmt = $pdo->prepare("SELECT r.*, c.* 
                          FROM db_requisicoes_obra r
                          JOIN db_cliente c ON r.cliente_id = c.id
                          WHERE r.id = ?");
    $stmt->execute([$obra_id]);
    $obra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$obra) {
        die("Obra não encontrada");
    }
    
    // Busca dados de agendamento se existir
    $stmt = $pdo->prepare("SELECT * FROM db_agendamento WHERE requisicao_id = ?");
    $stmt->execute([$obra_id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Obra Completa</title>
    <!-- Use o mesmo estilo do requisicao_obra.php -->
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
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
        .info-value {
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .agendamento-info {
            background-color: #e7f3fe;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 5px solid #2196F3;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendente {
            background-color: #ff9800;
            color: white;
        }
        .status-aprovada {
            background-color: #4CAF50;
            color: white;
        }
        .status-andamento {
            background-color: #2196F3;
            color: white;
        }
        .status-concluida {
            background-color: #9c27b0;
            color: white;
        }
        .btn-voltar {
            display: inline-block;
            padding: 10px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn-voltar:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalhes Completo da Obra</h1>
        
        <div class="cliente-info">
            <h3>Dados do Cliente</h3>
            <p><strong>Nome/Razão Social:</strong> <?php echo htmlspecialchars($obra['nome_razao_social']); ?></p>
            <p><strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($obra['cpf_cnpj']); ?></p>
            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($obra['telefone']); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($obra['email']); ?></p>
            <p><strong>Endereço:</strong> <?php 
                echo htmlspecialchars($obra['endereco']) . ', ' . 
                    htmlspecialchars($obra['numero']) . ' - ' . 
                    htmlspecialchars($obra['bairro']) . ', ' . 
                    htmlspecialchars($obra['cidade']) . '/' . 
                    htmlspecialchars($obra['estado']);
            ?></p>
        </div>
        
        <div class="form-group">
            <label>Descrição da Obra/Reforma</label>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($obra['descricao'])); ?></div>
        </div>
        
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <div class="form-group">
                    <label>Prioridade</label>
                    <div class="info-value"><?php echo htmlspecialchars(ucfirst($obra['prioridade'])); ?></div>
                </div>
            </div>
            <div style="flex: 1;">
                <div class="form-group">
                    <label>Status</label>
                    <div class="info-value">
                        <span class="status status-<?php echo strtolower($obra['status']); ?>">
                            <?php echo htmlspecialchars($obra['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <div class="form-group">
                    <label>Data de Criação</label>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($obra['data_criacao'])); ?></div>
                </div>
            </div>
            <div style="flex: 1;">
                <div class="form-group">
                    <label>Data Prevista</label>
                    <div class="info-value">
                        <?php echo $obra['data_prevista'] ? date('d/m/Y', strtotime($obra['data_prevista'])) : 'Não informada'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Observações</label>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($obra['observacoes'] ?? 'Nenhuma observação')); ?></div>
        </div>
        
        <?php if ($agendamento): ?>
        <div class="agendamento-info">
            <h3>Informações de Agendamento</h3>
            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label>Data de Início</label>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($agendamento['data_inicio_obra'])); ?></div>
                </div>
                <div style="flex: 1;">
                    <label>Hora de Início</label>
                    <div class="info-value"><?php echo substr($agendamento['hora_inicio'], 0, 5); ?></div>
                </div>
            </div>
            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label>Data de Término</label>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($agendamento['data_fim_obra'])); ?></div>
                </div>
                <div style="flex: 1;">
                    <label>Hora de Término</label>
                    <div class="info-value"><?php echo substr($agendamento['hora_fim'], 0, 5); ?></div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Responsável</label>
                <div class="info-value"><?php echo htmlspecialchars($agendamento['responsavel']); ?></div>
            </div>
            <div>
                <label>Observações do Agendamento</label>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($agendamento['observacoes'] ?? 'Nenhuma observação')); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <a href="lista_requisicoes.php" class="btn-voltar">Voltar para a Lista</a>
    </div>
</body>
</html>