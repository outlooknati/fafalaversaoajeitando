CREATE DATABASE FAFALA;

USE FAFALA;

CREATE TABLE perfil_visual(
	id_visual INT AUTO_INCREMENT PRIMARY KEY,
	nome_perfil_visual ENUM('Infantil','Adulto','Personalizado') NOT NULL,
	cor_fundo_tema_padrao VARCHAR(7) NOT NULL,
	cor_card_tema_padrao VARCHAR(7) NOT NULL,
	modo_escuro_padrao BOOLEAN NOT NULL,
	descricao_visual VARCHAR(200) NOT NULL
);

CREATE TABLE usuario(
	id_usuario INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	nome_usuario VARCHAR(100) NOT NULL,
	senha_hash VARCHAR(255) NOT NULL,
	data_cadastro DATE NOT NULL,
	telefone VARCHAR(20) NOT NULL
);

CREATE TABLE paciente(
	id_paciente INT AUTO_INCREMENT PRIMARY KEY,
	nome VARCHAR(100) NOT NULL,
	data_nascimento DATE NOT NULL,
	data_cadastro DATE NOT NULL,
	cor_card_tema VARCHAR(7) NOT NULL,
	cor_fundo_tema VARCHAR(7) NOT NULL,
	tamanho_card ENUM('Pequeno','Médio','Grande') NOT NULL,
	quantidade_cards INT NOT NULL,
	espacamento_cards ENUM('Normal','Grande') NOT NULL,
	modo_escuro BOOLEAN NOT NULL,
	contraste ENUM('Alto','Normal') NOT NULL,
	mostrar_texto BOOLEAN NOT NULL,
	tempo_debounce INT NOT NULL,
	id_visual INT,
	FOREIGN KEY(id_visual) REFERENCES perfil_visual(id_visual),
	id_usuario INT,
	FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE categoria(
	id_categoria INT AUTO_INCREMENT PRIMARY KEY,
	nome_categoria VARCHAR(50) NOT NULL,
	descricao_categoria VARCHAR(100),
	cor_identificacao VARCHAR(7) NOT NULL
);

CREATE TABLE imagem(
	id_imagem INT AUTO_INCREMENT PRIMARY KEY,
	nome_pictograma VARCHAR(50) NOT NULL,
	fonte_referencia ENUM('ARASAAC', 'Personalizado') NOT NULL DEFAULT 'ARASAAC',
	arquivo_imagem BLOB NOT NULL,
	texto_fala VARCHAR(100) NOT NULL,
	id_usuario INT,
	FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE imagem_categoria(
	id_imagem_categoria INT AUTO_INCREMENT PRIMARY KEY,
	id_categoria INT,
	FOREIGN KEY(id_categoria) REFERENCES categoria(id_categoria),
	id_imagem INT,
	FOREIGN KEY(id_imagem) REFERENCES imagem(id_imagem)
);

CREATE TABLE prancha(
	id_prancha INT AUTO_INCREMENT PRIMARY KEY,
	descricao_prancha VARCHAR(100),
	data_criacao DATE NOT NULL,
	tipo_prancha ENUM('Padrão','Personalizada') NOT NULL,
	nome_prancha VARCHAR(50) NOT NULL,
	contexto_uso VARCHAR(50) NOT NULL,
	id_paciente INT,
	FOREIGN KEY(id_paciente) REFERENCES paciente(id_paciente)
);

CREATE TABLE prancha_imagem(
	id_prancha_imagem INT AUTO_INCREMENT PRIMARY KEY,
	ordem_exibicao INT NOT NULL,
	id_prancha INT NOT NULL,
	FOREIGN KEY(id_prancha) REFERENCES prancha(id_prancha),
	id_imagem INT NOT NULL,
	FOREIGN KEY(id_imagem) REFERENCES imagem(id_imagem)
);

CREATE TABLE pergunta(
	id_pergunta INT AUTO_INCREMENT PRIMARY KEY,
	texto_pergunta VARCHAR(200) NOT NULL,
	categoria ENUM('Acessibilidade','Visual') NOT NULL,
	tipo_resposta ENUM('S','N','Visual') NOT NULL,
	ordem_exibicao_pergunta INT NOT NULL
);

CREATE TABLE resposta(
	id_resposta INT AUTO_INCREMENT PRIMARY KEY,
	resposta ENUM(
		'Sim',
		'Não',
		'Não sei',
		'Infantil',
		'Adulto',
		'Personalizado'
	) NOT NULL,
	data_resposta DATETIME NOT NULL,
	id_pergunta INT NOT NULL,
	FOREIGN KEY(id_pergunta) REFERENCES pergunta(id_pergunta),
	id_paciente INT NOT NULL,
	FOREIGN KEY(id_paciente) REFERENCES paciente(id_paciente)
);

CREATE TABLE registro_de_uso(
	id_registro INT AUTO_INCREMENT PRIMARY KEY,
	data_horario_uso DATETIME NOT NULL,
	id_prancha INT NOT NULL,
	FOREIGN KEY(id_prancha) REFERENCES prancha(id_prancha),
	id_paciente INT NOT NULL,
	FOREIGN KEY(id_paciente) REFERENCES paciente(id_paciente),
	id_imagem INT NOT NULL,
	FOREIGN KEY(id_imagem) REFERENCES imagem(id_imagem)
);