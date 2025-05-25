<?php
include("../conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Atualizar status se houver requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['requisicao_id']) && isset($_POST['novo_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE db_requisicoes_obra SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['novo_status'], $_POST['requisicao_id']]);
        // Redireciona para evitar reenvio do formulário
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        die("Erro ao atualizar status: " . $e->getMessage());
    }
}

// Configuração da paginação
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Conta o total de requisições
$total_obras = $pdo->query("SELECT COUNT(DISTINCT r.id) FROM db_requisicoes_obra r")->fetchColumn();
$total_paginas = ceil($total_obras / $registros_por_pagina);

// Busca obras com paginação
try {
    $stmt = $pdo->prepare("SELECT 
                      r.id, r.descricao, r.data_criacao, r.prioridade, r.status,
                      c.nome_razao_social,
                      a.id as agendamento_id, a.data_inicio_obra, a.data_fim_obra, a.hora_inicio, a.hora_fim, a.status as status_agendamento,
                      (SELECT COUNT(*) FROM db_agendamento a WHERE a.requisicao_id = r.id) as possui_agendamento
                      FROM db_requisicoes_obra r
                      JOIN db_cliente c ON r.cliente_id = c.id
                      LEFT JOIN db_agendamento a ON a.requisicao_id = r.id
                      GROUP BY r.id
                      ORDER BY r.data_criacao DESC
                      LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar obras: " . $e->getMessage());
}

// Opções de status conforme a tabela
$opcoes_status = ['Pendente', 'Em análise', 'Aprovada', 'Em andamento', 'Concluída', 'Cancelada'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Obras Requisitadas</title>
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
            margin-bottom: 30px;
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
            font-weight: bold;
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
            font-size: 14px;
            margin: 2px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .btn-agendar {
            background-color: #2196F3;
        }
        .btn-ver-agendamento {
            background-color: #17a2b8;
        }
        .btn-editar {
            background-color: #ffc107;
        }
        .btn-visualizar {
            background-color: #6c757d;
        }
        .btn-financeiro {
            background-color: #28a745;
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
        .status-cancelada {
            background-color: #f44336;
            color: white;
        }
        .status-concluida {
            background-color: #007bff;
            color: white;
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
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .search-container input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-container button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .info-agendamento {
            font-size: 12px;
            line-height: 1.4;
        }
        .info-agendamento strong {
            display: inline-block;
            min-width: 80px;
        }
        .acoes {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
		.dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        .dropdown-content a {
            color: black;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 13px;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .status-dropdown {
            cursor: pointer;
            position: relative;
        }
        .status-dropdown:hover {
            text-decoration: underline;
        }
        form {
            margin: 0;
            padding: 0;
        }
        .status-concluida {
            background-color: #007bff;
            color: white;
        }
        .status-em-analise {
            background-color: #17a2b8;
            color: white;
        }
        .status-em-andamento {
            background-color: #6610f2;
            color: white;
        }
		.dropdown-content button {
    color: black;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    font-size: 13px;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
}

.dropdown-content button:hover {
    background-color: #f1f1f1;
}

.dropdown-content form {
    margin: 0;
    padding: 0;
}
    </style>
</head>
<body>
 <div class="container">
        <h1>Obras Requisitadas</h1>
        
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Pesquisar por cliente, descrição...">
            <button type="button" class="btn" onclick="searchTable()">Pesquisar</button>
        </div>
        
        <table id="obrasTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Descrição</th>
                    <th>Data Criação</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Agendamento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($obras as $obra): ?>
                <tr>
                    <td><?php echo htmlspecialchars($obra['id']); ?></td>
                    <td><?php echo htmlspecialchars($obra['nome_razao_social']); ?></td>
                    <td><?php echo htmlspecialchars(substr($obra['descricao'], 0, 50)); ?>...</td>
                    <td><?php echo date('d/m/Y', strtotime($obra['data_criacao'])); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($obra['prioridade'])); ?></td>
<td>
    <div class="dropdown">
        <div class="status status-<?php echo strtolower(str_replace(' ', '-', $obra['status'])); ?> status-dropdown">
            <?php echo htmlspecialchars($obra['status']); ?>
        </div>
        <div class="dropdown-content">
            <?php foreach ($opcoes_status as $status): ?>
                <?php if ($status != $obra['status']): ?>
                    <form method="post" action="" style="display: inline;">
                        <input type="hidden" name="requisicao_id" value="<?php echo $obra['id']; ?>">
                        <input type="hidden" name="novo_status" value="<?php echo $status; ?>">
                        <button type="submit" style="background:none; border:none; width:100%; text-align:left; padding:8px 12px; cursor:pointer;">
                            <?php echo $status; ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</td>
                    <td>
                        <?php if ($obra['possui_agendamento'] > 0): ?>
                            <div class="info-agendamento">
                                <div><strong>Início:</strong> <?php echo date('d/m/Y', strtotime($obra['data_inicio_obra'])); ?> às <?php echo substr($obra['hora_inicio'], 0, 5); ?></div>
                                <div><strong>Término:</strong> <?php echo date('d/m/Y', strtotime($obra['data_fim_obra'])); ?> às <?php echo substr($obra['hora_fim'], 0, 5); ?></div>
                                <?php if (!empty($obra['status_agendamento'])): ?>
                                    <div><strong>Status:</strong> <span class="status status-<?php echo strtolower($obra['status_agendamento']); ?>"><?php echo htmlspecialchars($obra['status_agendamento']); ?></span></div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="status">Não agendado</span>
                        <?php endif; ?>
                    </td>
<td class="acoes">
    <?php if ($obra['possui_agendamento'] > 0): ?>
        <?php if (isset($obra['status_agendamento']) && strtolower($obra['status_agendamento']) == 'concluida'): ?>
            <a href="encaminhar_financeira.php?id=<?php echo $obra['id']; ?>" class="btn btn-financeiro">Enviar para Financeiro</a>
        <?php else: ?>
            <a href="ver_agendamento.php?id=<?php echo $obra['id']; ?>" class="btn btn-ver-agendamento">Ver Agendamento</a>
            <a href="editar_agendamento.php?id=<?php echo $obra['agendamento_id']; ?>" class="btn btn-editar">Editar</a>
        <?php endif; ?>
    <?php else: ?>
        <a href="agendamento.php?id=<?php echo $obra['id']; ?>" class="btn btn-agendar">Agendar</a>
    <?php endif; ?>
    <a href="visualizar_obra.php?id=<?php echo $obra['id']; ?>" class="btn btn-visualizar">Visualizar Completo</a>
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
       function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("obrasTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const tdArray = tr[i].getElementsByTagName("td");
                
                for (let j = 0; j < tdArray.length - 1; j++) {
                    const td = tdArray[j];
                    if (td) {
                        const txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? "" : "none";
            }
        }

        // Permite pressionar Enter para pesquisar
        document.getElementById("searchInput").addEventListener("keyup", function(event) {
            if (event.key === "Enter") {
                searchTable();
            }
        });
    </script>
</body>
</html>