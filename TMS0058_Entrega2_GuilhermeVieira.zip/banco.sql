CREATE DATABASE IF NOT EXISTS loja_guinfo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loja_guinfo;

CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    descricao VARCHAR(500) NOT NULL DEFAULT '',
    preco DECIMAL(10,2) NOT NULL,
    estoque INT UNSIGNED NOT NULL DEFAULT 0,
    imagem VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS administradores (
    id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    usuario_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_administradores_usuario (usuario_id),
    CONSTRAINT fk_administradores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedidos_usuario (usuario_id),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NULL,
    nome_produto VARCHAR(150) NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_pedido_itens_pedido (pedido_id),
    KEY idx_pedido_itens_produto (produto_id),
    CONSTRAINT fk_pedido_itens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO produtos (nome, categoria, descricao, preco, estoque, imagem)
SELECT iniciais.nome, iniciais.categoria, iniciais.descricao, iniciais.preco, iniciais.estoque, iniciais.imagem
FROM (
    SELECT 'Mouse Pad 40X30' AS nome, 'Acessorios' AS categoria, 'Mouse pad speed.' AS descricao, 79.90 AS preco, 25 AS estoque, 'imagem/mousepad.png' AS imagem
    UNION ALL SELECT 'Fone sem Fio Preto', 'Audio', 'Fone de ouvido preto sem fio.', 199.90, 12, 'imagem/fone.png'
    UNION ALL SELECT 'Headset Gamer Preto e Vermelho', 'Audio', 'Headset com microfone integrado.', 139.90, 18, 'imagem/fone2.png'
    UNION ALL SELECT 'Fone com fio Preto', 'Audio', 'Fone de ouvido com fio.', 139.90, 20, 'imagem/fone3.png'
    UNION ALL SELECT 'Fone com Fio Gamer', 'Audio', 'Fone de ouvido gamer com fio.', 139.90, 15, 'imagem/fone4.png'
    UNION ALL SELECT 'Microfone RGB com Suporte', 'Microfones', 'Microfone preto com iluminacao RGB.', 149.00, 10, 'imagem/microfone.png'
    UNION ALL SELECT 'Microfone Branco', 'Microfones', 'Microfone branco com suporte de mesa.', 149.00, 8, 'imagem/microfone2.png'
    UNION ALL SELECT 'Microfone Preto Compacto', 'Microfones', 'Microfone preto com suporte de mesa.', 149.00, 9, 'imagem/microfone3.png'
    UNION ALL SELECT 'Microfone Branco e Preto RGB', 'Microfones', 'Microfone de mesa com iluminacao RGB.', 149.00, 7, 'imagem/microfone4.png'
    UNION ALL SELECT 'Mouse Gamer Branco', 'Mouses', 'Mouse gamer branco com base iluminada.', 149.90, 14, 'imagem/mouse-gamer-branco.png'
    UNION ALL SELECT 'Mouse Gamer Preto', 'Mouses', 'Mouse gamer preto.', 169.90, 16, 'imagem/mouse-gamer-preto.png'
    UNION ALL SELECT 'Mouse Gamer Vermelho', 'Mouses', 'Mouse gamer vermelho com base iluminada.', 159.90, 11, 'imagem/mouse-gamer-vermelho.png'
) AS iniciais
WHERE NOT EXISTS (SELECT 1 FROM produtos);
