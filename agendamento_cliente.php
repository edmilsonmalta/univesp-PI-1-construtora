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

// Obtém o ID do cliente logado
$cliente_id = $_SESSION['usuario_id'];

// Processar confirmação de conclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_conclusao'])) {
    $agendamento_id = $_POST['agendamento_id'];
    
    try {
        // Verifica se o agendamento pertence ao cliente
        $stmt = $pdo->prepare("
            SELECT a.id 
            FROM db_agendamento a
            JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
            WHERE a.id = ? AND r.cliente_id = ?
        ");
        $stmt->execute([$agendamento_id, $cliente_id]);
        
        if ($stmt->fetch()) {
            // Atualiza o status para "Concluída"
            $stmt = $pdo->prepare("UPDATE db_agendamento SET status = 'concluida' WHERE id = ?");
            $stmt->execute([$agendamento_id]);
            
            $_SESSION['mensagem'] = "Obra confirmada como concluída com sucesso!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            die("Agendamento não encontrado ou não pertence a este cliente.");
        }
    } catch (PDOException $e) {
        die("Erro ao confirmar conclusão: " . $e->getMessage());
    }
}

// Busca informações do cliente
try {
    $stmt = $pdo->prepare("SELECT nome_razao_social FROM db_cliente WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        die("Cliente não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados do cliente: " . $e->getMessage());
}

// Filtros
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Busca os agendamentos do cliente
try {
    $stmt = $pdo->prepare("
        SELECT a.*, r.descricao, col.nome AS responsavel_nome
        FROM db_agendamento a
        JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
        JOIN db_colaboradores col ON a.responsavel = col.nome
        WHERE r.cliente_id = :cliente_id
        AND MONTH(a.data_inicio_obra) = :mes
        AND YEAR(a.data_inicio_obra) = :ano
        ORDER BY a.data_inicio_obra, a.hora_inicio
    ");
    
    $stmt->execute([
        ':cliente_id' => $cliente_id,
        ':mes' => $mes,
        ':ano' => $ano
    ]);
    
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}

// Array com nomes dos meses em português
$meses_pt = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
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
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .filtros {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select, .form-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .btn-confirmar {
            background-color: #28a745;
        }
        .btn-confirmar:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .sem-resultados {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendente {
            background-color: #ffc107;
            color: #212529;
        }
        .status-andamento {
            background-color: #17a2b8;
            color: white;
        }
        .status-concluida {
            background-color: #28a745;
            color: white;
        }
        .status-cancelada {
            background-color: #dc3545;
            color: white;
        }
        .mensagem {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Meus Agendamentos</h1>
        <p style="text-align: center;">Cliente: <strong><?= htmlspecialchars($cliente['nome_razao_social']) ?></strong></p>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem mensagem-sucesso">
                <?= $_SESSION['mensagem'] ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        
        <form method="GET" class="filtros">
            <div class="form-group">
                <label for="mes">Mês</label>
                <select id="mes" name="mes">
                    <?php foreach ($meses_pt as $num => $nome): ?>
                        <option value="<?= $num ?>" <?= $mes == $num ? 'selected' : '' ?>>
                            <?= $nome ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="ano">Ano</label>
                <input type="number" id="ano" name="ano" min="2000" max="2100" value="<?= $ano ?>" style="width: 80px;">
            </div>
            
            <button type="submit" class="btn">Filtrar</button>
        </form>
        
        <?php if (!empty($agendamentos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Responsável</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $agendamento): 
                        // Verifica se a data/hora atual é maior ou igual à data de fim da obra
                        $data_fim_obra = new DateTime($agendamento['data_fim_obra'] . ' ' . $agendamento['hora_fim']);
                        $agora = new DateTime();
                        $pode_confirmar = ($agora >= $data_fim_obra && strtolower($agendamento['status']) !== 'concluida');
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($agendamento['data_inicio_obra'])) ?></td>
                            <td><?= substr($agendamento['hora_inicio'], 0, 5) ?> - <?= substr($agendamento['hora_fim'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($agendamento['responsavel_nome']) ?></td>
                            <td><?= htmlspecialchars(substr($agendamento['descricao'], 0, 50)) ?><?= strlen($agendamento['descricao']) > 50 ? '...' : '' ?></td>
                            <td>
                                <span class="status status-<?= strtolower($agendamento['status']) ?>">
                                    <?= htmlspecialchars($agendamento['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($pode_confirmar): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                                        <button type="submit" name="confirmar_conclusao" class="btn btn-confirmar">
                                            Confirmar Conclusão
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sem-resultados">
                Nenhum agendamento encontrado para <?= $meses_pt[$mes] ?>/<?= $ano ?>.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>