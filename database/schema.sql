CREATE DATABASE IF NOT EXISTS immobilier_saas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE immobilier_saas;

CREATE TABLE IF NOT EXISTS cities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    postal_code VARCHAR(10) NOT NULL,
    department VARCHAR(5) NOT NULL DEFAULT '77',
    price_per_sqm_low DECIMAL(8,2) NOT NULL,
    price_per_sqm_mid DECIMAL(8,2) NOT NULL,
    price_per_sqm_high DECIMAL(8,2) NOT NULL,
    factor DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    description TEXT NULL,
    population INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_postal_code (postal_code),
    INDEX idx_department (department),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cities (name, slug, postal_code, department, price_per_sqm_low, price_per_sqm_mid, price_per_sqm_high, factor, description, population) VALUES
('Nandy', 'nandy', '77176', '77', 2565.00, 2850.00, 3135.00, 1.00, 'Commune résidentielle paisible au sud de la Seine-et-Marne, idéale pour les familles. Proximité Sénart et forêt de Rougeau.', 7200),
('Savigny-le-Temple', 'savigny-le-temple', '77176', '77', 2430.00, 2710.00, 2980.00, 0.95, 'Ville dynamique de la ville nouvelle de Sénart, bien desservie par le RER D. Nombreux équipements et commerces.', 30500),
('Cesson', 'cesson', '77240', '77', 2690.00, 2990.00, 3290.00, 1.05, 'Petite commune résidentielle prisée, entre Seine et forêt. Cadre verdoyant et calme à proximité de Melun.', 10200),
('Melun', 'melun', '77000', '77', 2820.00, 3135.00, 3450.00, 1.10, 'Préfecture de Seine-et-Marne, pôle économique et administratif. Marché immobilier actif avec forte demande locative.', 41500),
('Moissy-Cramayel', 'moissy-cramayel', '77550', '77', 2380.00, 2650.00, 2920.00, 0.93, 'Commune de Sénart en pleine expansion avec programmes neufs. Proche du centre commercial Carré Sénart.', 19500),
('Réau', 'reau', '77550', '77', 2480.00, 2765.00, 3040.00, 0.97, 'Commune rurale en mutation, proximité aérodrome et zones d''activités. Cadre semi-rural attractif.', 2100),
('Vert-Saint-Denis', 'vert-saint-denis', '77240', '77', 2610.00, 2907.00, 3200.00, 1.02, 'Commune résidentielle entre Melun et Sénart. Environnement calme et verdoyant, écoles réputées.', 7500),
('Combs-la-Ville', 'combs-la-ville', '77380', '77', 2690.00, 2990.00, 3290.00, 1.05, 'Ville attractive avec gare RER D, cadre de vie agréable. Marché immobilier en hausse constante.', 22500),
('Lieusaint', 'lieusaint', '77127', '77', 2640.00, 2935.00, 3230.00, 1.03, 'Cœur de la ville nouvelle de Sénart, proximité Carré Sénart. Ville jeune et dynamique.', 14000),
('Le Mée-sur-Seine', 'le-mee-sur-seine', '77350', '77', 2355.00, 2622.00, 2880.00, 0.92, 'Commune voisine de Melun, prix attractifs. Bord de Seine, accès rapide aux transports.', 21000),
('Dammarie-les-Lys', 'dammarie-les-lys', '77190', '77', 2400.00, 2679.00, 2950.00, 0.94, 'Proche de Melun et de la forêt de Fontainebleau. Quartiers variés, prix accessibles.', 22500),
('Limoges-Fourches', 'limoges-fourches', '77550', '77', 2300.00, 2565.00, 2820.00, 0.90, 'Petit village rural au charme préservé. Idéal pour les amateurs de tranquillité et nature.', 800),
('Seine-Port', 'seine-port', '77240', '77', 2950.00, 3278.00, 3600.00, 1.15, 'Village de caractère en bord de Seine. Cadre exceptionnel, propriétés de standing.', 1900),
('Saint-Fargeau-Ponthierry', 'saint-fargeau-ponthierry', '77310', '77', 2565.00, 2850.00, 3135.00, 1.00, 'Commune attractive entre Seine et forêt. Gare RER D, bon compromis prix/cadre de vie.', 13500),
('Fontainebleau', 'fontainebleau', '77300', '77', 3200.00, 3563.00, 3920.00, 1.25, 'Ville impériale, marché premium. Château, forêt, écoles internationales. Très recherchée.', 15000)
ON DUPLICATE KEY UPDATE name = VALUES(name);

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(255) NOT NULL,
    meta_description TEXT NOT NULL,
    persona VARCHAR(100) NOT NULL,
    awareness_level VARCHAR(50) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_articles_website_slug (website_id, slug),
    INDEX idx_website_id (website_id),
    INDEX idx_status_created_at (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_revisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id INT UNSIGNED NOT NULL,
    revision_number INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(255) NOT NULL,
    meta_description TEXT NOT NULL,
    persona VARCHAR(100) NOT NULL,
    awareness_level VARCHAR(50) NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_article_revision (article_id, revision_number),
    INDEX idx_article_created_at (article_id, created_at),
    CONSTRAINT fk_article_revisions_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id INT UNSIGNED NOT NULL,
    nom VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    telephone VARCHAR(40) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    ville VARCHAR(120) NOT NULL,
    estimation DECIMAL(12,2) NOT NULL,
    urgence VARCHAR(40) NOT NULL,
    motivation VARCHAR(80) NOT NULL,
    notes TEXT NULL,
    score ENUM('chaud', 'tiede', 'froid') NOT NULL DEFAULT 'froid',
    statut ENUM('nouveau', 'contacté', 'signé') NOT NULL DEFAULT 'nouveau',
    created_at DATETIME NOT NULL,
    INDEX idx_website_id (website_id),
    INDEX idx_email (email),
    INDEX idx_statut (statut),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    confirmed_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_newsletter_confirmed_at (confirmed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
