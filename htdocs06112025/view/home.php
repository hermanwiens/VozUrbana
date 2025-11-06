<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal de Problemas Urbanos</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <header>
        <h1>Bem-vindo ao Portal Problemas Urbanos</h1>
        <p>Descreva os problemas em seu bairro</p>
    </header>

    <main>
        <div class="menu">
            <a href="index.php?action=form" class="btn">Cadastrar Problema</a>
            <a href="index.php?action=listar" class="btn">Ver Todos os Problemas Relatados</a>
        </div>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> PROBLEMAS URBANOS</p>
    </footer>
</body>
</html>