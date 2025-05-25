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

// Processamento do formulário de login
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Limpa e valida os dados de entrada
        $login = preg_replace('/[^0-9]/', '', $_POST['login']);
        $senha = $_POST['senha'];
        
        if (empty($login)) {
            throw new Exception("CPF/CNPJ é obrigatório.");
        }
        
        if (empty($senha)) {
            throw new Exception("Senha é obrigatória.");
        }
        
        // Busca o usuário no banco de dados
        $stmt = $pdo->prepare("SELECT u.*, c.nome_razao_social FROM db_usuarios u 
                              JOIN db_cliente c ON u.cliente_id = c.id 
                              WHERE u.login = ?");
        $stmt->execute([$login]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception("CPF/CNPJ não cadastrado.");
        }
        
        // Verifica a senha
        if (!password_verify($senha, $usuario['senha_hash'])) {
            throw new Exception("Senha incorreta.");
        }
        
        // Autenticação bem-sucedida - cria a sessão
        $_SESSION['usuario_id'] = $usuario['cliente_id'];
        $_SESSION['usuario_nome'] = $usuario['nome_razao_social'];
        $_SESSION['usuario_login'] = $usuario['login'];
        
        // Redireciona para a página principal
        header("Location: requisicao_obra.php");
        exit();
        
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Requisição de Obras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .erro {
            background-color: #f2dede;
            color: #a94442;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
        }
        .links {
            margin-top: 20px;
            text-align: center;
        }
        .links a {
            color: #4CAF50;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function formatarLogin(input) {
            // Remove tudo que não é número
            let value = input.value.replace(/\D/g, '');
            
            // Verifica se é CPF (11 dígitos) ou CNPJ (14 dígitos)
            if (value.length > 11) { // CNPJ
                if (value.length > 14) value = value.substring(0, 14);
                
                // Formatação do CNPJ: 00.000.000/0000-00
                if (value.length > 12) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
                } else if (value.length > 8) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4}).*/, '$1.$2.$3/$4');
                } else if (value.length > 5) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3}).*/, '$1.$2.$3');
                } else if (value.length > 2) {
                    value = value.replace(/^(\d{2})(\d{3}).*/, '$1.$2');
                }
            } else { // CPF
                if (value.length > 11) value = value.substring(0, 11);
                
                // Formatação do CPF: 000.000.000-00
                if (value.length > 9) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
                } else if (value.length > 6) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{3}).*/, '$1.$2.$3');
                } else if (value.length > 3) {
                    value = value.replace(/^(\d{3})(\d{3}).*/, '$1.$2');
                }
            }
            
            input.value = value;
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <!-- Adicione sua logo aqui -->
            <h2>Sistema de Requisição de Obras</h2>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem erro">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="login">CPF/CNPJ</label>
                <input type="text" id="login" name="login" placeholder="Digite seu CPF ou CNPJ" 
                       oninput="formatarLogin(this)" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>
            
            <button type="submit">Entrar</button>
            
            <div class="links">
                <a href="recuperar_senha.php">Esqueci minha senha</a>
                <span> | </span>
                <a href="cadastro_cliente.php">Cadastre-se</a>
            </div>
        </form>
    </div>
</body>
</html>