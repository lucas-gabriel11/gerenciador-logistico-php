
<?php
require_once "conexao.php";

$erro = "";
$transportadorEditar = null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idTexto = trim($_POST["id"] ?? "");
    $id = $idTexto !== ""
        ? filter_var($idTexto, FILTER_VALIDATE_INT)
        : null;

    $nome = trim($_POST["nome"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $empresa = trim($_POST["empresa"] ?? "");

   
    if (
        $nome === "" ||
        $documento === "" ||
        $telefone === "" ||
        ($idTexto !== "" && $id === false)
    ) {
        $erro = "Preencha os campos obrigatórios corretamente.";
    } else {

        
        if ($id !== null) {

            $sql = "UPDATE transportadores SET
                    nome = :nome,
                    documento = :documento,
                    telefone = :telefone,
                    empresa = :empresa
                    WHERE id = :id";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":nome" => $nome,
                ":documento" => $documento,
                ":telefone" => $telefone,
                ":empresa" => $empresa,
                ":id" => $id
            ]);

        } else {

            
            $sql = "INSERT INTO transportadores
                    (nome, documento, telefone, empresa)
                    VALUES
                    (:nome, :documento, :telefone, :empresa)";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":nome" => $nome,
                ":documento" => $documento,
                ":telefone" => $telefone,
                ":empresa" => $empresa
            ]);
        }

        header("Location: transportadores.php");
        exit;
    }

    
    $transportadorEditar = [
        "id" => $idTexto,
        "nome" => $nome,
        "documento" => $documento,
        "telefone" => $telefone,
        "empresa" => $empresa
    ];
}


if ($transportadorEditar === null && isset($_GET["editar"])) {

    $idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

    if ($idEditar) {
        $comando = $conexao->prepare(
            "SELECT * FROM transportadores WHERE id = :id"
        );

        $comando->execute([":id" => $idEditar]);

        $transportadorEditar = $comando->fetch(PDO::FETCH_ASSOC);
    }
}


$comando = $conexao->query(
    "SELECT * FROM transportadores ORDER BY id DESC"
);

$transportadores = $comando->fetchAll(PDO::FETCH_ASSOC);


function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transportadores - Gerenciador Logístico</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f4f6f9;
        }

        .menu {
            background-color: #17365d;
            min-height: 100vh;
            color: white;
            padding: 25px 15px;
        }

        .menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu a:hover {
            background-color: #284d78;
        }

        .conteudo {
            padding: 30px;
        }

        .cartao {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-2 menu">
            <h4>Logística</h4>
            <hr>

            <a href="index.php">Início</a>
            <a href="produtos.php">Produtos</a>
            <a href="enderecos.php">Endereços</a>
            <a href="transportadores.php">Transportadores</a>
            <a href="fretes.php">Fretes</a>
            <a href="relatorios.php">Relatórios</a>
        </aside>

        <main class="col-md-10 conteudo">

            <h2>Cadastro de Transportadores</h2>
            <p>Cadastre e gerencie os responsáveis pelo transporte.</p>

            <div class="cartao">

                <h4>
                    <?= $transportadorEditar
                        ? "Editar Transportador"
                        : "Novo Transportador" ?>
                </h4>

                <?php if ($erro !== ""): ?>
                    <div class="alert alert-danger">
                        <?= escapar($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="transportadores.php">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= escapar($transportadorEditar["id"] ?? "") ?>"
                    >

                    <div class="mb-3">
                        <label class="form-label">Nome do transportador</label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            required
                            value="<?= escapar($transportadorEditar["nome"] ?? "") ?>"
                        >
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">CPF ou CNPJ</label>

                            <input
                                type="text"
                                name="documento"
                                class="form-control"
                                required
                                value="<?= escapar($transportadorEditar["documento"] ?? "") ?>"
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>

                            <input
                                type="text"
                                name="telefone"
                                class="form-control"
                                required
                                value="<?= escapar($transportadorEditar["telefone"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nome da empresa</label>

                        <input
                            type="text"
                            name="empresa"
                            class="form-control"
                            value="<?= escapar($transportadorEditar["empresa"] ?? "") ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $transportadorEditar
                            ? "Salvar Alterações"
                            : "Cadastrar Transportador" ?>
                    </button>

                    <?php if ($transportadorEditar): ?>
                        <a href="transportadores.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <div class="cartao">

                <h4>Transportadores Cadastrados</h4>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>CPF/CNPJ</th>
                                <th>Telefone</th>
                                <th>Empresa</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($transportadores) > 0): ?>

                                <?php foreach ($transportadores as $transportador): ?>

                                    <tr>
                                        <td><?= escapar($transportador["id"]) ?></td>
                                        <td><?= escapar($transportador["nome"]) ?></td>
                                        <td><?= escapar($transportador["documento"]) ?></td>
                                        <td><?= escapar($transportador["telefone"]) ?></td>
                                        <td><?= escapar($transportador["empresa"]) ?></td>

                                        <td>
                                            <a
                                                href="transportadores.php?editar=<?= escapar($transportador["id"]) ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Editar
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="text-center">
                                        Nenhum transportador cadastrado.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

    </div>
</div>

</body>
</html>
