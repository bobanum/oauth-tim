BEGIN TRANSACTION;
-- DROP TABLE IF EXISTS "provider";
-- CREATE TABLE "provider" (
-- 	"id"	INTEGER,
-- 	"name"	TEXT NOT NULL UNIQUE,
-- 	"type"	TEXT NOT NULL,
-- 	"prefix"	TEXT,
-- 	"authorize_url"	TEXT NOT NULL,
-- 	"token_url"	TEXT NOT NULL,
-- 	"user_info_url"	TEXT NOT NULL,
-- 	"created_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
-- 	"updated_at"	DATETIME DEFAULT CURRENT_TIMESTAMP,
-- 	PRIMARY KEY("id" AUTOINCREMENT)
-- );

INSERT INTO "provider" ("id","name","type","prefix","authorize_url","token_url","user_info_url","created_at","updated_at") VALUES (1,'Azure','OAuth2','AZURE','https://login.microsoftonline.com/${AZURE_TENANT}/oauth2/v2.0/authorize','https://login.microsoftonline.com/${AZURE_TENANT}/oauth2/v2.0/token','https://graph.microsoft.com/v1.0/me','2025-06-20 22:16:00','2025-06-20 22:16:00'),
 (2,'Google','OAuth2','GOOGLE','https://accounts.google.com/o/oauth2/auth','https://oauth2.googleapis.com/token','https://www.googleapis.com/oauth2/v3/userinfo','2025-06-20 22:16:00','2025-06-20 22:16:00'),
 (3,'Facebook','OAuth2','FACEBOOK','https://www.facebook.com/v10.0/dialog/oauth','https://graph.facebook.com/v10.0/oauth/access_token','https://graph.facebook.com/me?fields=id,name,email','2025-06-20 22:16:00','2025-06-20 22:16:00'),
 (4,'Github','OAuth2','GITHUB','https://github.com/login/oauth/authorize','https://github.com/login/oauth/access_token','https://api.github.com/user','2025-06-20 22:16:00','2025-06-20 22:16:00'),
 (5,'Apple','OAuth2','APPLE','https://appleid.apple.com/auth/authorize','https://appleid.apple.com/auth/token','https://appleid.apple.com/auth/userinfo','2025-06-20 22:16:00','2025-06-20 22:16:00');
COMMIT;
