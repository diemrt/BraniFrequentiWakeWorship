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