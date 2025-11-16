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
	<meta name="mobile-web-app-capable" content="yes">
    <title>Lista de Obras Requisitadas</title>
    <style>
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 10px;
        background-color: #f5f5f5;
        font-size: 14px;
    }
    
    .container {
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        overflow-x: auto;
    }
    
    h1 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
        font-size: 1.5em;
    }
    
    /* Tabela responsiva */
    .table-container {
        overflow-x: auto;
        margin-top: 15px;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px; /* Largura mínima para manter legibilidade */
    }
    
    th, td {
        padding: 10px 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        font-size: 13px;
    }
    
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        white-space: nowrap;
    }
    
    tr:hover {
        background-color: #f9f9f9;
    }
    
    /* Botões responsivos */
    .btn {
        display: inline-block;
        padding: 6px 10px;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 12px;
        margin: 2px 0;
        text-align: center;
        width: 100%;
    }
    
    .btn-agendar {
        background-color: #2196F3;
    }
    
    .btn-ver-agendamento {
        background-color: #17a2b8;
    }
    
    .btn-editar {
        background-color: #ffc107;
        color: #000;
    }
    
    .btn-visualizar {
        background-color: #6c757d;
    }
    
    .btn-financeiro {
        background-color: #28a745;
    }
    
    /* Status */
    .status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        white-space: nowrap;
    }
    
    .status-pendente { background-color: #ff9800; color: white; }
    .status-aprovada { background-color: #4CAF50; color: white; }
    .status-cancelada { background-color: #f44336; color: white; }
    .status-concluida { background-color: #007bff; color: white; }
    .status-em-analise { background-color: #17a2b8; color: white; }
    .status-em-andamento { background-color: #6610f2; color: white; }
    
    /* Paginação responsiva */
    .paginacao {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 20px;
        gap: 5px;
    }
    
    .paginacao a {
        padding: 8px 12px;
        text-decoration: none;
        color: #2196F3;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        min-width: 40px;
        text-align: center;
    }
    
    .paginacao a.active {
        background-color: #2196F3;
        color: white;
        border: 1px solid #2196F3;
    }
    
    /* Barra de pesquisa */
    .search-container {
        margin-bottom: 15px;
        display: flex;
        gap: 8px;
        flex-direction: column;
    }
    
    @media (min-width: 768px) {
        .search-container {
            flex-direction: row;
        }
    }
    
    .search-container input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .search-container button {
        padding: 10px 15px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        white-space: nowrap;
    }
    
    /* Informações de agendamento */
    .info-agendamento {
        font-size: 11px;
        line-height: 1.4;
    }
    
    .info-agendamento strong {
        display: inline-block;
        min-width: 60px;
    }
    
    /* Ações em coluna */
    .acoes {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 120px;
    }
    
    /* Dropdown responsivo */
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 140px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1000;
        border-radius: 4px;
        left: 0;
        right: auto;
    }
    
    @media (max-width: 480px) {
        .dropdown-content {
            right: 0;
            left: auto;
        }
    }
    
    .dropdown-content button {
        color: black;
        padding: 8px 12px;
        text-decoration: none;
        display: block;
        font-size: 12px;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
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
    
    /* Layout para telas maiores */
    @media (min-width: 768px) {
        body {
            padding: 20px;
            font-size: 16px;
        }
        
        .container {
            padding: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 12px;
            font-size: 13px;
            width: auto;
        }
        
        .acoes {
            flex-direction: row;
            flex-wrap: wrap;
        }
    }
    
    @media (min-width: 1024px) {
        .container {
            max-width: 1200px;
        }
        
        table {
            min-width: auto;
        }
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
        <div class="table-container">
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
	 </div>
	 
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
		
		 // Fechar dropdown ao tocar fora (para mobile)
    document.addEventListener('touchstart', function(event) {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(function(dropdown) {
            if (!dropdown.parentElement.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    });

    // Melhorar toque nos botões
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(function(btn) {
            btn.style.cursor = 'pointer';
        });
    });
    </script>
</body>
</html>