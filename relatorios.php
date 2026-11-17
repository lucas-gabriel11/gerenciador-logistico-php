
<?php
require_once "conexao.php";

$data = $_GET["data"] ?? date("Y-m-d");

$dataValidada = DateTime::createFromFormat("!Y-m-d", $data);

if (!$dataValidada || $dataValidada->format("Y-m-d") !== $data) {
    $data = date("Y-m-d");
}


$sql = "SELECT
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
        INNER JOIN transportadores t ON f.transportador_id = t.id
        INNER JOIN enderecos o ON f.origem_id = o.id
        INNER JOIN enderecos d ON f.destino_id = d.id
        INNER JOIN frete_produtos fp ON f.id = fp.frete_id
        INNER JOIN produtos p ON fp.produto_id = p.id
        WHERE f.data_frete = :data
        GROUP BY f.id, f.data_frete, t.nome, o.cidade, d.cidade
        ORDER BY f.id DESC";

$comando = $conexao->prepare($sql);
$comando->execute([":data" => $data]);

$fretes = $comando->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET["baixar"])) {

    $nomeArquivo = "relatorio-fretes-" . $data . ".csv";

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$nomeArquivo\"");

    $saida = fopen("php://output", "w");

    fwrite($saida, "\xEF\xBB\xBF");

    fputcsv(
        $saida,
        ["ID", "Data", "Transportador", "Origem", "Destino", "Produtos"],
        ";"
    );

    foreach ($fretes as $frete) {
        fputcsv($saida, [
            $frete["id"],
            $frete["data_frete"],
            $frete["transportador"],
            $frete["cidade_origem"],
            $frete["cidade_destino"],
            $frete["produtos"]
        ], ";");
    }

    fclose($saida);
    exit;
}

function escapar($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Relatórios - Gerenciador Logístico</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

            <h2>Relatório de Fretes</h2>

            <p>
                Consulte os fretes cadastrados em uma data específica
                e baixe o relatório.
            </p>

            <div class="cartao">

                <form method="GET" action="relatorios.php" class="row align-items-end g-3">

                    <div class="col-md-4">
                        <label for="data" class="form-label">
                            Data dos fretes
                        </label>

                        <input
                            type="date"
                            id="data"
                            name="data"
                            class="form-control"
                            value="<?= escapar($data) ?>"
                            required
                        >
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            Consultar
                        </button>
                    </div>

                    <div class="col-md-auto">
                        <button
                            type="submit"
                            name="baixar"
                            value="1"
                            class="btn btn-success"
                        >
                            Baixar relatório CSV
                        </button>
                    </div>

                </form>

            </div>

            <div class="cartao">

                <h4>
                    Fretes de <?= escapar(date("d/m/Y", strtotime($data))) ?>
                </h4>

                <?php if (count($fretes) > 0): ?>

                    <div class="table-responsive">

                        <table class="table table-striped table-bordered">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Transportador</th>
                                    <th>Origem</th>
                                    <th>Destino</th>
                                    <th>Produtos</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($fretes as $frete): ?>

                                    <tr>
                                        <td><?= escapar($frete["id"]) ?></td>
                                        <td><?= escapar($frete["transportador"]) ?></td>
                                        <td><?= escapar($frete["cidade_origem"]) ?></td>
                                        <td><?= escapar($frete["cidade_destino"]) ?></td>
                                        <td><?= escapar($frete["produtos"]) ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <p class="mb-0">
                        Nenhum frete encontrado para essa data.
                    </p>

                <?php endif; ?>

            </div>

        </main>

    </div>
</div>

</body>
</html>
