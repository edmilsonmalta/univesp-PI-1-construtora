<?php
// Configurações do banco de dados
$host = 'localhost';
$dbname = 'construtora';
$username = 'admin';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Verificar se o ID foi passado na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: lista_colaboradores.php");
    exit();
}

$id = $_GET['id'];

// Verificar se o formulário foi submetido (confirmação de exclusão)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM db_colaboradores WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirecionar com mensagem de sucesso
        header("Location: lista_colaboradores.php?msg=Colaborador excluído com sucesso");
        exit();
    } catch (PDOException $e) {
        die("Erro ao excluir colaborador: " . $e->getMessage());
    }
}

// Buscar nome do colaborador para exibir na confirmação
$nome_colaborador = '';
try {
    $stmt = $pdo->prepare("SELECT nome FROM db_colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($colaborador) {
        $nome_colaborador = $colaborador['nome'];
    } else {
        header("Location: lista_colaboradores.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar colaborador: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Colaborador</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #333;
        }
        .confirmation-message {
            margin: 20px 0;
            font-size: 18px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-confirm {
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-cancel {
            background-color: #2196F3;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excluir Colaborador</h1>
        
        <div class="confirmation-message">
            Tem certeza que deseja excluir o colaborador <strong><?php echo htmlspecialchars($nome_colaborador); ?></strong>?
        </div>
        
        <form method="POST">
            <button type="submit" class="btn btn-confirm">Confirmar Exclusão</button>
            <a href="lista_colaboradores.php" class="btn btn-cancel">Cancelar</a>
        </form>
    </div>
</body>
</html>