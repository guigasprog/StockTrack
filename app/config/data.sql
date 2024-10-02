-- CRIAR DB

CREATE DATABASE stocktrack_db;

-- USAR DB

USE stocktrack_db;

-- CRIAR TABELAS 

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    endereco VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE produtos 
ADD COLUMN categoria_id INT,
ADD CONSTRAINT fk_categoria_produto
FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

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

INSERT INTO categorias (nome) VALUES
('Categoria A'),
('Categoria B'),
('Categoria C');

INSERT INTO clientes (nome, email, telefone, endereco) VALUES
('Maria Silva', 'maria.silva@example.com', '9999-9999', 'Rua A, 123'),
('João Pereira', 'joao.pereira@example.com', '9888-8888', 'Avenida B, 456'),
('Ana Costa', 'ana.costa@example.com', '9777-7777', 'Travessa C, 789');

INSERT INTO produtos (nome, descricao, preco, quantidade, categoria_id) VALUES
('Produto 1', 'Descrição do Produto 1', 29.90, 50, 1),
('Produto 2', 'Descrição do Produto 2', 19.99, 30, 2),
('Produto 3', 'Descrição do Produto 3', 39.50, 20, 3);

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

