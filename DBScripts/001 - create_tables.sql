-- Creazione della tabella Brani
CREATE TABLE Brani (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Titolo VARCHAR(255) NOT NULL,
    Tipologia ENUM('Lode', 'Adorazione') NOT NULL
);

-- Creazione della tabella BraniSuonati
CREATE TABLE BraniSuonati (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    IdBrano INT NOT NULL,
    BranoSuonatoIl DATE NOT NULL,
    FOREIGN KEY (IdBrano) REFERENCES Brani(Id) ON DELETE CASCADE
);

-- Creazione della tabella Utenti
CREATE TABLE Utenti (
    Id INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL
);