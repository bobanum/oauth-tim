CREATE TABLE "app" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL,
	"app_key"	TEXT NOT NULL UNIQUE,
	"app_secret"	TEXT,
	"description"	TEXT,
	"contact_email"	TEXT,
	"providers" TEXT,
	"databases" TEXT DEFAULT 'db.sqlite',
	"allowed_referer" TEXT,
	"allowed_ips" TEXT,
	"is_active"	BOOLEAN DEFAULT 1,
	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE "api_log" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"app_id" INTEGER NOT NULL,
	"endpoint" TEXT,
	"ip" TEXT,
	"referer" TEXT,
	"user_agent" TEXT,
	"status_code" INTEGER,
	"requested_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY("app_id") REFERENCES app("id")
);
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
CREATE TABLE
	"client" (
		"id" TEXT,
		"secret" TEXT NOT NULL,
		"name" TEXT NOT NULL,
		"redirect_uri" TEXT NOT NULL,
		"grant_types" TEXT NOT NULL,
		"scope" TEXT,
		"extra" TEXT,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id")
	);
CREATE TABLE
	"provider" (
		"id" TEXT,
		"name" TEXT NOT NULL,
		"prefix" TEXT NOT NULL UNIQUE,
        "authorize_url" TEXT NOT NULL,
        "token_url" TEXT NOT NULL,
        "user_info_url" TEXT NOT NULL,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id")
	);

CREATE TABLE
	"access_token" (
		"id" INTEGER,
		"access_token" TEXT NOT NULL UNIQUE,
		"client_id" TEXT NOT NULL,
		"user_id" TEXT,
		"scope" TEXT,
		"expires_at" TIMESTAMP NOT NULL,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id"),
		FOREIGN KEY ("client_id") REFERENCES "client" ("id"),
		FOREIGN KEY ("user_id") REFERENCES "user" ("id")
	);

CREATE TABLE
	"authorization_code" (
		"id" INTEGER,
		"code" TEXT UNIQUE,
		"client_id" TEXT NOT NULL,
		"user_id" TEXT NOT NULL,
		"redirect_uri" TEXT NOT NULL,
		"scope" TEXT,
		"expires_at" TIMESTAMP NOT NULL,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id"),
		FOREIGN KEY ("client_id") REFERENCES "client" ("id"),
		FOREIGN KEY ("user_id") REFERENCES "user" ("id")
	);

CREATE TABLE
	"blacklisted_token" ("id" INTEGER, "revoked_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY ("id"));

CREATE TABLE
	"refresh_token" (
		"id" INTEGER,
		"refresh_token" TEXT NOT NULL UNIQUE,
		"client_id" INTEGER NOT NULL,
		"user_id" TEXT,
		"expires_at" TIMESTAMP NOT NULL,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id"),
		FOREIGN KEY ("client_id") REFERENCES "client",
		FOREIGN KEY ("user_id") REFERENCES "user"
	);

CREATE TABLE
	"scope" (
		"id" INTEGER,
		"name" TEXT NOT NULL UNIQUE,
		"description" TEXT,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id")
	);

-- Insert dummy data
INSERT INTO
	"app" ("id", "name", "app_key", "app_secret", "description", "contact_email", "providers", "databases", "allowed_referer", "allowed_ips", "is_active")
	VALUES
	(1, "test", "77b83e64c9a39c4b0f1f0f3b5dadd712", "5a8dc1bbb3a722897d88234987b11327c5d86dfb633f8ebc4c9c2a55371874f6", "Juste un test", "bobanum@gmail.com", "google|azure|github", "kweez.sqlite|school.sqlite|oauth.sqlite", "localhost:5555", null, true);

INSERT INTO
	"provider" ("id", "name", "prefix", "authorize_url", "token_url", "user_info_url")
VALUES
	("1", "Microsoft", "AZURE", "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize", "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", "https://graph.microsoft.com/v1.0/me"),
	("2", "Google", "GOOGLE", "https://accounts.google.com/o/oauth2/v2/auth", "https://oauth2.googleapis.com/token", "https://www.googleapis.com/oauth2/v3/userinfo"),
	("3", "Facebook", "FACEBOOK", "https://www.facebook.com/v10.0/dialog/oauth", "https://graph.facebook.com/v10.0/oauth/access_token", "https://graph.facebook.com/me?fields=id,name,email,picture"),
	("4", "Github", "GITHUB", "https://github.com/login/oauth/authorize", "https://github.com/login/oauth/access_token", "https://api.github.com/user");

INSERT INTO
	"client" ("id", "secret", "name", "redirect_uri", "grant_types", "scope")
VALUES
	("f98acb8c06e8ce5e6c25", "f46a568ba6006033848c2d5b1c6ef7b7939d792b", "Github", "http://localhost:8000/index.php", "read", NULL);

INSERT INTO
	"access_token" ("id", "access_token", "client_id", "user_id", "scope", "expires_at")
VALUES
	(1, "f98acb8c06e8ce5e6c25", "f98acb8c06e8ce5e6c25", 1, NULL, datetime('now', '+1 hour')),
	(2, "f46a568ba6006033848c2d5b1c6ef7b7939d792b", "f98acb8c06e8ce5e6c25", 2, NULL, datetime('now', '+1 hour'));

INSERT INTO
	"authorization_code" ("id", "code", "client_id", "user_id", "redirect_uri", "scope", "expires_at")
VALUES
	(1, "f98acb8c06e8ce5e6c25", "f98acb8c06e8ce5e6c25", 1, "http://localhost:8000/index.php", NULL, datetime('now', '+10 minutes')),
	(2, "f46a568ba6006033848c2d5b1c6ef7b7939d792b", "f98acb8c06e8ce5e6c25", 2, "http://localhost:8000/index.php", NULL, datetime('now', '+10 minutes'));

INSERT INTO
	"blacklisted_token" ("id", "revoked_at")
VALUES
	(1, datetime('now')),
	(2, datetime('now'));

INSERT INTO
	"refresh_token" ("id", "refresh_token", "client_id", "user_id", "expires_at")
VALUES
	(1, "f98acb8c06e8ce5e6c25", 1, 1, datetime('now', '+30 days')),
	(2, "f46a568ba6006033848c2d5b1c6ef7b7939d792b", 2, 2, datetime('now', '+30 days'));

INSERT INTO
	"scope" ("id", "name", "description")
VALUES
	(1, "read", "Allows reading data"),
	(2, "write", "Allows writing data"),
	(3, "admin", "Allows administrative actions");

