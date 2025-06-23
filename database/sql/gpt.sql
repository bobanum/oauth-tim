-- =========================
-- SCHEMA DE BASE DE DONNÉES POUR AUTHENTIFICATION ET AUTORISATION
-- =========================
DROP TABLE IF EXISTS access_token;

DROP TABLE IF EXISTS refresh_token;

DROP TABLE IF EXISTS token_blacklist;

DROP TABLE IF EXISTS login_log;

DROP TABLE IF EXISTS user_role;

DROP TABLE IF EXISTS user;

DROP TABLE IF EXISTS role_permission;

DROP TABLE IF EXISTS role;

DROP TABLE IF EXISTS permission;

DROP TABLE IF EXISTS app;

DROP TABLE IF EXISTS provider;

-- =========================
-- TABLE DES FOURNISSEURS D'AUTHENTIFICATION
-- =========================
CREATE TABLE IF NOT EXISTS
    provider (
        id INTEGER,
        name TEXT NOT NULL UNIQUE, -- Nom du fournisseur (ex: Google, Facebook, etc.)
        type TEXT NOT NULL, -- Type de fournisseur (OAuth2, SAML, etc.)
        prefix TEXT, -- Préfixe pour les variables d'environnement (ex: GOOGLE, FACEBOOK)
        authorize_url TEXT NOT NULL, -- URL d'autorisation pour OAuth2
        token_url TEXT NOT NULL, -- URL de token pour OAuth2
        user_info_url TEXT NOT NULL, -- URL pour récupérer les infos utilisateur
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY ("id" AUTOINCREMENT)
    );

INSERT INTO
    provider (id, name, type, prefix, authorize_url, token_url, user_info_url)
VALUES
    (
        1,
        'Azure',
        'OAuth2',
        'AZURE',
        'https://login.microsoftonline.com/${AZURE_TENANT}/oauth2/v2.0/authorize',
        'https://login.microsoftonline.com/${AZURE_TENANT}/oauth2/v2.0/token',
        'https://graph.microsoft.com/v1.0/me'
    ),
    (
        2,
        'Google',
        'OAuth2',
        'GOOGLE',
        'https://accounts.google.com/o/oauth2/auth',
        'https://oauth2.googleapis.com/token',
        'https://www.googleapis.com/oauth2/v3/userinfo'
    ),
    (
        3,
        'Facebook',
        'OAuth2',
        'FACEBOOK',
        'https://www.facebook.com/v10.0/dialog/oauth',
        'https://graph.facebook.com/v10.0/oauth/access_token',
        'https://graph.facebook.com/me?fields=id,name,email'
    ),
    (
        4,
        'Github',
        'OAuth2',
        'GITHUB',
        'https://github.com/login/oauth/authorize',
        'https://github.com/login/oauth/access_token',
        'https://api.github.com/user'
    ),(
        5,
        'Apple',
        'OAuth2',
        'APPLE',
        'https://appleid.apple.com/auth/authorize',
        'https://appleid.apple.com/auth/token',
        'https://appleid.apple.com/auth/userinfo'
    );

-- =========================
-- TABLE DES APPS CLIENTES
-- =========================
CREATE TABLE IF NOT EXISTS
    app (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        app_key TEXT NOT NULL UNIQUE, -- Clé publique transmise dans les requêtes
        app_secret TEXT, -- Facultatif, pour signature ou app interne
        is_active BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

-- =========================
-- ROLES ET PERMISSIONS
-- =========================
CREATE TABLE IF NOT EXISTS
    role (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);

CREATE TABLE IF NOT EXISTS
    permission (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE);

CREATE TABLE IF NOT EXISTS
    role_permission (
        role_id INTEGER,
        permission_id INTEGER,
        FOREIGN KEY (role_id) REFERENCES role (id),
        FOREIGN KEY (permission_id) REFERENCES permission (id),
        PRIMARY KEY (role_id, permission_id)
    );

-- =========================
-- UTILISATEURS
-- =========================
CREATE TABLE IF NOT EXISTS
    user (
        id INTEGER,
        provider_id INTEGER NOT NULL, -- ID du fournisseur d'authentification (OAuth, SSO, etc.)
        login TEXT NOT NULL,
        email TEXT,
        name TEXT,
        password TEXT, -- Auth locale (optionnel si OAuth)
        status TEXT DEFAULT 1, -- 0: inactif, 1: actif, 2: suspendu, 3: supprimé
        extra TEXT, -- JSON pour stocker des données additionnelles (ex: avatar, préférences)
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY ("id" AUTOINCREMENT),
        FOREIGN KEY (provider_id) REFERENCES provider (id),
        UNIQUE (login, provider_id) -- Un utilisateur ne peut pas avoir le même login pour le même fournisseur
    );

CREATE TABLE IF NOT EXISTS
    user_role (
        user_id INTEGER,
        role_id INTEGER,
        FOREIGN KEY (user_id) REFERENCES user (id),
        FOREIGN KEY (role_id) REFERENCES role (id),
        PRIMARY KEY (user_id, role_id)
    );

-- =========================
-- ACCESS TOKENS (JWT)
-- =========================
CREATE TABLE IF NOT EXISTS
    access_token (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        app_id INTEGER,
        token TEXT NOT NULL UNIQUE, -- le JWT
        issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        revoked BOOLEAN DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES user (id),
        FOREIGN KEY (app_id) REFERENCES app (id)
    );

-- =========================
-- REFRESH TOKENS
-- =========================
CREATE TABLE IF NOT EXISTS
    refresh_token (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        app_id INTEGER,
        token TEXT NOT NULL UNIQUE,
        issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        revoked BOOLEAN DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES user (id),
        FOREIGN KEY (app_id) REFERENCES app (id)
    );

-- =========================
-- TOKEN BLACKLIST (pour JWT stateless ou accès temporaire)
-- =========================
CREATE TABLE IF NOT EXISTS
    token_blacklist (id INTEGER PRIMARY KEY AUTOINCREMENT, token TEXT NOT NULL UNIQUE, reason TEXT, blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP);

-- =========================
-- LOGS DE CONNEXION (optionnel)
-- =========================
CREATE TABLE IF NOT EXISTS
    login_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        app_id INTEGER,
        ip_address TEXT,
        user_agent TEXT,
        status TEXT, -- success / failure
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user (id),
        FOREIGN KEY (app_id) REFERENCES app (id)
    );