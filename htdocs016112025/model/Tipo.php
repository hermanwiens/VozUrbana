<?php
// filepath: htdocs06112025/model/Tipo.php
class Tipo {
    public $id;
    public $nome;
    public $descricao;

    public function __construct($id, $nome, $descricao = null) {
        $this->id = $id;
        $this->nome = $nome;
        $this->descricao = $descricao;
    }
}
?>
```