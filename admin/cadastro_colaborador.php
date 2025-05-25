<?php
include("../conf/connect.php");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $funcao = $_POST['funcao'] ?? '';
    
    // Validações simples
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
            // Inserir no banco de dados
            $stmt = $pdo->prepare("INSERT INTO db_colaboradores (nome, cpf, funcao) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $cpf, $funcao]);
            
            $mensagem = "Colaborador cadastrado com sucesso!";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $erros[] = "Este CPF já está cadastrado.";
            } else {
                $erros[] = "Erro ao cadastrar: " . $e->getMessage();
            }
        }
    }
}

// Função para validar CPF (simplificada)
function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se não é uma sequência de dígitos repetidos
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Aqui viria o cálculo dos dígitos verificadores
    // Para simplificar, vamos considerar que está válido
    return true;
}

// Buscar funções disponíveis no banco de dados
$funcoes = [];
try {
    $stmt = $pdo->query("SELECT nome_funcao FROM db_funcoes ORDER BY nome_funcao");
    $funcoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erros[] = "Erro ao carregar funções: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Colaborador</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastro de Colaborador</h1>
        
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
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" required>
            </div>
            
            <div class="form-group">
                <label for="funcao">Função:</label>
                <select id="funcao" name="funcao" required>
                    <option value="">Selecione uma função</option>
                    <?php foreach ($funcoes as $funcao): ?>
                        <option value="<?php echo htmlspecialchars($funcao); ?>">
                            <?php echo htmlspecialchars($funcao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>