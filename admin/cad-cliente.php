<?php
include("../conf/connect.php");

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Processamento do formulário
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação e sanitização dos dados
        $tipo = $_POST['tipo'];
        $cpf_cnpj = preg_replace('/[^0-9]/', '', $_POST['cpf_cnpj']);
        $nome_razao_social = htmlspecialchars($_POST['nome_razao_social']);
        $nome_fantasia = isset($_POST['nome_fantasia']) ? htmlspecialchars($_POST['nome_fantasia']) : null;
		$responsavel = isset($_POST['responsavel']) ? htmlspecialchars($_POST['responsavel']) : null;
        $data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $endereco = htmlspecialchars($_POST['endereco']);
        $numero = htmlspecialchars($_POST['numero']);
        $complemento = isset($_POST['complemento']) ? htmlspecialchars($_POST['complemento']) : null;
        $bairro = htmlspecialchars($_POST['bairro']);
        $cidade = htmlspecialchars($_POST['cidade']);
        $estado = $_POST['estado'];
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep']);

        // Validação do CPF/CNPJ
        if ($tipo == 'PF' && !validarCPF($cpf_cnpj)) {
            throw new Exception("CPF inválido.");
        } elseif ($tipo == 'PJ' && !validarCNPJ($cpf_cnpj)) {
            throw new Exception("CNPJ inválido.");
        }

        // Inserção no banco de dados
        $stmt = $pdo->prepare("INSERT INTO db_cliente (
            tipo, cpf_cnpj, nome_razao_social, nome_fantasia, data_nascimento,
            telefone, email, endereco, numero, complemento, bairro, cidade, estado, cep, responsavel
        ) VALUES (
            :tipo, :cpf_cnpj, :nome_razao_social, :nome_fantasia, :data_nascimento,
            :telefone, :email, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, :responsavel
        )");

        $stmt->execute([
            ':tipo' => $tipo,
            ':cpf_cnpj' => $cpf_cnpj,
            ':nome_razao_social' => $nome_razao_social,
            ':nome_fantasia' => $nome_fantasia,
            ':data_nascimento' => $data_nascimento,
            ':telefone' => $telefone,
            ':email' => $email,
            ':endereco' => $endereco,
            ':numero' => $numero,
            ':complemento' => $complemento,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':cep' => $cep,
			'responsavel' => $responsavel
        ]);

        $mensagem = "Cliente cadastrado com sucesso!";
    } catch (Exception $e) {
        $mensagem = "Erro ao cadastrar cliente: " . $e->getMessage();
    }
}

// Funções de validação
function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Cálculo dos dígitos verificadores
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

function validarCNPJ($cnpj) {
    // Remove caracteres não numéricos
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    // Cálculo do primeiro dígito verificador
    $soma = 0;
    $peso = 5;
    for ($i = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $peso;
        $peso = ($peso == 2) ? 9 : $peso - 1;
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Cálculo do segundo dígito verificador
    $soma = 0;
    $peso = 6;
    for ($i = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $peso;
        $peso = ($peso == 2) ? 9 : $peso - 1;
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica os dígitos verificadores
    if ($cnpj[12] != $digito1 || $cnpj[13] != $digito2) {
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
    <title>Cadastro de Clientes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .radio-group {
            display: flex;
            gap: 20px;
        }
        .radio-option {
            display: flex;
            align-items: center;
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
        .mensagem {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .sucesso {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .erro {
            background-color: #f2dede;
            color: #a94442;
        }
        .pf-fields, .pj-fields {
            display: none;
        }
    </style>
    <script>
        function toggleFields() {
            const tipo = document.querySelector('input[name="tipo"]:checked').value;
            
            if (tipo === 'PF') {
                document.getElementById('pf-fields').style.display = 'block';
                document.getElementById('pj-fields').style.display = 'none';
                document.getElementById('cpf_cnpj').placeholder = 'Digite o CPF';
                document.getElementById('cpf_cnpj').maxLength = 14;
            } else {
                document.getElementById('pf-fields').style.display = 'none';
                document.getElementById('pj-fields').style.display = 'block';
                document.getElementById('cpf_cnpj').placeholder = 'Digite o CNPJ';
                document.getElementById('cpf_cnpj').maxLength = 18;
            }
        }

        function formatarCPFCNPJ(input) {
            const tipo = document.querySelector('input[name="tipo"]:checked').value;
            let value = input.value.replace(/\D/g, '');
            
            if (tipo === 'PF') {
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 9) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
                } else if (value.length > 6) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{3}).*/, '$1.$2.$3');
                } else if (value.length > 3) {
                    value = value.replace(/^(\d{3})(\d{3}).*/, '$1.$2');
                }
            } else {
                if (value.length > 14) value = value.substring(0, 14);
                
                if (value.length > 12) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
                } else if (value.length > 8) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4}).*/, '$1.$2.$3/$4');
                } else if (value.length > 5) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3}).*/, '$1.$2.$3');
                } else if (value.length > 2) {
                    value = value.replace(/^(\d{2})(\d{3}).*/, '$1.$2');
                }
            }
            
            input.value = value;
        }

        function formatarTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{4}).*/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/^(\d{2}).*/, '($1)');
            }
            
            input.value = value;
        }

        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            
            if (value.length > 8) value = value.substring(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d{3}).*/, '$1-$2');
            }
            
            input.value = value;
        }
		
    </script>
