-- Aggiunta colonna Ruolo alla tabella Utenti
ALTER TABLE Utenti 
ADD COLUMN Ruolo ENUM('Admin', 'Developer', 'User') NOT NULL DEFAULT 'User';
