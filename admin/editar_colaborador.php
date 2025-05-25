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

// Buscar dados do colaborador
$colaborador = null;
try {
    $stmt = $pdo->prepare("SELECT id, nome, cpf, funcao FROM db_colaboradores WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colaborador) {
        header("Location: lista_colaboradores.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar colaborador: " . $e->getMessage());
}

// Buscar funções disponíveis
$funcoes = [];
try {
    $stmt = $pdo->query("SELECT nome_funcao FROM db_funcoes ORDER BY nome_funcao");
    $funcoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Erro ao carregar funções: " . $e->getMessage());
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $funcao = $_POST['funcao'] ?? '';
    
    // Validações
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($cpf)) {
        $erros[] = "O CPF é obrigatório.";
    } elseif (!validarCPF($cpf)) {
        $erros[] = "CPF inválido.";
    }
    
    if (empty($funcao)) {
        $erros[] = "A função é obrigatória.";
    }
    
    if (empty($erros)) {
        try {
            // Atualizar no banco de dados
            $stmt = $pdo->prepare("UPDATE db_colaboradores SET nome = ?, cpf = ?, funcao = ? WHERE id = ?");
            $stmt->execute([$nome, $cpf, $funcao, $id]);
            
            $mensagem = "Colaborador atualizado com sucesso!";
            // Atualizar os dados exibidos
            $colaborador['nome'] = $nome;
            $colaborador['cpf'] = $cpf;
            $colaborador['funcao'] = $funcao;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $erros[] = "Este CPF já está cadastrado para outro colaborador.";
            } else {
                $erros[] = "Erro ao atualizar: " . $e->getMessage();
            }
        }
    }
}

// Função para validar CPF (simplificada)
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) {
        return false;
    }
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    return true;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Colaborador</title>
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
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .btn-voltar {
            display: inline-block;
            margin-top: 15px;
            background-color: #2196F3;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Colaborador</h1>
        
        <a href="lista_colaboradores.php" class="btn-voltar">← Voltar para a lista</a>
        
        <?php if (!empty($erros)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensagem)): ?>
            <div class="success">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($colaborador['nome']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($colaborador['cpf']); ?>" placeholder="000.000.000-00" required>
            </div>
            
            <div class="form-group">
                <label for="funcao">Função:</label>
                <select id="funcao" name="funcao" required>
                    <option value="">Selecione uma função</option>
                    <?php foreach ($funcoes as $f): ?>
                        <option value="<?php echo htmlspecialchars($f); ?>" <?php echo ($f == $colaborador['funcao']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>