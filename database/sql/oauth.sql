CREATE TABLE "app" (
	"id"	INTEGER,
	"name"	TEXT NOT NULL,
	"app_key"	TEXT NOT NULL UNIQUE,
	"app_secret"	TEXT,
	"description"	TEXT,
	"contact_email"	TEXT,
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
CREATE TABLE
	"user" (
		"id" INTEGER,
		"username" TEXT NOT NULL UNIQUE,
		"password" TEXT NOT NULL,
		"email" TEXT NOT NULL UNIQUE,
		"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY ("id")
	);

CREATE TABLE
	"client" (
		"id" TEXT,
		"secret" TEXT NOT NULL,
		"name" TEXT NOT NULL,
		"redirect_uri" TEXT NOT NULL,
		"grant_types" TEXT NOT NULL,
		"scope" TEXT,
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
	"app" ("id", "name", "app_key", "app_secret", "description", "contact_email", "allowed_referer", "allowed_ips", "is_active")
	VALUES
	(1, "test", "77b83e64c9a39c4b0f1f0f3b5dadd712", "5a8dc1bbb3a722897d88234987b11327c5d86dfb633f8ebc4c9c2a55371874f6", "Juste un test", "bobanum@gmail.com", "localhost:5555", null, true);
INSERT INTO
	"user" ("id", "username", "password", "email")
VALUES
	(1, "johndoe", "$2a$06$7pdBjl4PJ155EuwoNnDkfO1LHxA2BO3BZCKZjwYSvWwEgoSrXCGBu", "johndoe@example.com"),
	(2, "janedoe", "$2a$06$DTXFz8AqdxFz3qJ4XUGQ8.7lrbY7ZvrRRz9r5TJOL78uC5EoD8hBS", "janedoe@example.com"),
	(3, "jimmydoe", "$2a$06$7rDt8UCIBEnOnWnD0R7XMOUEPMhGieyXhyak4WTQwCQaLFgJzT0i.", "jimmydoe@example.com"),
	(4, "jinnydoe", "$2a$06$do/9gmsyK0BnNZr6WdNryOKS1d5I/nvrFJNQcNtwFaEEqoW/y/3OO", "jinnydoe@example.com");

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