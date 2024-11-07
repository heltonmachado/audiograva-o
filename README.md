
Gravador de Áudio

Este é um aplicativo web que permite gravar, reproduzir, baixar e fazer upload de áudios usando diferentes dispositivos de entrada. Ele oferece funcionalidades como equalizador de áudio e controle de volume por canal.
Tecnologias Utilizadas

    HTML5 e CSS3: Estrutura e estilização da interface.
    Bootstrap 4.5: Framework CSS para layout responsivo.
    Font Awesome: Ícones utilizados na interface.
    JavaScript: Manipulação da interface e interações do usuário.
    PHP: Gerenciamento de upload de arquivos no servidor.
    Web Audio API: Manipulação de áudio e implementação de filtros (equalizador).
    LocalStorage e Cookies: Persistência de configurações de dispositivo e tema.

Funcionalidades

    Modo Claro/Escuro: Alterna o tema visual do site.
    Seleção de Dispositivos de Entrada: Permite escolher até 8 canais de áudio.
    Gravação e Reprodução de Áudio: Grava áudio usando microfones disponíveis e permite a reprodução instantânea.
    Controle de Volume: Ajuste do volume para cada canal.
    Equalizador: Controle de graves, médios e agudos.
    Download e Upload: Baixe as gravações em formato MP3 ou envie para o servidor.
    Salvar Configurações: As seleções de dispositivos são salvas no navegador para uso posterior.
    Aviso de Saída: Confirmação ao tentar atualizar ou sair da página para evitar perda de gravações.


Requisitos

    Navegador moderno com suporte à Web Audio API.
    PHP instalado para a funcionalidade de upload de gravações.

Personalização

    Para modificar o tema padrão, altere o valor inicial do cookie theme no script JavaScript.
    Ajuste o design e os estilos no arquivo /src/css/styles.css.


Licença

Este projeto é de código aberto e está licenciado sob a MIT License.
Desenvolvedor

    Helton Machado – Desenvolvido com ❤

criação do banco mysql

CREATE DATABASE record_db;

USE record_db;

CREATE TABLE records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

 apos a criação do banco de dados no mysql 
 execulte o xamp  ou wamp e acesse o phpmyadmin
 e execulte na barra de navegador o endereço 
http://localhost/audio/index.php
e acesse a pagina do sistema.


