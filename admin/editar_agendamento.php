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
// Filtro por colaborador (prioritário)
$filtro_colaborador = isset($_GET['colaborador']) ? $_GET['colaborador'] : '';

// Filtro por data (secundário)
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';

// Verifica se foi passado um ID de agendamento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de agendamento inválido");
}
$agendamento_id = (int)$_GET['id'];

// Busca os dados do agendamento e da requisição associada
try {
    $stmt = $pdo->prepare("SELECT a.*, r.descricao, c.nome_razao_social, r.data_criacao, r.id as requisicao_id
                          FROM db_agendamento a
                          JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
                          JOIN db_cliente c ON r.cliente_id = c.id
                          WHERE a.id = ?");
    $stmt->execute([$agendamento_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dados) {
        die("Agendamento não encontrado");
    }
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

// Busca agendamentos com filtros (excluindo o agendamento atual)
$query_agendamentos = "SELECT a.*, c.nome as nome_colaborador, cli.nome_razao_social, r.descricao
                      FROM db_agendamento a
                      JOIN db_colaboradores c ON a.responsavel = c.nome
                      JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
                      JOIN db_cliente cli ON r.cliente_id = cli.id
                      WHERE a.id != :agendamento_id";

$params = [':agendamento_id' => $agendamento_id];

if (!empty($filtro_colaborador)) {
    $query_agendamentos .= " AND a.responsavel = :colaborador";
    $params[':colaborador'] = $filtro_colaborador;
}

if (!empty($filtro_data)) {
    $query_agendamentos .= " AND DATE(a.data_inicio_obra) = :data";
    $params[':data'] = $filtro_data;
}

$query_agendamentos .= " ORDER BY a.data_inicio_obra, a.hora_inicio";

try {
    $stmt = $pdo->prepare($query_agendamentos);
    $stmt->execute($params);
    $agendamentos_filtrados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}
// Verifica se foi passado um ID de agendamento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de agendamento inválido");
}
$agendamento_id = (int)$_GET['id'];

// Busca os dados do agendamento e da requisição associada
try {
    $stmt = $pdo->prepare("SELECT a.*, r.descricao, c.nome_razao_social, r.data_criacao
                          FROM db_agendamento a
                          JOIN db_requisicoes_obra r ON a.requisicao_id = r.id
                          JOIN db_cliente c ON r.cliente_id = c.id
                          WHERE a.id = ?");
    $stmt->execute([$agendamento_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dados) {
        die("Agendamento não encontrado");
    }
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
            // Atualiza agendamento
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
                $agendamento_id
            ]);
            
            header("Location: ver_agendamento.php?id=" . $dados['requisicao_id'] . "&msg=Agendamento atualizado com sucesso");
            exit();
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar agendamento: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agendamento</title>
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
        .form-agendamento {
            margin-top: 20px;
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
        .btn-voltar {
            background-color: #6c757d;
            margin-left: 10px;
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
        <h1>Editar Agendamento</h1>
        
        <!-- Síntese da Requisição -->
        <div class="sintese-requisicao">
            <h2>Síntese da Requisição</h2>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($dados['nome_razao_social']); ?></p>
            <p><strong>Descrição:</strong> <?php echo htmlspecialchars(substr($dados['descricao'], 0, 100)); ?>...</p>
            <p><strong>Data Criação:</strong> <?php echo date('d/m/Y', strtotime($dados['data_criacao'])); ?></p>
        </div>
       <div class="agendamentos-relacionados">
            <h2>Outros Agendamentos</h2>
            
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
            
            <?php if (empty($agendamentos_filtrados)): ?>
                <p>Nenhum outro agendamento encontrado com os filtros selecionados.</p>
            <?php else: ?>
                <table class="tabela-agendamentos">
                    <thead>
                        <tr>
                            <th>Colaborador</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Cliente</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos_filtrados as $agendamento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agendamento['nome_colaborador']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($agendamento['data_inicio_obra'])); ?></td>
                                <td><?php echo substr($agendamento['hora_inicio'], 0, 5) . ' - ' . substr($agendamento['hora_fim'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['nome_razao_social']); ?></td>
                                <td><?php echo htmlspecialchars(substr($agendamento['descricao'], 0, 30)) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Formulário de Edição -->
        <div class="form-agendamento">
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
                                    <?php echo ($dados['responsavel'] == $colab['nome']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($colab['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_inicio">Data *</label>
                        <input type="date" id="data_inicio" name="data_inicio" 
                               value="<?php echo htmlspecialchars($dados['data_inicio_obra']); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="hora_inicio">Hora Início *</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" 
                               value="<?php echo substr(htmlspecialchars($dados['hora_inicio']), 0, 5); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora_fim">Hora Fim *</label>
                        <input type="time" id="hora_fim" name="hora_fim" 
                               value="<?php echo substr(htmlspecialchars($dados['hora_fim']), 0, 5); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($dados['observacoes']); ?></textarea>
                </div>
                
                <button type="submit" class="btn">Atualizar Agendamento</button>
                <a href="#" onclick="voltarPagina()" class="btn btn-voltar">Cancelar</a>
            </form>
        </div>
    </div>
    
    <script>
		  function aplicarFiltros() {
            const colaborador = document.getElementById('colaborador-filtro').value;
            const data = document.getElementById('data-filtro').value;
            
            let url = `editar_agendamento.php?id=<?php echo $agendamento_id; ?>`;
            
            if (colaborador) {
                url += `&colaborador=${encodeURIComponent(colaborador)}`;
            }
            
            if (data) {
                url += `&data=${data}`;
            }
            
            window.location.href = url;
        }
        
        function limparFiltros() {
            window.location.href = `editar_agendamento.php?id=<?php echo $agendamento_id; ?>`;
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
		function voltarPagina() {
    window.history.back();
}
    </script>
</body>
</html>