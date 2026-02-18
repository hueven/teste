-- CRIAÇÃO DA TABELA DE USUÁRIOS
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERIR USUÁRIO PADRÃO (Senha: admin123)
-- Você deve alterar essa senha depois!
INSERT INTO `admin_users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


-- CRIAÇÃO DA TABELA DE PROJETOS (WORKS)
CREATE TABLE IF NOT EXISTS `works` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `year` varchar(10),
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DADOS DE EXEMPLO (OPCIONAL)
INSERT INTO `works` (`title`, `description`, `year`, `image_path`, `display_order`) VALUES
('Modernist Building', 'Exploring the geometry of unmatched architectural purity.', '2024', '../imagens/01.webp', 1),
('Interior Silence', 'A study on minimalism and the impact of light.', '2023', '../imagens/02.webp', 2),
('Concrete Waves', 'The fluidity of concrete structures challenging rigidity.', '2024', '../imagens/03.webp', 3),
('Vertical Gardens', 'Integrating nature into the verticality of metropolis.', '2023', '../imagens/04.webp', 4),
('Shadow Play', 'Capturing the ephemeral dance of shadows.', '2022', '../imagens/05.webp', 5),
('Light Corridors', 'A perspective on how light travels through empty spaces.', '2024', '../imagens/06.webp', 6);
