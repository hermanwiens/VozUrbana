<?php 
// Carrega as classes de modelo necessárias
require_once __DIR__ . '/../model/PontoDAO.php';
require_once __DIR__ . '/../model/TipoDAO.php';

// Garante sessão para flash messages
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * PontoController
 *
 * Controlador responsável pelas ações relacionadas aos "pontos" (problemas urbanos).
 */
class PontoController {
    private $pontoDAO;
    private $tipoDAO;

    public function __construct($pontoDAO, $tipoDAO) {
        $this->pontoDAO = $pontoDAO;
        $this->tipoDAO = $tipoDAO;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * home()
     */
    public function home() {
        include "view/home.php";
    }

    /**
     * form()
     * Carrega tipos do banco e flash messages antes de incluir a view.
     */
    public function form() {
        $tipos = $this->tipoDAO->todos();

        // Recupera mensagens de sessão (flash) e limpa
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        include "view/form.php";
    }

    /**
     * salvar()
     * - valida campos
     * - processa upload com checagem de MIME e tamanho
     * - resolve tipo via select (ou texto livre em 'Outro')
     * - insere no banco e fornece flash messages
     */
    public function salvar() {
        $errors = [];
        $old = [];

        // Inputs básicos e trimming
        $tipo_select = $_POST['tipo_select'] ?? '';
        $tipo_texto = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $latitudeRaw = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
        $longitudeRaw = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

        // Guarda para repopular o formulário em caso de erro
        $old = [
            'tipo_select' => $tipo_select,
            'tipo' => $tipo_texto,
            'descricao' => $descricao,
            'latitude' => $latitudeRaw,
            'longitude' => $longitudeRaw
        ];

        // Validação do tipo (usaremos $tipoNome para gravar)
        $tipoNome = '';

        if ($tipo_select !== '') {
            if ($tipo_select === 'other') {
                // O usuário informou um tipo livre
                if ($tipo_texto === '') {
                    $errors[] = "Informe o tipo do problema.";
                } elseif (mb_strlen($tipo_texto) > 100) {
                    $errors[] = "O campo 'Tipo' deve ter no máximo 100 caracteres.";
                } else {
                    $tipoNome = $tipo_texto;
                }
            } else {
                // Tipo selecionado por id -> buscar nome no banco
                $tipoObj = $this->tipoDAO->buscarPorId($tipo_select);
                if ($tipoObj) {
                    $tipoNome = $tipoObj->nome;
                } else {
                    $errors[] = "Tipo selecionado inválido.";
                }
            }
        } else {
            $errors[] = "Selecione um tipo de problema.";
        }

        // Validações adicionais
        if ($descricao === '') {
            $errors[] = "O campo 'Descrição' é obrigatório.";
        }

        $latitude = filter_var($latitudeRaw, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($longitudeRaw, FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $latitude < -90 || $latitude > 90) {
            $errors[] = "Latitude inválida. Use um número entre -90 e 90.";
        }

        if ($longitude === false || $longitude < -180 || $longitude > 180) {
            $errors[] = "Longitude inválida. Use um número entre -180 e 180.";
        }

        // Processamento do upload (opcional)
        $fotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Erro no upload do arquivo (código: " . $_FILES['foto']['error'] . ").";
            } else {
                $maxBytes = 2 * 1024 * 1024; // 2 MB
                if ($_FILES['foto']['size'] > $maxBytes) {
                    $errors[] = "A foto deve ter no máximo 2 MB.";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
                    finfo_close($finfo);

                    $mimeToExt = [
                        'image/jpeg' => 'jpg',
                        'image/pjpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!isset($mimeToExt[$mime])) {
                        $errors[] = "Tipo de arquivo não permitido. Envie JPG, PNG ou WEBP.";
                    } else {
                        $ext = $mimeToExt[$mime];
                        $nomeArquivo = uniqid('', true) . "." . $ext;
                        $diretorioUploads = "public/img/uploads/";

                        if (!is_dir($diretorioUploads)) {
                            mkdir($diretorioUploads, 0755, true);
                        }

                        $caminho = $diretorioUploads . $nomeArquivo;

                        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
                            $errors[] = "Falha ao salvar a foto enviada.";
                        } else {
                            $fotoPath = $caminho;
                        }
                    }
                }
            }
        }

        // Se houver erros, envia flash e volta ao formulário
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $old;
            header("Location: index.php?action=form");
            exit;
        }

        // Monta objeto Ponto e persiste
        $ponto = new Ponto(
            null,
            $tipoNome,
            $descricao,
            $latitude,
            $longitude,
            $fotoPath,
            date("Y-m-d H:i:s")
        );

        try {
            $this->pontoDAO->inserir($ponto);
            $_SESSION['success'] = "Ponto salvo com sucesso.";
            header("Location: index.php?action=listar");
            exit;
        } catch (Exception $e) {
            error_log("Erro ao inserir ponto: " . $e->getMessage());
            $_SESSION['errors'] = ["Ocorreu um erro ao salvar o ponto. Tente novamente mais tarde."];
            $_SESSION['old'] = $old;
            header("Location: index.php?action=form");
            exit;
        }
    }

    /**
     * listar()
     * Recupera pontos e possível mensagem de sucesso (flash) antes de incluir a view.
     */
    public function listar() {
        $pontos = $this->pontoDAO->todos();
        // Recupera mensagem de sucesso (flash)
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
        include "view/lista.php";
    }

    /**
     * mapa($id)
     */
    public function mapa($id) {
        $ponto = $this->pontoDAO->buscarPorId($id);
        include "view/mapa.php";
    }

    // Carrega registro e exibe form populado para edição
    public function editar() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['errors'] = ['ID inválido para edição.'];
            header('Location: index.php?action=listar');
            exit;
        }

        $ponto = $this->pontoDAO->buscarPorId($id);
        if (!$ponto) {
            $_SESSION['errors'] = ['Registro não encontrado.'];
            header('Location: index.php?action=listar');
            exit;
        }

        // Preenche array $old esperado pelo form
        $old = [
            'id' => $ponto->id,
            'tipo' => $ponto->tipo ?? ($ponto->tipo_nome ?? ''),
            'tipo_select' => $ponto->tipo_id ?? null,
            'descricao' => $ponto->descricao ?? '',
            'latitude' => $ponto->latitude ?? '',
            'longitude' => $ponto->longitude ?? ''
        ];

        $tipos = $this->tipoDAO->todos();
        include __DIR__ . '/../view/form.php';
    }

    // Deleta registro recebido via POST e redireciona para lista
    public function deletar() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            $_SESSION['errors'] = ['ID inválido para exclusão.'];
            header('Location: index.php?action=listar');
            exit;
        }

        $ok = $this->pontoDAO->deletar($id);
        if ($ok) {
            $_SESSION['success'] = 'Registro excluído com sucesso.';
        } else {
            $_SESSION['errors'] = ['Falha ao excluir registro.'];
        }

        header('Location: index.php?action=listar');
        exit;
    }
}
?>