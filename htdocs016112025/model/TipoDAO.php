<?php
// filepath: htdocs06112025/model/TipoDAO.php
require_once "model/Database.php";
require_once "model/Tipo.php";

class TipoDAO {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    // Retorna todos os tipos
    public function todos() {
        $stmt = $this->pdo->query("SELECT id, nome, descricao FROM tipos_problema ORDER BY nome ASC");
        $tipos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tipos[] = new Tipo($row['id'], $row['nome'], $row['descricao']);
        }
        return $tipos;
    }

    // Busca por id
    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome, descricao FROM tipos_problema WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new Tipo($row['id'], $row['nome'], $row['descricao']);
        }
        return null;
    }
}
?>
```