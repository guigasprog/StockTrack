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
    status ENUM('pendente', 'concluído', 'cancelado') DEFAULT 'pendente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- TABELA DE ITENS DO PEDIDO (MANY-TO-MANY ENTRE PEDIDOS E PRODUTOS)
CREATE TABLE pedido_produto (
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    PRIMARY KEY (pedido_id, produto_id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- CRIA TABELA DE ESTOQUE USANDO produto_id COMO PRIMARY KEY
CREATE TABLE estoque (
    produto_id INT PRIMARY KEY,
    quantidade INT NOT NULL,
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- INSERINDO DADOS

-- INSERINDO DADOS NAS TABELAS

-- Endereços
INSERT INTO enderecos (logradouro, numero, complemento, bairro, cidade, estado, cep) VALUES
('Rua A', '123', 'Apto 101', 'Centro', 'São Paulo', 'SP', '01234-567'),
('Avenida B', '456', NULL, 'Jardim', 'Rio de Janeiro', 'RJ', '02345-678'),
('Travessa C', '789', 'Casa', 'Bairro Velho', 'Belo Horizonte', 'MG', '03456-789');

-- Categorias
INSERT INTO categorias (nome, descricao) VALUES
('Categoria A', 'Descrição da Categoria A'),
('Categoria B', 'Descrição da Categoria B'),
('Categoria C', 'Descrição da Categoria C');

-- Clientes
INSERT INTO clientes (nome, email, telefone, cpf, endereco_id) VALUES
('Maria Silva', 'maria.silva@example.com', '9999-9999', '12345678901', 1),
('João Pereira', 'joao.pereira@example.com', '9888-8888', '10987654321', 2),
('Ana Costa', 'ana.costa@example.com', '9777-7777', '10203040506', 3);

-- Produtos (removido o campo quantidade)
INSERT INTO produtos (nome, descricao, preco, validade, categoria_id) VALUES
('Produto 1', 'Descrição do Produto 1', 29.90, '2025-12-31', 1),
('Produto 2', 'Descrição do Produto 2', 19.99, '2023-11-30', 2),
('Produto 3', 'Descrição do Produto 3', 39.50, '2024-10-01', 3);

-- Pedidos
INSERT INTO pedidos (cliente_id, total, status) VALUES
(1, 59.80, 'concluído'),
(2, 19.99, 'pendente'),
(1, 39.50, 'cancelado');

-- Itens de Pedido
INSERT INTO pedido_produto (pedido_id, produto_id, quantidade) VALUES
(1, 1, 2),
(1, 2, 1),
(2, 2, 1),
(3, 3, 1);

-- Estoque (adicionando quantidade e preço por unidade)
INSERT INTO estoque (produto_id, quantidade) VALUES
(1, 50),
(2, 30),
(3, 20);

CREATE TABLE imagens_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    imagem BLOB NOT NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);
