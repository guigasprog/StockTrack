-- CRIAR DB

DROP DATABASE IF EXISTS stocktrack_db;

CREATE DATABASE stocktrack_db;

-- USAR DB

USE stocktrack_db;

-- CRIAR TABELAS 

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

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    cpf VARCHAR(14) NOT NULL UNIQUE,
    endereco_id INT,
    FOREIGN KEY (endereco_id) REFERENCES enderecos(idEndereco) ON DELETE CASCADE
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    quantidade INT DEFAULT 0,
    validade DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'concluído', 'cancelado') DEFAULT 'pendente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE TABLE itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    produto_id INT,
    quantidade INT NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

CREATE TABLE estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    quantidade INT NOT NULL,
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- INSERINDO DADOS

INSERT INTO enderecos (logradouro, numero, complemento, bairro, cidade, estado, cep) VALUES
('Rua A', '123', 'Apto 101', 'Centro', 'São Paulo', 'SP', '01234-567'),
('Avenida B', '456', NULL, 'Jardim', 'Rio de Janeiro', 'RJ', '02345-678'),
('Travessa C', '789', 'Casa', 'Bairro Velho', 'Belo Horizonte', 'MG', '03456-789');

INSERT INTO categorias (nome) VALUES
('Categoria A'),
('Categoria B'),
('Categoria C');

INSERT INTO clientes (nome, email, telefone, cpf, endereco_id) VALUES
('Maria Silva', 'maria.silva@example.com', '9999-9999', '12345678901', 1),
('João Pereira', 'joao.pereira@example.com', '9888-8888', '10987654321', 2),
('Ana Costa', 'ana.costa@example.com', '9777-7777', '10203040506', 3);

INSERT INTO produtos (nome, descricao, preco, quantidade, validade, categoria_id) VALUES
('Produto 1', 'Descrição do Produto 1', 29.90, 50, '2025-12-31', 1),
('Produto 2', 'Descrição do Produto 2', 19.99, 30, '2023-11-30', 2),
('Produto 3', 'Descrição do Produto 3', 39.50, 20, '2024-10-01', 3);

INSERT INTO pedidos (cliente_id, total, status) VALUES
(1, 59.80, 'concluído'),
(2, 19.99, 'pendente'),
(1, 39.50, 'cancelado');

INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco) VALUES
(1, 1, 2, 29.90),
(1, 2, 1, 19.99),
(2, 2, 1, 19.99),
(3, 3, 1, 39.50);

INSERT INTO estoque (produto_id, quantidade) VALUES
(1, 50),
(2, 30),
(3, 20);
