
<?php
require_once "conexao.php";

$erro = "";
$enderecoEditar = null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idTexto = trim($_POST["id"] ?? "");
    $id = $idTexto !== ""
        ? filter_var($idTexto, FILTER_VALIDATE_INT)
        : null;

    $tipo = $_POST["tipo"] ?? "";
    $cep = trim($_POST["cep"] ?? "");
    $rua = trim($_POST["rua"] ?? "");
    $numero = trim($_POST["numero"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $estado = strtoupper(trim($_POST["estado"] ?? ""));

    
    if (
        !in_array($tipo, ["Origem", "Destino"], true) ||
        $cep === "" ||
        $rua === "" ||
        $numero === "" ||
        $bairro === "" ||
        $cidade === "" ||
        strlen($estado) !== 2 ||
        ($idTexto !== "" && $id === false)
    ) {
        $erro = "Preencha todos os campos corretamente.";
    } else {

        
        if ($id !== null) {

            $sql = "UPDATE enderecos SET
                    tipo = :tipo,
                    cep = :cep,
                    rua = :rua,
                    numero = :numero,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado
                    WHERE id = :id";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":tipo" => $tipo,
                ":cep" => $cep,
                ":rua" => $rua,
                ":numero" => $numero,
                ":bairro" => $bairro,
                ":cidade" => $cidade,
                ":estado" => $estado,
                ":id" => $id
            ]);

        } else {

           
            $sql = "INSERT INTO enderecos
                    (tipo, cep, rua, numero, bairro, cidade, estado)
                    VALUES
                    (:tipo, :cep, :rua, :numero, :bairro, :cidade, :estado)";

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ":tipo" => $tipo,
                ":cep" => $cep,
                ":rua" => $rua,
                ":numero" => $numero,
                ":bairro" => $bairro,
                ":cidade" => $cidade,
                ":estado" => $estado
            ]);
        }

        header("Location: enderecos.php");
        exit;
    }

   
    $enderecoEditar = [
        "id" => $idTexto,
        "tipo" => $tipo,
        "cep" => $cep,
        "rua" => $rua,
        "numero" => $numero,
        "bairro" => $bairro,
        "cidade" => $cidade,
        "estado" => $estado
    ];
}


if ($enderecoEditar === null && isset($_GET["editar"])) {

    $idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

    if ($idEditar) {
        $comando = $conexao->prepare(
            "SELECT * FROM enderecos WHERE id = :id"
        );

        $comando->execute([":id" => $idEditar]);

        $enderecoEditar = $comando->fetch(PDO::FETCH_ASSOC);
    }
}


$comando = $conexao->query(
    "SELECT * FROM enderecos ORDER BY id DESC"
);

$enderecos = $comando->fetchAll(PDO::FETCH_ASSOC);


function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Endereços - Gerenciador Logístico</title>

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

            <h2>Cadastro de Endereços</h2>
            <p>Cadastre os endereços de origem e destino dos fretes.</p>

            <div class="cartao">

                <h4>
                    <?= $enderecoEditar ? "Editar Endereço" : "Novo Endereço" ?>
                </h4>

                <?php if ($erro !== ""): ?>
                    <div class="alert alert-danger">
                        <?= escapar($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="enderecos.php">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= escapar($enderecoEditar["id"] ?? "") ?>"
                    >

                    <div class="mb-3">
                        <label class="form-label">Tipo de endereço</label>

                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>

                            <option
                                value="Origem"
                                <?= ($enderecoEditar["tipo"] ?? "") === "Origem" ? "selected" : "" ?>
                            >
                                Origem
                            </option>

                            <option
                                value="Destino"
                                <?= ($enderecoEditar["tipo"] ?? "") === "Destino" ? "selected" : "" ?>
                            >
                                Destino
                            </option>
                        </select>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">CEP</label>
                            <input
                                type="text"
                                name="cep"
                                class="form-control"
                                maxlength="9"
                                required
                                value="<?= escapar($enderecoEditar["cep"] ?? "") ?>"
                            >
                        </div>

                        <div class="col-md-8 mb-3">
                            <label class="form-label">Rua</label>
                            <input
                                type="text"
                                name="rua"
                                class="form-control"
                                required
                                value="<?= escapar($enderecoEditar["rua"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Número</label>
                            <input
                                type="text"
                                name="numero"
                                class="form-control"
                                required
                                value="<?= escapar($enderecoEditar["numero"] ?? "") ?>"
                            >
                        </div>

                        <div class="col-md-9 mb-3">
                            <label class="form-label">Bairro</label>
                            <input
                                type="text"
                                name="bairro"
                                class="form-control"
                                required
                                value="<?= escapar($enderecoEditar["bairro"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-8 mb-3">
                            <label class="form-label">Cidade</label>
                            <input
                                type="text"
                                name="cidade"
                                class="form-control"
                                required
                                value="<?= escapar($enderecoEditar["cidade"] ?? "") ?>"
                            >
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado (UF)</label>
                            <input
                                type="text"
                                name="estado"
                                class="form-control"
                                maxlength="2"
                                placeholder="Ex.: PR"
                                required
                                value="<?= escapar($enderecoEditar["estado"] ?? "") ?>"
                            >
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $enderecoEditar ? "Salvar Alterações" : "Cadastrar Endereço" ?>
                    </button>

                    <?php if ($enderecoEditar): ?>
                        <a href="enderecos.php" class="btn btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <div class="cartao">

                <h4>Endereços Cadastrados</h4>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>CEP</th>
                                <th>Rua</th>
                                <th>Número</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($enderecos) > 0): ?>

                                <?php foreach ($enderecos as $endereco): ?>

                                    <tr>
                                        <td><?= escapar($endereco["id"]) ?></td>
                                        <td><?= escapar($endereco["tipo"]) ?></td>
                                        <td><?= escapar($endereco["cep"]) ?></td>
                                        <td><?= escapar($endereco["rua"]) ?></td>
                                        <td><?= escapar($endereco["numero"]) ?></td>
                                        <td><?= escapar($endereco["bairro"]) ?></td>
                                        <td><?= escapar($endereco["cidade"]) ?></td>
                                        <td><?= escapar($endereco["estado"]) ?></td>

                                        <td>
                                            <a
                                                href="enderecos.php?editar=<?= escapar($endereco["id"]) ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                Editar
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" class="text-center">
                                        Nenhum endereço cadastrado.
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