</head>
<body>
    <div class="container">
        <h1>Cadastro de Clientes</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Tipo de Cliente:</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="tipo-pf" name="tipo" value="PF" checked onchange="toggleFields()">
                        <label for="tipo-pf" style="font-weight: normal;">Pessoa Física</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="tipo-pj" name="tipo" value="PJ" onchange="toggleFields()">
                        <label for="tipo-pj" style="font-weight: normal;">Pessoa Jurídica</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="cpf_cnpj">CPF/CNPJ:</label>
                <input type="text" id="cpf_cnpj" name="cpf_cnpj" placeholder="Digite o CPF" 
                       oninput="formatarCPFCNPJ(this)" required>
            </div>
            
            <div class="form-group">
                <label for="nome_razao_social">Nome/Razão Social:</label>
                <input type="text" id="nome_razao_social" name="nome_razao_social" required>
            </div>
            
            <div id="pf-fields" class="pf-fields">
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento">
                </div>
            </div>
            
            <div id="pj-fields" class="pj-fields" style="display: none;">
                <div class="form-group">
                    <label for="nome_fantasia">Nome Fantasia:</label>
                    <input type="text" id="nome_fantasia" name="nome_fantasia">
                </div>
                <div class="form-group">
                    <label for="responsavel">Responsável pela Empresa:</label>
                    <input type="text" id="responsavel" name="responsavel" class='pj-required'>
                </div>
            </div>
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" oninput="formatarTelefone(this)" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required>
            </div>
            
            <div class="form-group">
                <label for="numero">Número:</label>
                <input type="text" id="numero" name="numero" required>
            </div>
            
            <div class="form-group">
                <label for="complemento">Complemento:</label>
                <input type="text" id="complemento" name="complemento">
            </div>
            
            <div class="form-group">
                <label for="bairro">Bairro:</label>
                <input type="text" id="bairro" name="bairro" required>
            </div>
            
            <div class="form-group">
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            
            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                    <option value="">Selecione</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option>
                    <option value="BA">Bahia</option>
                    <option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option>
                    <option value="ES">Espírito Santo</option>
                    <option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option>
                    <option value="MT">Mato Grosso</option>
                    <option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option>
                    <option value="PA">Pará</option>
                    <option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option>
                    <option value="PE">Pernambuco</option>
                    <option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option>
                    <option value="RN">Rio Grande do Norte</option>
                    <option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option>
                    <option value="RR">Roraima</option>
                    <option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option>
                    <option value="SE">Sergipe</option>
                    <option value="TO">Tocantins</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cep">CEP:</label>
                <input type="text" id="cep" name="cep" oninput="formatarCEP(this)" required>
            </div>
            
            <div class="form-group">
                <button type="submit">Cadastrar</button>
            </div>
        </form>
    </div>
    
    <script>
        // Inicializa os campos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields();
        });
		// Função de validação dos campos PJ
    function validarCamposPJ() {
        if (document.querySelector('input[name="tipo"]:checked').value === 'PJ') {
            const camposPJ = document.querySelectorAll('.pj-required');
            for (const campo of camposPJ) {
                if (!campo.value.trim()) {
                    alert('Por favor, preencha todos os campos de Pessoa Jurídica');
                    campo.focus();
                    return false;
                }
            }
        }
        return true;
    }

    // ADICIONE AQUI O EVENTO DE SUBMIT (esta é a parte que você precisa incluir)
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validarCamposPJ()) {
            e.preventDefault(); // Impede o envio se a validação falhar
        }
    });
    </script>
</body>
</html>