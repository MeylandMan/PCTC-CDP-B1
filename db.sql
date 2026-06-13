CREATE DATABASE IF NOT EXISTS monitoring_project;
USE monitoring_project;

CREATE TABLE IF NOT EXISTS roles
(
    id          INT          PRIMARY KEY AUTO_INCREMENT,
    role_name   VARCHAR(50)  NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS users
(
    id         INT          PRIMARY KEY AUTO_INCREMENT,
    firstname  VARCHAR(100) NOT NULL,
    lastname   VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role_id    INT          NOT NULL,
    phone      VARCHAR(30),
    avatar     VARCHAR(255),
    is_active  BOOLEAN      NOT NULL DEFAULT TRUE,
    last_login DATETIME     DEFAULT CURRENT_TIMESTAMP,
    last_ip    VARCHAR(50),
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,

    INDEX idx_users_role_id  (role_id),
    INDEX idx_users_email    (email),       -- recherches de connexion
    INDEX idx_users_is_active (is_active)   -- filtrer actifs/inactifs
);

CREATE TABLE IF NOT EXISTS permissions
(
    id              INT          PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description     TEXT
);

CREATE TABLE IF NOT EXISTS role_permissions
(
    id            INT NOT NULL AUTO_INCREMENT,
    role_id       INT NOT NULL,
    permission_id INT NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_role_permission (role_id, permission_id),  -- évite les doublons

    FOREIGN KEY (role_id)       REFERENCES roles(id)       ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,

    INDEX idx_rp_role_id       (role_id),
    INDEX idx_rp_permission_id (permission_id)
);

CREATE TABLE IF NOT EXISTS locations
(
    id        INT           PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(150)  NOT NULL,
    city      VARCHAR(100)  NOT NULL,
    country   VARCHAR(100),
    latitude  DECIMAL(10,8),
    longitude DECIMAL(11,8)
);

CREATE TABLE IF NOT EXISTS devices
(
    id          INT          PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(150) NOT NULL,
    hostname    VARCHAR(150) NOT NULL,
    ip_address  VARCHAR(50)  NOT NULL UNIQUE,
    mac_address VARCHAR(50)  NOT NULL UNIQUE,
    type        ENUM('server','router','switch','firewall','printer','iot','workstation','other')
                             NOT NULL DEFAULT 'other',
    location_id INT          NOT NULL,
    status      ENUM('online','offline','warning','maintenance')
                             NOT NULL DEFAULT 'offline',
    os_version  VARCHAR(100) NOT NULL,
    last_check  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,

    INDEX idx_devices_status      (status),       -- dashboard : filtrer en ligne/hors ligne
    INDEX idx_devices_location_id (location_id),  -- géolocalisation
    INDEX idx_devices_type        (type)          -- filtres par type
);

CREATE TABLE IF NOT EXISTS device_metrics
(
    id          BIGINT         PRIMARY KEY AUTO_INCREMENT,
    device_id   INT            NOT NULL,
    cpu_usage   DECIMAL(5,2),
    ram_usage   DECIMAL(5,2),
    disk_usage  DECIMAL(5,2),
    network_in  FLOAT,
    network_out FLOAT,
    temperature FLOAT,
    collected_at DATETIME      DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,

    INDEX idx_metrics_device_id    (device_id),
    INDEX idx_metrics_collected_at (collected_at),                     -- requêtes temporelles
    INDEX idx_metrics_device_time  (device_id, collected_at DESC)      -- courbes temps réel par appareil
);

CREATE TABLE IF NOT EXISTS alerts
(
    id              INT          PRIMARY KEY AUTO_INCREMENT,
    device_id       INT          NOT NULL,
    alert_level     ENUM('info','warning','critique','urgent'),
    alert_type      VARCHAR(100),
    message         TEXT,
    threshold_value FLOAT,
    current_value   FLOAT,
    status          ENUM('open','acknowledged','resolved') DEFAULT 'open',
    created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,

    INDEX idx_alerts_device_id  (device_id),
    INDEX idx_alerts_status     (status),
    INDEX idx_alerts_level      (alert_level),
    INDEX idx_alerts_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS notifications
(
    id      INT          PRIMARY KEY AUTO_INCREMENT,
    user_id INT          NOT NULL,
    title   VARCHAR(255) NOT NULL,
    content TEXT,
    channel ENUM('email','sms','push','dashboard') NOT NULL DEFAULT 'dashboard',
    is_read BOOLEAN      NOT NULL DEFAULT FALSE,
    sent_at DATETIME     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_notif_user_id (user_id),
    INDEX idx_notif_is_read (is_read),
    INDEX idx_notif_user_unread (user_id, is_read)  -- badge "non lus"
);

CREATE TABLE IF NOT EXISTS incidents
(
    id          INT          PRIMARY KEY AUTO_INCREMENT,
    device_id   INT          NOT NULL,
    assigned_to INT          NOT NULL,
    priority    ENUM('low','medium','high','critique') NOT NULL DEFAULT 'medium',
    status      ENUM('open','incoming','waiting','resolved','closed') NOT NULL DEFAULT 'open',
    title       VARCHAR(255),
    description TEXT,
    opened_at   DATETIME,
    resolved_at DATETIME,

    FOREIGN KEY (device_id)   REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id)   ON DELETE CASCADE,

    INDEX idx_incidents_device_id   (device_id),
    INDEX idx_incidents_assigned_to (assigned_to),
    INDEX idx_incidents_status      (status),
    INDEX idx_incidents_priority    (priority)
);

CREATE TABLE IF NOT EXISTS logs
(
    id         BIGINT       PRIMARY KEY AUTO_INCREMENT,
    user_id    INT          NOT NULL,
    action     VARCHAR(255),
    module     VARCHAR(100),
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_logs_user_id    (user_id),
    INDEX idx_logs_created_at (created_at),
    INDEX idx_logs_module     (module)
);

CREATE TABLE IF NOT EXISTS reports
(
    id           INT          PRIMARY KEY AUTO_INCREMENT,
    generated_by INT          NOT NULL,
    report_type  VARCHAR(100),
    file_path    VARCHAR(255),
    generated_at DATETIME     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_reports_generated_by (generated_by),
    INDEX idx_reports_generated_at (generated_at)
);

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================
 
-- ------------------------------------------------------------
-- Rôles
-- ------------------------------------------------------------
INSERT INTO roles (role_name, description) VALUES
('super_admin',  'Gestion globale de la plateforme, paramétrage système et visualisation complète'),
('admin',        'Gestion des utilisateurs, appareils, alertes et consultation des rapports'),
('technicien',   'Consultation des équipements, gestion des incidents et validation des alertes'),
('utilisateur',  'Consultation des équipements autorisés et des alertes associées'),
('auditeur',     'Consultation seule avec accès aux rapports et aux logs');
 
-- ------------------------------------------------------------
-- Permissions
-- ------------------------------------------------------------
INSERT INTO permissions (permission_name, description) VALUES
('view_dashboard',    'Accès au tableau de bord principal'),
('manage_users',      'Créer, modifier et désactiver des utilisateurs'),
('manage_devices',    'Ajouter, modifier et supprimer des équipements'),
('view_reports',      'Consulter et télécharger les rapports'),
('manage_alerts',     'Configurer les seuils et valider les alertes'),
('manage_incidents',  'Créer, affecter et clore des tickets d\'incidents'),
('view_logs',         'Consulter l\'historique complet des actions'),
('manage_roles',      'Créer et modifier les rôles et permissions'),
('export_data',       'Exporter les données en PDF, Excel ou CSV'),
('manage_settings',   'Modifier les paramètres globaux du système');
 
-- ------------------------------------------------------------
-- Associations rôle ↔ permission
-- ------------------------------------------------------------
INSERT INTO role_permissions (role_id, permission_id) VALUES
-- super_admin (id=1) : tout
(1, 1),(1, 2),(1, 3),(1, 4),(1, 5),(1, 6),(1, 7),(1, 8),(1, 9),(1, 10),
-- admin (id=2) : tout sauf manage_roles et manage_settings
(2, 1),(2, 2),(2, 3),(2, 4),(2, 5),(2, 6),(2, 7),(2, 9),
-- technicien (id=3)
(3, 1),(3, 3),(3, 5),(3, 6),
-- utilisateur (id=4)
(4, 1),(4, 3),
-- auditeur (id=5)
(5, 1),(5, 4),(5, 7),(5, 9);
 
-- ------------------------------------------------------------
-- Utilisateurs  (mots de passe = bcrypt de "Password123!")
-- ------------------------------------------------------------
INSERT INTO users (firstname, lastname, email, password, role_id, phone, is_active, last_ip) VALUES
('Alice',   'Dupont',   'alice.dupont@nexora.com',   '$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 1, '+241 77 11 22 33', TRUE,  '192.168.1.10'),
('Bob',     'Martin',   'bob.martin@nexora.com',     '$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 2, '+241 77 44 55 66', TRUE,  '192.168.1.15'),
('Claire',  'Ondo',     'claire.ondo@nexora.com',    '$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 3, '+241 77 88 99 00', TRUE,  '192.168.1.22'),
('David',   'Nzamba',   'david.nzamba@nexora.com',   '$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 3, '+241 77 12 34 56', TRUE,  '192.168.1.30'),
('Estelle', 'Bouanga',  'estelle.bouanga@nexora.com','$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 4, '+241 77 65 43 21', TRUE,  '192.168.1.40'),
('François','Mbadinga', 'f.mbadinga@nexora.com',     '$2y$12$Kp3QwXv9mLzRt7Hs1UeY8OjNbF4cGdA6oVqZ5iW2nE0pTlMuCsXrK', 5, '+241 77 99 88 77', FALSE, '10.0.0.5');
 
-- ------------------------------------------------------------
-- Localisations
-- ------------------------------------------------------------
INSERT INTO locations (site_name, city, country, latitude, longitude) VALUES
('Siège Social Nexora',      'Libreville',      'Gabon', 0.39241700,  9.45356200),
('Datacenter Port-Gentil',   'Port-Gentil',     'Gabon',-0.71938900,  8.78113800),
('Agence Franceville',       'Franceville',     'Gabon',-1.63332700, 13.58346600),
('Antenne Oyem',             'Oyem',            'Gabon', 1.59919200, 11.57919500),
('Bureau régional Mouila',   'Mouila',          'Gabon',-1.86441200, 11.05652700);
 
-- ------------------------------------------------------------
-- Appareils
-- ------------------------------------------------------------
INSERT INTO devices (name, hostname, ip_address, mac_address, type, location_id, status, os_version) VALUES
('Serveur Web Principal',   'srv-web-01',    '192.168.1.1',   'AA:BB:CC:DD:EE:01', 'server',      1, 'online',      'Ubuntu Server 22.04 LTS'),
('Serveur Base de Données', 'srv-db-01',     '192.168.1.2',   'AA:BB:CC:DD:EE:02', 'server',      1, 'online',      'Ubuntu Server 22.04 LTS'),
('Routeur Cœur de Réseau',  'rtr-core-01',  '192.168.1.254', 'AA:BB:CC:DD:EE:03', 'router',      1, 'online',      'Cisco IOS 16.9'),
('Switch Distribution',     'sw-dist-01',    '192.168.1.253', 'AA:BB:CC:DD:EE:04', 'switch',      1, 'online',      'Cisco IOS 15.2'),
('Pare-feu Périmétrique',   'fw-edge-01',    '10.0.0.1',      'AA:BB:CC:DD:EE:05', 'firewall',    1, 'online',      'pfSense 2.7'),
('Serveur Backup',          'srv-bak-01',    '192.168.2.1',   'AA:BB:CC:DD:EE:06', 'server',      2, 'warning',     'Debian 12'),
('NAS Stockage',            'nas-01',        '192.168.2.2',   'AA:BB:CC:DD:EE:07', 'server',      2, 'online',      'TrueNAS SCALE 23.10'),
('Imprimante Réseau RH',    'prn-rh-01',     '192.168.3.10',  'AA:BB:CC:DD:EE:08', 'printer',     3, 'offline',     'HP FutureSmart 5'),
('Capteur IoT Température', 'iot-temp-01',   '10.10.0.1',     'AA:BB:CC:DD:EE:09', 'iot',         4, 'online',      'FreeRTOS 10.5'),
('Poste Admin Alice',       'ws-alice-01',   '192.168.1.50',  'AA:BB:CC:DD:EE:10', 'workstation', 1, 'online',      'Windows 11 Pro 23H2'),
('Serveur Monitoring',      'srv-mon-01',    '192.168.1.3',   'AA:BB:CC:DD:EE:11', 'server',      1, 'maintenance', 'Ubuntu Server 22.04 LTS'),
('Switch Accès Agence',     'sw-acc-fv-01',  '192.168.5.1',   'AA:BB:CC:DD:EE:12', 'switch',      3, 'online',      'HP ProCurve 2610');
 
-- ------------------------------------------------------------
-- Métriques (collectes récentes)
-- ------------------------------------------------------------
INSERT INTO device_metrics (device_id, cpu_usage, ram_usage, disk_usage, network_in, network_out, temperature, collected_at) VALUES
-- srv-web-01 : 3 collectes
(1, 45.20, 62.50, 38.10, 1024.50, 512.30,  52.1, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(1, 48.70, 63.10, 38.15, 1100.20, 530.80,  52.4, DATE_SUB(NOW(), INTERVAL  5 MINUTE)),
(1, 52.30, 65.80, 38.20, 1250.60, 610.20,  53.0, NOW()),
-- srv-db-01 : 3 collectes
(2, 70.10, 80.30, 55.40, 2048.00, 1024.00, 58.3, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(2, 72.50, 81.00, 55.42, 2100.50, 1050.30, 58.7, DATE_SUB(NOW(), INTERVAL  5 MINUTE)),
(2, 75.80, 83.60, 55.45, 2300.80, 1150.60, 59.5, NOW()),
-- srv-bak-01 : état warning — disque critique
(6, 18.40, 42.10, 91.30, 300.00,  150.00,  47.2, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(6, 19.10, 42.50, 93.80, 310.20,  155.10,  47.5, DATE_SUB(NOW(), INTERVAL  5 MINUTE)),
(6, 20.00, 43.00, 96.10, 320.50,  160.00,  47.9, NOW()),
-- iot-temp-01
(9, 2.10,  18.50,  5.20, 12.30,    5.10,   38.6, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(9, 2.30,  18.60,  5.20, 13.10,    5.40,   39.1, DATE_SUB(NOW(), INTERVAL  5 MINUTE)),
(9, 2.50,  18.70,  5.20, 12.80,    5.30,   39.4, NOW());
 
-- ------------------------------------------------------------
-- Alertes
-- ------------------------------------------------------------
INSERT INTO alerts (device_id, alert_level, alert_type, message, threshold_value, current_value, status, created_at) VALUES
(6, 'critique', 'disk_usage',    'Utilisation disque critique sur srv-bak-01',             95.00, 96.10, 'open',         DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(2, 'warning',  'cpu_usage',     'CPU élevé sur srv-db-01 — surveiller la charge',         70.00, 75.80, 'acknowledged', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'warning',  'ram_usage',     'RAM dépassant le seuil sur srv-db-01',                   80.00, 83.60, 'open',         DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(8, 'info',     'connectivity',  'Imprimante prn-rh-01 hors ligne — vérifier l\'alimentation', NULL, NULL, 'open',       DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'warning',  'cpu_usage',     'Pic CPU détecté sur srv-web-01',                         50.00, 52.30, 'resolved',     DATE_SUB(NOW(), INTERVAL 1 DAY)),
(11,'info',     'maintenance',   'Serveur de monitoring en cours de mise à jour',           NULL,  NULL,  'acknowledged', DATE_SUB(NOW(), INTERVAL 6 HOUR));
 
-- ------------------------------------------------------------
-- Notifications
-- ------------------------------------------------------------
INSERT INTO notifications (user_id, title, content, channel, is_read, sent_at) VALUES
(2, 'Alerte critique : disque srv-bak-01',  'L\'utilisation disque du serveur de backup a atteint 96,1 %. Intervention requise.', 'email',     FALSE, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(3, 'Alerte critique : disque srv-bak-01',  'L\'utilisation disque du serveur de backup a atteint 96,1 %. Intervention requise.', 'dashboard', FALSE, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(4, 'Alerte CPU srv-db-01 reconnue',        'L\'alerte CPU sur le serveur de base de données a été prise en charge.',             'dashboard', TRUE,  DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'Imprimante RH hors ligne',             'La prn-rh-01 ne répond plus au ping depuis 10 minutes.',                            'email',     TRUE,  DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(3, 'Incident #3 assigné',                  'Un nouvel incident critique vous a été affecté : disque srv-bak-01.',                'dashboard', FALSE, DATE_SUB(NOW(), INTERVAL 4 MINUTE)),
(5, 'Rapport mensuel disponible',           'Le rapport de supervision du mois de mai est disponible au téléchargement.',         'email',     FALSE, DATE_SUB(NOW(), INTERVAL 1 DAY));
 
-- ------------------------------------------------------------
-- Incidents
-- ------------------------------------------------------------
INSERT INTO incidents (device_id, assigned_to, priority, status, title, description, opened_at, resolved_at) VALUES
(6,  3, 'critique', 'incoming', 'Disque critique srv-bak-01',
 'L\'espace disque du serveur de backup dépasse 96 %. Risque de panne imminente. Purger les vieilles sauvegardes ou étendre la capacité.',
 DATE_SUB(NOW(), INTERVAL 5 MINUTE), NULL),
 
(8,  4, 'low',      'open',     'Imprimante RH hors ligne',
 'La prn-rh-01 ne répond plus. Vérifier l\'alimentation et le câble réseau avant escalade.',
 DATE_SUB(NOW(), INTERVAL 3 HOUR),   NULL),
 
(2,  3, 'high',     'waiting',  'Charge RAM élevée sur srv-db-01',
 'La RAM dépasse 83 %. En attente de la fin du batch planifié ce soir pour réévaluer.',
 DATE_SUB(NOW(), INTERVAL 1 HOUR),   NULL),
 
(11, 2, 'medium',   'resolved', 'Mise à jour du serveur de monitoring',
 'Fenêtre de maintenance planifiée pour mise à jour de l\'OS et des sondes.',
 DATE_SUB(NOW(), INTERVAL 6 HOUR),   DATE_SUB(NOW(), INTERVAL 1 HOUR)),
 
(1,  3, 'medium',   'closed',   'Pic CPU srv-web-01',
 'Pic CPU observé hier soir à 52 %. Causé par un crawl SEO externe. Règle de rate limiting ajoutée.',
 DATE_SUB(NOW(), INTERVAL 1 DAY),    DATE_SUB(NOW(), INTERVAL 20 HOUR)),
 
(5,  2, 'high',     'open',     'Vérification règles pare-feu fw-edge-01',
 'Audit des règles de filtrage suite à une tentative de connexion suspecte détectée dans les logs.',
 DATE_SUB(NOW(), INTERVAL 2 HOUR),   NULL);
 
-- ------------------------------------------------------------
-- Logs d'audit
-- ------------------------------------------------------------
INSERT INTO logs (user_id, action, module, ip_address, user_agent, created_at) VALUES
(1, 'LOGIN_SUCCESS',       'auth',        '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(2, 'LOGIN_SUCCESS',       'auth',        '192.168.1.15', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', DATE_SUB(NOW(), INTERVAL 7 HOUR)),
(2, 'CREATE_USER',         'users',       '192.168.1.15', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(2, 'UPDATE_DEVICE',       'devices',     '192.168.1.15', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 'LOGIN_SUCCESS',       'auth',        '192.168.1.22', 'Mozilla/5.0 (X11; Linux x86_64) Firefox/125', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'ACKNOWLEDGE_ALERT',   'alerts',      '192.168.1.22', 'Mozilla/5.0 (X11; Linux x86_64) Firefox/125', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'UPDATE_INCIDENT',     'incidents',   '192.168.1.22', 'Mozilla/5.0 (X11; Linux x86_64) Firefox/125', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'GENERATE_REPORT',     'reports',     '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0) Chrome/124', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 'LOGIN_FAILED',        'auth',        '10.0.0.5',     'curl/8.1.2',                               DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'EXPORT_LOGS',         'audit',       '192.168.1.40', 'Mozilla/5.0 (Macintosh) Safari/17', DATE_SUB(NOW(), INTERVAL 2 DAY));
 
-- ------------------------------------------------------------
-- Rapports
-- ------------------------------------------------------------
INSERT INTO reports (generated_by, report_type, file_path, generated_at) VALUES
(1, 'monthly',  '/storage/reports/2025/05/rapport_mensuel_mai_2025.pdf',        DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'weekly',   '/storage/reports/2025/06/rapport_hebdo_s22_2025.xlsx',         DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'daily',    '/storage/reports/2025/06/rapport_quotidien_2025-06-12.pdf',    DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'monthly',  '/storage/reports/2025/04/rapport_mensuel_avril_2025.xlsx',     DATE_SUB(NOW(), INTERVAL 32 DAY)),
(5, 'daily',    '/storage/reports/2025/06/audit_export_2025-06-11.csv',         DATE_SUB(NOW(), INTERVAL 2 DAY));