
<?php
require_once "conexao.php";

$erro = "";
$sucesso = "";


$transportadores = $conexao->query(
    "SELECT * FROM transportadores ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

$origens = $conexao->query(
    "SELECT * FROM enderecos WHERE tipo = 'Origem' ORDER BY cidade"
)->fetchAll(PDO::FETCH_ASSOC);

$destinos = $conexao->query(
    "SELECT * FROM enderecos WHERE tipo = 'Destino' ORDER BY cidade"
)->fetchAll(PDO::FETCH_ASSOC);

$produtos = $conexao->query(
    "SELECT * FROM produtos ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $transportadorId = filter_var(
        $_POST["transportador_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    $origemId = filter_var(
        $_POST["origem_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    $destinoId = filter_var(
        $_POST["destino_id"] ?? "",
        FILTER_VALIDATE_INT
    );

    $dataFrete = $_POST["data_frete"] ?? "";

    $produtosIds = $_POST["produtos"] ?? [];
    $quantidades = $_POST["quantidades"] ?? [];

    
    if (
        !$transportadorId ||
        !$origemId ||
        !$destinoId ||
        $origemId === $destinoId ||
        $dataFrete === "" ||
        !is_array($produtosIds) ||
        !is_array($quantidades) ||
        count($produtosIds) === 0 ||
        count($produtosIds) !== count($quantidades)
    ) {
        $erro = "Preencha todos os campos e selecione pelo menos um produto.";
    } else {

        
        $dataValidada = DateTime::createFromFormat("!Y-m-d", $dataFrete);

        if (
            !$dataValidada ||
            $dataValidada->format("Y-m-d") !== $dataFrete
        ) {
            $erro = "Informe uma data válida.";
        }

        
        $itens = [];

        if ($erro === "") {

            foreach ($produtosIds as $indice => $produtoTexto) {

                $produtoId = filter_var(
                    $produtoTexto,
                    FILTER_VALIDATE_INT
                );

                $quantidade = filter_var(
                    $quantidades[$indice] ?? "",
                    FILTER_VALIDATE_INT
                );

                if (!$produtoId || !$quantidade || $quantidade < 1) {
                    $erro = "Confira os produtos e suas quantidades.";
                    break;
                }

                
                if (!isset($itens[$produtoId])) {
                    $itens[$produtoId] = 0;
                }

                $itens[$produtoId] += $quantidade;
            }
        }

        
        if ($erro === "") {

            $verificacoes = [
                ["transportadores", $transportadorId],
                ["enderecos", $origemId],
                ["enderecos", $destinoId]
            ];

            foreach ($verificacoes as $verificacao) {

                $consulta = $conexao->prepare(
                    "SELECT id FROM " . $verificacao[0] . " WHERE id = :id"
                );

                $consulta->execute([":id" => $verificacao[1]]);

                if (!$consulta->fetch()) {
                    $erro = "Um dos registros selecionados não existe.";
                    break;
                }
            }

            foreach ($itens as $produtoId => $quantidade) {

                $consulta = $conexao->prepare(
                    "SELECT id FROM produtos WHERE id = :id"
                );

                $consulta->execute([":id" => $produtoId]);

                if (!$consulta->fetch()) {
                    $erro = "Um dos produtos selecionados não existe.";
                    break;
                }
            }
        }

        
        if ($erro === "") {

            try {

                $conexao->beginTransaction();

                $sqlFrete = "INSERT INTO fretes
                    (transportador_id, origem_id, destino_id, data_frete)
                    VALUES
                    (:transportador, :origem, :destino, :data)";

                $comandoFrete = $conexao->prepare($sqlFrete);

                $comandoFrete->execute([
                    ":transportador" => $transportadorId,
                    ":origem" => $origemId,
                    ":destino" => $destinoId,
                    ":data" => $dataFrete
                ]);

                
                $freteId = $conexao->lastInsertId();

                
                $sqlProduto = "INSERT INTO frete_produtos
                    (frete_id, produto_id, quantidade)
                    VALUES
                    (:frete, :produto, :quantidade)";

                $comandoProduto = $conexao->prepare($sqlProduto);

                foreach ($itens as $produtoId => $quantidade) {

                    $comandoProduto->execute([
                        ":frete" => $freteId,
                        ":produto" => $produtoId,
                        ":quantidade" => $quantidade
                    ]);
                }

                $conexao->commit();

                header("Location: fretes.php");
                exit;

            } catch (PDOException $erroBanco) {

                if ($conexao->inTransaction()) {
                    $conexao->rollBack();
                }

                $erro = "Não foi possível cadastrar o frete. Confira os dados e tente novamente.";
            }
        }
    }
}



$sqlLista = "SELECT
                f.id,
                f.data_frete,
                t.nome AS transportador,
                o.cidade AS cidade_origem,
                d.cidade AS cidade_destino,
                GROUP_CONCAT(
                    CONCAT(p.nome, ' (', fp.quantidade, ')')
                    SEPARATOR ', '
                ) AS produtos
            FROM fretes f
            INNER JOIN transportadores t
                ON f.transportador_id = t.id
            INNER JOIN enderecos o
                ON f.origem_id = o.id
            INNER JOIN enderecos d
                ON f.destino_id = d.id
            INNER JOIN frete_produtos fp
                ON f.id = fp.frete_id
            INNER JOIN produtos p
                ON fp.produto_id = p.id
            GROUP BY
                f.id,
                f.data_frete,
                t.nome,
                o.cidade,
                d.cidade
            ORDER BY f.id DESC";

$fretes = $conexao->query($sqlLista)->fetchAll(PDO::FETCH_ASSOC);



function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fretes - Gerenciador Logístico</title>

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

            <h2>Geração de Fretes</h2>
            <p>Relacione transportadores, endereços e produtos.</p>

            <?php if ($erro !== ""): ?>
                <div class="alert alert-danger">
                    <?= escapar($erro) ?>
                </div>
            <?php endif; ?>

            <div class="cartao">

                <h4>Novo Frete</h4>

                <?php if (
                    count($transportadores) === 0 ||
                    count($origens) === 0 ||
                    count($destinos) === 0 ||
                    count($produtos) === 0
                ): ?>

                    <div class="alert alert-warning">
                        Para gerar um frete, cadastre pelo menos um transportador,
                        um endereço de origem, um endereço de destino e um produto.
                    </div>

                <?php else: ?>

                    <form method="POST" action="fretes.php">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Transportador</label>

                                <select
                                    name="transportador_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Selecione o transportador</option>

                                    <?php foreach ($transportadores as $transportador): ?>
                                        <option value="<?= escapar($transportador["id"]) ?>">
                                            <?= escapar($transportador["nome"]) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data do frete</label>

                                <input
                                    type="date"
                                    name="data_frete"
                                    class="form-control"
                                    value="<?= date("Y-m-d") ?>"
                                    required
                                >
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Endereço de origem</label>

                                <select
                                    name="origem_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Selecione a origem</option>

                                    <?php foreach ($origens as $endereco): ?>
                                        <option value="<?= escapar($endereco["id"]) ?>">
                                            <?= escapar($endereco["rua"]) ?>,
                                            <?= escapar($endereco["numero"]) ?> -
                                            <?= escapar($endereco["cidade"]) ?>/<?= escapar($endereco["estado"]) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Endereço de destino</label>

                                <select
                                    name="destino_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Selecione o destino</option>

                                    <?php foreach ($destinos as $endereco): ?>
                                        <option value="<?= escapar($endereco["id"]) ?>">
                                            <?= escapar($endereco["rua"]) ?>,
                                            <?= escapar($endereco["numero"]) ?> -
                                            <?= escapar($endereco["cidade"]) ?>/<?= escapar($endereco["estado"]) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                        </div>

                        <hr>

                        <h5>Produtos do Frete</h5>

                        <div id="lista-produtos">

                            <div class="row linha-produto mb-3">

                                <div class="col-md-8">
                                    <label class="form-label">Produto</label>

                                    <select
                                        name="produtos[]"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">Selecione um produto</option>

                                        <?php foreach ($produtos as $produto): ?>
                                            <option value="<?= escapar($produto["id"]) ?>">
                                                <?= escapar($produto["nome"]) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Quantidade</label>

                                    <input
                                        type="number"
                                        name="quantidades[]"
                                        class="form-control"
                                        min="1"
                                        value="1"
                                        required
                                    >
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button
                                        type="button"
                                        class="btn btn-danger remover-produto"
                                        disabled
                                    >
                                        X
                                    </button>
                                </div>

                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-primary mb-3"
                            id="adicionar-produto"
                        >
                            + Adicionar outro produto
                        </button>

                        <br>

                        <button type="submit" class="btn btn-primary">
                            Gerar Frete
                        </button>

                    </form>

                <?php endif; ?>

            </div>

            <div class="cartao">

                <h4>Fretes Cadastrados</h4>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Transportador</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Produtos e quantidades</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($fretes) > 0): ?>

                                <?php foreach ($fretes as $frete): ?>

                                    <tr>
                                        <td><?= escapar($frete["id"]) ?></td>
                                        <td><?= date("d/m/Y", strtotime($frete["data_frete"])) ?></td>
                                        <td><?= escapar($frete["transportador"]) ?></td>
                                        <td><?= escapar($frete["cidade_origem"]) ?></td>
                                        <td><?= escapar($frete["cidade_destino"]) ?></td>
                                        <td><?= escapar($frete["produtos"]) ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="text-center">
                                        Nenhum frete cadastrado.
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

<script>
    
    const listaProdutos = document.getElementById("lista-produtos");
    const botaoAdicionar = document.getElementById("adicionar-produto");

    botaoAdicionar.addEventListener("click", function () {

        const primeiraLinha = listaProdutos.querySelector(".linha-produto");
        const novaLinha = primeiraLinha.cloneNode(true);

        novaLinha.querySelector("select").selectedIndex = 0;
        novaLinha.querySelector("input").value = 1;

        novaLinha.querySelector(".remover-produto").disabled = false;

        listaProdutos.appendChild(novaLinha);

    });

    
    listaProdutos.addEventListener("click", function (evento) {

        if (evento.target.classList.contains("remover-produto")) {

            const linhas = listaProdutos.querySelectorAll(".linha-produto");

            if (linhas.length > 1) {
                evento.target.closest(".linha-produto").remove();
            }

        }

    });
</script>

</body>
</html>
