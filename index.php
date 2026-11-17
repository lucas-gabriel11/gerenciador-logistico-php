
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerenciador Logístico</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f1f4f9;
        }

        .menu {
            background-color: #17365d;
            min-height: 100vh;
            color: white;
        }

        .menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px;
            border-radius: 5px;
        }

        .menu a:hover {
            background-color: #28558a;
        }

        .conteudo {
            padding: 30px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">

            <!-- MENU LATERAL -->
            <div class="col-md-3 col-lg-2 menu p-3">

                <h4 class="mb-4">Logística</h4>

                <a href="index.php">Início</a>
                <a href="produtos.php">Produtos</a>
                <a href="enderecos.php">Endereços</a>
                <a href="transportadores.php">Transportadores</a>
                <a href="fretes.php">Fretes</a>
                <a href="relatorios.php">Relatórios</a>

            </div>

            <!-- CONTEÚDO PRINCIPAL -->
            <div class="col-md-9 col-lg-10 conteudo">

                <h2>Gerenciador Logístico</h2>

                <p>Bem-vindo ao sistema de gerenciamento logístico.</p>

                <div class="card mt-4 shadow-sm">
                    <div class="card-body">

                        <h5 class="card-title">
                            Sistema de Gestão Logística
                        </h5>

                        <p class="card-text">
                            Utilize o menu lateral para acessar
                            as funcionalidades do sistema.
                        </p>

                    </div>
                </div>

            </div>

        </div>
    </div>

</body>
</html>
