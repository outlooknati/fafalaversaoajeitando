-- =====================================================
-- FAFALA - DADOS FIXOS DO QUESTIONÁRIO
-- Perguntas do primeiro acesso e perfis visuais.
-- Rodar uma vez, depois de criar as tabelas.
-- Os id_pergunta (1 a 6) precisam bater com resposta[1] ... resposta[6] do formulário do questionario.php.
-- =====================================================

USE FAFALA;

-- -----------------------------------------------------
-- PERGUNTAS
-- 'S' = pergunta de Sim / Não / Não sei
-- 'Visual' = pergunta de escolha do tema visual
-- -----------------------------------------------------

INSERT INTO pergunta (id_pergunta, texto_pergunta, categoria, tipo_resposta, ordem_exibicao_pergunta) VALUES
(1, 'O usuário tem dificuldade para enxergar elementos pequenos?', 'Acessibilidade', 'S', 1),
(2, 'O usuário tem dificuldade para tocar com precisão em elementos pequenos?', 'Acessibilidade', 'S', 2),
(3, 'O usuário costuma tocar acidentalmente em elementos próximos?', 'Acessibilidade', 'S', 3),
(4, 'O usuário se comunica melhor quando há poucos elementos na tela?', 'Acessibilidade', 'S', 4),
(5, 'O usuário sabe ler ou está em processo de aprendizagem da leitura?', 'Acessibilidade', 'S', 5),
(6, 'Qual tema visual é mais adequado para o usuário?', 'Visual', 'Visual', 6);

-- -----------------------------------------------------
-- PERFIS VISUAIS
-- Infantil: fundo do site (#FBFAF7); o degradê verde e amarelo
--           é desenhado por cima pelo CSS.
-- Adulto: cinza bem claro, liso.
-- Personalizado: valores padrão do tema "Azul". Quando a pessoa
--           escolhe outra cor no questionário, o PHP troca pelas
--           cores do tema escolhido.
-- -----------------------------------------------------

INSERT INTO perfil_visual (nome_perfil_visual, cor_fundo_tema_padrao, cor_card_tema_padrao, modo_escuro_padrao, descricao_visual) VALUES
('Infantil', '#FBFAF7', '#FFFFFF', 0, 'Cores alegres e suaves, com o visual padrão do aplicativo. Pensado para crianças.'),
('Adulto', '#F5F7FA', '#FFFFFF', 0, 'Visual neutro e sóbrio, pensado para adolescentes e adultos.'),
('Personalizado', '#DDEBFA', '#9CC2EE', 0, 'Escolha as cores preferidas e ajuste o visual de acordo com o gosto do paciente.');