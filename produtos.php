

<?php
require_once "conexao.php";

$erro = "";
$produtoEditar = null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idTexto = trim($_POST["id"] ?? "");
    $id = $idTexto !== ""
        ? filter_var($idTexto, FILTER_VALIDATE_INT)
        : null;

    $nome = trim($_POST["nome"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $peso = filter_var($_POST["peso"] ?? "", FILTER_VALIDATE_FLOAT);
    $quantidade = filter_var($_POST["quantidade"] ?? "", FILTER_VALIDATE_INT);

    
    if (
        $nome === "" ||
        $peso === false ||
        $peso <= 0 ||
        $quantidade === false ||
        $quantidade < 1 ||
        ($idTexto !== "" && $id === false)
    ) {
        $erro = "Preencha os campos corretamente.";
    } else {

       
        if ($id !== null) {

            $sql = "UPDATE produtos
                    SET nome = :nome,
                        descricao = :descricao,
                        peso = :peso,
                        quantidade = :quantidade
                    WHERE id = :id";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":nome" => $nome,
                ":descricao" => $descricao,
                ":peso" => $peso,
                ":quantidade" => $quantidade,
                ":id" => $id
            ]);

        } else {

           
            $sql = "INSERT INTO produtos
                    (nome, descricao, peso, quantidade)
                    VALUES
                    (:nome, :descricao, :peso, :quantidade)";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":nome" => $nome,
                ":descricao" => $descricao,
                ":peso" => $peso,
                ":quantidade" => $quantidade
            ]);
        }

        
        header("Location: produtos.php");
        exit;
    }

    
    $produtoEditar = [
        "id" => $idTexto,
        "nome" => $nome,
        "descricao" => $descricao,
        "peso" => $_POST["peso"] ?? "",
        "quantidade" => $_POST["quantidade"] ?? ""
    ];
}


if ($produtoEditar === null && isset($_GET["editar"])) {

    $idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

    if ($idEditar) {
        $comando = $conexao->prepare(
            "SELECT * FROM produtos WHERE id = :id"
        );

        $comando->execute([":id" => $idEditar]);

        $produtoEditar = $comando->fetch(PDO::FETCH_ASSOC);
    }
}


$comando = $conexao->query(
    "SELECT * FROM produtos ORDER BY id DESC"
);

$produtos = $comando->fetchAll(PDO::FETCH_ASSOC);


function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - Gerenciador Logístico</title>

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

            <h2>Cadastro de Produtos</h2>
            <p>Cadastre e gerencie os produtos transportados.</p>

            <div class="cartao">

                <h4>
                    <?= $produtoEditar ? "Editar Produto" : "Novo Produto" ?>
                </h4>

                <?php if ($erro !== ""): ?>
                    <div class="alert alert-danger">
                        <?= escapar($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="produtos.php">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= escapar($produtoEditar["id"] ?? "") ?>"
                    >

                    <div class="mb-3">
                        <label class="form-label">Nome do produto</label>

                        <input
                            type="text"
                            name="nome"
                            class="form-control"
                            required
                            value="<?= escapar($produtoEditar["nome"] ?? "") ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>

                        <textarea
                            name="descricao"
                            class="form-control"
                            rows="3"
                        ><?= escapar($produtoEditar["descricao"] ?? "") ?></textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Peso (kg)</label>

                            <input
                                type="number"
                                name="peso"
                                class="form-control"
                                step="0.01"
                                min="0.01"
                                required
                                value="<?= escapar($produtoEditar["peso"] ?? "") ?>"
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantidade</label>

                            <input
                                type="number"
                                name="quantidade"
                                class="form-control"
                                min="1"
                                required
                                value="<?= escapar($produtoEditar["quantidade"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $produtoEditar ? "Salvar Alterações" : "Cadastrar Produto" ?>
                    </button>

                    <?php if ($produtoEditar): ?>
                        <a href="produtos.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <div class="cartao">

                <h4>Produtos Cadastrados</h4>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Peso (kg)</th>
                                <th>Quantidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($produtos) > 0): ?>

                                <?php foreach ($produtos as $produto): ?>

                                    <tr>
                                        <td><?= escapar($produto["id"]) ?></td>
                                        <td><?= escapar($produto["nome"]) ?></td>
                                        <td><?= escapar($produto["descricao"]) ?></td>
                                        <td><?= escapar($produto["peso"]) ?></td>
                                        <td><?= escapar($produto["quantidade"]) ?></td>

                                        <td>
                                            <a
                                                href="produtos.php?editar=<?= escapar($produto["id"]) ?>"
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
                                        Nenhum produto cadastrado.
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
