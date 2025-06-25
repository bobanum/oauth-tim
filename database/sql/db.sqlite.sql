BEGIN TRANSACTION;
DROP TABLE IF EXISTS "access_token";
CREATE TABLE "access_token" (
	"id"	INTEGER,
	"user_id"	INTEGER NOT NULL,
	"app_id"	INTEGER,
	"token"	TEXT NOT NULL UNIQUE,
	"issued_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"expires_at"	DATETIME NOT NULL,
	"revoked"	BOOLEAN DEFAULT 0,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("app_id") REFERENCES "app"("id"),
	FOREIGN KEY("user_id") REFERENCES "user"("id")
);
DROP TABLE IF EXISTS "app";
CREATE TABLE "app" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL,
	"app_key"	TEXT NOT NULL UNIQUE,
	"app_secret"	TEXT,
	"is_active"	BOOLEAN DEFAULT 1,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "login_log";
CREATE TABLE "login_log" (
	"id"	INTEGER,
	"user_id"	INTEGER,
	"app_id"	INTEGER,
	"ip_address"	TEXT,
	"user_agent"	TEXT,
	"status"	TEXT,
	"message"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("app_id") REFERENCES "app"("id"),
	FOREIGN KEY("user_id") REFERENCES "user"("id")
);
DROP TABLE IF EXISTS "permission";
CREATE TABLE "permission" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL UNIQUE,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "provider";
CREATE TABLE "provider" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL UNIQUE,
	"type"	TEXT NOT NULL,
	"prefix"	TEXT,
	"authorize_url"	TEXT NOT NULL,
	"token_url"	TEXT NOT NULL,
	"user_info_url"	TEXT NOT NULL,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"updated_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "refresh_token";
CREATE TABLE "refresh_token" (
	"id"	INTEGER,
	"user_id"	INTEGER NOT NULL,
	"app_id"	INTEGER,
	"token"	TEXT NOT NULL UNIQUE,
	"issued_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"expires_at"	DATETIME NOT NULL,
	"revoked"	BOOLEAN DEFAULT 0,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("app_id") REFERENCES "app"("id"),
	FOREIGN KEY("user_id") REFERENCES "user"("id")
);
DROP TABLE IF EXISTS "role";
CREATE TABLE "role" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL UNIQUE,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "role_permission";
CREATE TABLE "role_permission" (
	"role_id"	INTEGER,
	"permission_id"	INTEGER,
	PRIMARY KEY("role_id","permission_id"),
	FOREIGN KEY("permission_id") REFERENCES "permission"("id"),
	FOREIGN KEY("role_id") REFERENCES "role"("id")
);
DROP TABLE IF EXISTS "token_blacklist";
CREATE TABLE "token_blacklist" (
	"id"	INTEGER,
	"token"	TEXT NOT NULL UNIQUE,
	"reason"	TEXT,
	"blacklisted_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
DROP TABLE IF EXISTS "user";
CREATE TABLE "user" (
	"id"	INTEGER,
	"provider_id"	INTEGER NOT NULL,
	"login"	TEXT NOT NULL,
	"email"	TEXT,
	"name"	TEXT,
	"password"	TEXT,
	"status"	TEXT DEFAULT 1,
	"extra"	TEXT,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	"updated_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("login","provider_id"),
	FOREIGN KEY("provider_id") REFERENCES "provider"("id")
);
DROP TABLE IF EXISTS "user_role";
CREATE TABLE "user_role" (
	"user_id"	INTEGER,
	"role_id"	INTEGER,
	PRIMARY KEY("user_id","role_id"),
	FOREIGN KEY("role_id") REFERENCES "role"("id"),
	FOREIGN KEY("user_id") REFERENCES "user"("id")
);
COMMIT;
