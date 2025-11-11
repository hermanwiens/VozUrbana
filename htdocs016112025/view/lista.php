<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Problemas Cadastrados</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .btn { display:inline-block; padding:6px 10px; border-radius:4px; text-decoration:none; background:#4CAF50; color:#fff; margin-right:6px; }
        .btn-delete { background:#e74c3c; border:none; cursor:pointer; padding:6px 10px; color:#fff; border-radius:4px; }
        .btn-link { background:#3498db; color:#fff; padding:6px 10px; border-radius:4px; text-decoration:none; }
    </style>
</head>
<body>
    <header>
        <h1>Problemas Urbanos Registrados</h1>
        <p>Veja os registros enviados pela comunidade</p>
    </header>

    <main>
        <?php
        // Exibe mensagem de sucesso (flash) se existir
        $success = $success ?? null;
        if ($success): ?>
            <div style="max-width:900px;margin:0 auto;background:#e6ffea;border:1px solid #b3ffcf;padding:12px;border-radius:6px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <ul class="lista-pontos">
            <?php foreach ($pontos as $p): ?>
                <li>
                    <h3><?= htmlspecialchars($p->tipo) ?></h3>
                    <p><?= nl2br(htmlspecialchars($p->descricao)) ?></p>
                    <p><strong>Localização:</strong> <?= htmlspecialchars($p->latitude) ?>, <?= htmlspecialchars($p->longitude) ?></p>
                    <?php if (!empty($p->foto)): ?>
                        <p><img src="<?= htmlspecialchars($p->foto) ?>" alt="Foto do ponto" style="max-width:100%;border-radius:8px;"></p>
                    <?php endif; ?>
                    <p><em>Enviado em: <?= htmlspecialchars(date("d/m/Y H:i", strtotime($p->data_envio))) ?></em></p>

                    <a href="index.php?action=mapa&id=<?= urlencode($p->id) ?>" class="btn-link">Ver no mapa</a>

                    <!-- Botão de editar -->
                    <a href="index.php?action=editar&id=<?= urlencode($p->id) ?>" class="btn">Editar</a>

                    <!-- Form de deletar (POST) -->
                    <form method="post" action="index.php?action=deletar" style="display:inline" onsubmit="return confirm('Confirma exclusão deste registro?');">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($p->id) ?>">
                        <button type="submit" class="btn-delete">Excluir</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="menu">
            <a href="index.php" class="btn">Voltar à Home</a>
        </div>
    </main>

    <footer>
        <p>&copy; <?= date("Y") ?> PROBLEMAS URBANOS</p>
    </footer>
</body>
</html>