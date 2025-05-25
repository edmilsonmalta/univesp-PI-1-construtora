<?php
include("../conf/connect.php");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Buscar todos os colaboradores
$colaboradores = [];
try {
    $stmt = $pdo->query("SELECT id, nome, cpf, funcao, DATE_FORMAT(data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro FROM db_colaboradores ORDER BY nome");
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar colaboradores: " . $e->getMessage());
}
if (isset($_GET['msg'])) {
    $mensagem = $_GET['msg'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Colaboradores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .actions {
            white-space: nowrap;
        }
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn-add {
            display: inline-block;
            margin-bottom: 20px;
            background-color: #2196F3;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lista de Colaboradores</h1>
        <?php if (isset($mensagem)): ?>
            <div class="success" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        <a href="cadastro_colaborador.php" class="btn btn-add">Novo Colaborador</a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Função</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $colaborador): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($colaborador['id']); ?></td>
                        <td><?php echo htmlspecialchars($colaborador['nome']); ?></td>
                        <td><?php echo htmlspecialchars($colaborador['cpf']); ?></td>
                        <td><?php echo htmlspecialchars($colaborador['funcao']); ?></td>
                        <td><?php echo htmlspecialchars($colaborador['data_cadastro']); ?></td>
                        <td class="actions">
                            <a href="editar_colaborador.php?id=<?php echo $colaborador['id']; ?>" class="btn btn-edit">Editar</a>
                            <a href="excluir_colaborador.php?id=<?php echo $colaborador['id']; ?>" class="btn btn-delete" onclick="return confirm('Tem certeza que deseja excluir este colaborador?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>