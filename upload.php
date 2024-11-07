<?php
// Estabelecendo conexão com o banco de dados
$conexao = mysqli_connect("localhost", "root", "", "record_db"); // Certifique-se de usar as credenciais corretas

// Verificando a conexão
if (!$conexao) {
    die(json_encode(["success" => false, "message" => "Erro de Conexão com Banco de Dados: " . mysqli_connect_error()]));
}

// Recebendo o arquivo de áudio
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['audio'])) {
    $file_path = 'uploads/' . basename($_FILES['audio']['name']); // Define o caminho do upload

    // Move o arquivo para o diretório desejado
    if (move_uploaded_file($_FILES['audio']['tmp_name'], $file_path)) {
        // Inserindo o caminho do arquivo na tabela
        $sql = "INSERT INTO records (file_path) VALUES ('$file_path')";
        
        if (mysqli_query($conexao, $sql)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar registro: " . mysqli_error($conexao)]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Erro ao mover o arquivo."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Nenhum arquivo enviado."]);
}

// Fechando a conexão
mysqli_close($conexao);
?>

<?php
//  REATE DATABASE record_db;
  // USE record_db;

 // CREATE TABLE records (
  //id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
 //  file_path VARCHAR(255) NOT NULL,
 // created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   PRIMARY KEY (id)
//   );
?>