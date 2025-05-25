<?php
include("../conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Verifica se foi passado um ID de requisição
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de requisição inválido");
}
$requisicao_id = (int)$_GET['id'];

// Busca os dados da requisição
try {
    $stmt = $pdo->prepare("SELECT r.*, c.nome_razao_social 
                          FROM db_requisicoes_obra r
                          JOIN db_cliente c ON r.cliente_id = c.id
                          WHERE r.id = ?");
    $stmt->execute([$requisicao_id]);
    $requisicao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$requisicao) {
        die("Requisição não encontrada");
    }
} catch (PDOException $e) {
    die("Erro ao buscar requisição: " . $e->getMessage());
}

// Inicializa variável de agendamento como null
$agendamento = null;

// Busca agendamento existente (se houver)
try {
    $stmt = $pdo->prepare("SELECT * FROM db_agendamento WHERE requisicao_id = ?");
    $stmt->execute([$requisicao_id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar agendamento: " . $e->getMessage());
}

// Busca todos os colaboradores
try {
    $stmt = $pdo->query("SELECT id, nome FROM db_colaboradores ORDER BY nome");
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar colaboradores: " . $e->getMessage());
}

// Filtro por colaborador (prioritário)
$filtro_colaborador = isset($_GET['colaborador']) ? $_GET['colaborador'] : '';

// Filtro por data (secundário)
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';

// Monta a query de busca de agendamentos com filtros
$query_agendamentos = "SELECT a.*, c.nome as nome_colaborador 
                      FROM db_agendamento a
                      JOIN db_colaboradores c ON a.responsavel = c.nome
                      WHERE 1=1";

$params = [];

if (!empty($filtro_colaborador)) {
    $query_agendamentos .= " AND a.responsavel = :colaborador";
    $params[':colaborador'] = $filtro_colaborador;
}

if (!empty($filtro_data)) {
    $query_agendamentos .= " AND DATE(a.data_inicio_obra) = :data";
    $params[':data'] = $filtro_data;
}

$query_agendamentos .= " ORDER BY a.data_inicio_obra, a.hora_inicio";

// Busca agendamentos dos colaboradores com filtros aplicados
try {
    $stmt = $pdo->prepare($query_agendamentos);
    $stmt->execute($params);
    $agendamentos_colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inicio = $_POST['data_inicio'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $responsavel = $_POST['responsavel'];
    $observacoes = $_POST['observacoes'];
    
    // Validação básica
    if (empty($data_inicio) || empty($hora_inicio) || empty($hora_fim) || empty($responsavel)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos";
    } else {
        try {
            if ($agendamento) {
                // Atualiza agendamento existente
                $stmt = $pdo->prepare("UPDATE db_agendamento SET 
                                      data_inicio_obra = ?, 
                                      data_fim_obra = ?,
                                      hora_inicio = ?,
                                      hora_fim = ?,
                                      responsavel = ?,
                                      observacoes = ?
                                      WHERE id = ?");
                $stmt->execute([
                    $data_inicio,
                    $data_inicio, // Data fim é a mesma que data inicio
                    $hora_inicio,
                    $hora_fim,
                    $responsavel,
                    $observacoes,
                    $agendamento['id']
                ]);
            } else {
                // Cria novo agendamento
                $stmt = $pdo->prepare("INSERT INTO db_agendamento 
                                      (requisicao_id, data_inicio_obra, data_fim_obra, hora_inicio, hora_fim, responsavel, observacoes)
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $requisicao_id,
                    $data_inicio,
                    $data_inicio, // Data fim é a mesma que data inicio
                    $hora_inicio,
                    $hora_fim,
                    $responsavel,
                    $observacoes
                ]);
            }
            
            header("Location: lista_requisicoes.php?msg=Agendamento " . ($agendamento ? "atualizado" : "criado") . " com sucesso");
            exit();
        } catch (PDOException $e) {
            $erro = "Erro ao salvar agendamento: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento de Obra</title>
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
        h1, h2 {
            color: #333;
        }
        .sintese-requisicao {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .sintese-requisicao p {
            margin: 5px 0;
        }
        .sintese-requisicao strong {
            display: inline-block;
            width: 120px;
        }
        .agendamentos-dia {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
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
        .form-agendamento {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .erro {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .filtros {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .filtro-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filtro-group label {
            font-weight: bold;
            white-space: nowrap;
        }
        .filtro-group input, .filtro-group select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filtro-group button {
            padding: 5px 10px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-limpar {
            background-color: #dc3545;
        }
        .btn-limpar:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Agendamento de Obra</h1>
        
        <!-- Síntese da Requisição -->
        <div class="sintese-requisicao">
            <h2>Síntese da Requisição</h2>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($requisicao['nome_razao_social']); ?></p>
            <p><strong>Descrição:</strong> <?php echo htmlspecialchars(substr($requisicao['descricao'], 0, 100)); ?>...</p>
            <p><strong>Data Criação:</strong> <?php echo date('d/m/Y', strtotime($requisicao['data_criacao'])); ?></p>
        </div>
        
        <!-- Lista de Agendamentos por Colaborador -->
        <div class="agendamentos-dia">
            <h2>Agendamentos por Colaborador</h2>
            
<div class="filtros">
    <div class="filtro-group">
        <label for="colaborador-filtro">Colaborador:</label>
        <select id="colaborador-filtro" name="colaborador-filtro">
            <option value="">Todos os colaboradores</option>
            <?php foreach ($colaboradores as $colab): ?>
                <option value="<?php echo htmlspecialchars($colab['nome']); ?>"
                    <?php echo ($filtro_colaborador == $colab['nome']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($colab['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filtro-group">
        <label for="data-filtro">Data (opcional):</label>
        <input type="date" id="data-filtro" name="data-filtro" value="<?php echo htmlspecialchars($filtro_data); ?>">
    </div>
    
    <button onclick="aplicarFiltros()" class="btn">Aplicar Filtros</button>
    <button onclick="limparFiltros()" class="btn btn-limpar">Limpar Filtros</button>
</div>
            
            <?php if (empty($agendamentos_colaboradores)): ?>
                <p>Nenhum agendamento encontrado com os filtros selecionados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Data</th>
                            <th>Hora Início</th>
                            <th>Hora Fim</th>
                            <th>Obra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos_colaboradores as $agendamento_colab): 
                            // Busca dados da obra associada
                            $stmt = $pdo->prepare("SELECT r.descricao, c.nome_razao_social 
                                                  FROM db_requisicoes_obra r
                                                  JOIN db_cliente c ON r.cliente_id = c.id
                                                  WHERE r.id = ?");
                            $stmt->execute([$agendamento_colab['requisicao_id']]);
                            $obra_info = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agendamento_colab['nome_colaborador']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($agendamento_colab['data_inicio_obra'])); ?></td>
                                <td><?php echo substr($agendamento_colab['hora_inicio'], 0, 5); ?></td>
                                <td><?php echo substr($agendamento_colab['hora_fim'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($obra_info['nome_razao_social']) . ' - ' . htmlspecialchars(substr($obra_info['descricao'], 0, 30)) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Formulário de Agendamento -->
        <div class="form-agendamento">
            <h2><?php echo $agendamento ? 'Editar' : 'Criar'; ?> Agendamento</h2>
            
            <?php if (isset($erro)): ?>
                <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="responsavel">Responsável *</label>
                        <select id="responsavel" name="responsavel" required>
                            <option value="">Selecione um colaborador</option>
                            <?php foreach ($colaboradores as $colab): ?>
                                <option value="<?php echo htmlspecialchars($colab['nome']); ?>" 
                                    <?php echo ($agendamento && $agendamento['responsavel'] == $colab['nome']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($colab['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_inicio">Data *</label>
                        <input type="date" id="data_inicio" name="data_inicio" 
                               value="<?php echo $agendamento ? htmlspecialchars($agendamento['data_inicio_obra']) : htmlspecialchars(date('Y-m-d')); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="hora_inicio">Hora Início *</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" 
                               value="<?php echo $agendamento ? substr(htmlspecialchars($agendamento['hora_inicio']), 0, 5) : '08:00'; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora_fim">Hora Fim *</label>
                        <input type="time" id="hora_fim" name="hora_fim" 
                               value="<?php echo $agendamento ? substr(htmlspecialchars($agendamento['hora_fim']), 0, 5) : '17:00'; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"><?php echo $agendamento ? htmlspecialchars($agendamento['observacoes']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn"><?php echo $agendamento ? 'Atualizar' : 'Salvar'; ?> Agendamento</button>
                <a href="lista_requisicoes.php" class="btn" style="background-color: #6c757d; margin-left: 10px;">Cancelar</a>
            </form>
        </div>
    </div>
    
    <script>
    function aplicarFiltros() {
        const colaborador = document.getElementById('colaborador-filtro').value;
        const data = document.getElementById('data-filtro').value;
        
        let url = `agendamento.php?id=<?php echo $requisicao_id; ?>`;
        
        if (colaborador) {
            url += `&colaborador=${encodeURIComponent(colaborador)}`;
        }
        
        if (data) {
            url += `&data=${data}`;
        }
        
        window.location.href = url;
    }
    
    function limparFiltros() {
        window.location.href = `agendamento.php?id=<?php echo $requisicao_id; ?>`;
    }
        // Validação para garantir que data fim é igual a data início
        document.querySelector('form').addEventListener('submit', function(e) {
            const dataInicio = document.getElementById('data_inicio').value;
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFim = document.getElementById('hora_fim').value;
            
            if (horaInicio >= horaFim) {
                alert('A hora de término deve ser posterior à hora de início');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>