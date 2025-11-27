-- Alter tables to use utf8mb4 charset for proper UTF-8 support
ALTER TABLE Brani CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE BraniSuonati CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE Utenti CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;