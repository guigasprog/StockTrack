-- DROP DB se já existir
DROP DATABASE IF EXISTS stocktrack_db;

-- CRIA DB
CREATE DATABASE stocktrack_db;

-- USA DB
USE stocktrack_db;

-- CRIA TABELA DE ENDEREÇOS
CREATE TABLE enderecos (
    idEndereco INT AUTO_INCREMENT PRIMARY KEY,
    logradouro VARCHAR(100),
    numero VARCHAR(10),
    complemento VARCHAR(50),
    bairro VARCHAR(50),
    cidade VARCHAR(50),
    estado VARCHAR(2),
    cep VARCHAR(9)
);

-- CRIA TABELA DE CLIENTES
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    cpf VARCHAR(14) NOT NULL UNIQUE,
    endereco_id INT,
    FOREIGN KEY (endereco_id) REFERENCES enderecos(idEndereco) ON DELETE CASCADE
);

-- CRIA TABELA DE CATEGORIAS
CREATE TABLE categorias (
    idCategoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT
);

-- CRIA TABELA DE PRODUTOS
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    validade DATE,
    preco DECIMAL(10, 2) NOT NULL,
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(idCategoria) ON DELETE SET NULL
);

-- TABELA DE PEDIDOS
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'concluído', 'cancelado', 'pagamento efetuado', 'enviado para entrega') DEFAULT 'pendente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- TABELA DE ITENS DO PEDIDO (MANY-TO-MANY ENTRE PEDIDOS E PRODUTOS)
CREATE TABLE pedido_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- CRIA TABELA DE ESTOQUE USANDO produto_id COMO PRIMARY KEY
CREATE TABLE estoque (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    quantidade INT NOT NULL,
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE imagens_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    imagem LONGBLOB NOT NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);
