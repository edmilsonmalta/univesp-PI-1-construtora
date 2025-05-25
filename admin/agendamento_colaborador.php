<?php
include("../conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Busca todos os colaboradores
try {
    $stmt = $pdo->query("SELECT id, nome FROM db_colaboradores ORDER BY nome");
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar colaboradores: " . $e->getMessage());
}

// Filtros padrão
$colaborador_id = isset($_GET['colaborador_id']) ? (int)$_GET['colaborador_id'] : null;
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Busca agendamentos filtrados
$agendamentos = [];
if ($colaborador_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, c.nome_razao_social, r.descricao
            FROM db_agendamento a
            JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
            JOIN db_cliente c ON r.cliente_id = c.id
            JOIN db_colaboradores col ON a.responsavel = col.nome
            WHERE col.id = :colaborador_id
            AND MONTH(a.data_inicio_obra) = :mes
            AND YEAR(a.data_inicio_obra) = :ano
            ORDER BY a.data_inicio_obra, a.hora_inicio
        ");
        
        $stmt->execute([
            ':colaborador_id' => $colaborador_id,
            ':mes' => $mes,
            ':ano' => $ano
        ]);
        
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar agendamentos: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos por Colaborador</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Agendamentos por Colaborador</h1>
        
        <form method="GET" class="filtros">
            <div class="form-group">
                <label for="colaborador_id">Colaborador</label>
                <select id="colaborador_id" name="colaborador_id" required>
                    <option value="">Selecione um colaborador</option>
                    <?php foreach ($colaboradores as $colab): ?>
                        <option value="<?= $colab['id'] ?>" <?= $colaborador_id == $colab['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($colab['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
			
 <div class="form-group">
                <label for="mes">Mês</label>
                <select id="mes" name="mes">
                    <?php 
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
                    
                    for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $mes == $i ? 'selected' : '' ?>>
                            <?= $meses_pt[$i] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="ano">Ano</label>
                <input type="number" id="ano" name="ano" min="2000" max="2100" value="<?= $ano ?>" style="width: 80px;">
            </div>
            
            <button type="submit" class="btn">Filtrar</button>
        </form>
        
        <?php if ($colaborador_id): ?>
            <?php if (!empty($agendamentos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Cliente</th>
                            <th>Descrição da Obra</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($agendamento['data_inicio_obra'])) ?></td>
                                <td><?= substr($agendamento['hora_inicio'], 0, 5) ?> - <?= substr($agendamento['hora_fim'], 0, 5) ?></td>
                                <td><?= htmlspecialchars($agendamento['nome_razao_social']) ?></td>
                                <td><?= htmlspecialchars(substr($agendamento['descricao'], 0, 50)) . (strlen($agendamento['descricao']) > 50 ? '...' : '') ?></td>
                                <td><?= htmlspecialchars($agendamento['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sem-resultados">
                    Nenhum agendamento encontrado para este colaborador no período selecionado.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>