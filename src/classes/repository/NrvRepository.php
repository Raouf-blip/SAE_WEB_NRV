<?php

namespace iutnc\nrv\repository;

use iutnc\nrv\festival\Spectacle;
use iutnc\nrv\festival\Soiree;
use Exception;
use PDO;
use PDOException; // pour eviter l'erreur sur certains pc avec vscode

class NrvRepository {

    private PDO $pdo;
    private static ?NrvRepository $instance = null;
    private static array $config = [];

    private function __construct()
    {
        try {
            $this->pdo = new PDO(
                self::$config['dsn'],
                self::$config['username'],
                self::$config['password']
            );

        } catch (PDOException $e) {
            echo 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    public static function getInstance(): NrvRepository {
        if (is_null(self::$instance)) {
            if (empty(self::$config)) {
                throw new \Exception("Database configuration not set. Use setConfig() before getInstance().");
            }
            self::$instance = new NrvRepository(self::$config);
        }
        return self::$instance;
    }

    // Methode pour definir la configuration de la base de données
    public static function setConfig(string $file): void
    {
        $conf = parse_ini_file($file);
        if ($conf === false) {
            throw new \Exception("Erreur lors de la lecture du fichier de configuration");
        }
        self::$config = $conf;
    }


    // Methode pour obtenir les infos d'un utilisateur
    public function findInfos(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchObject();
    }


    // Methode pour obtenir tous les spectacles
    public function getAllSpectacles(): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    idSpectacle, 
                    nomSpectacle, 
                    horaireDebut, 
                    horaireFin, 
                    idStyle, 
                    statut, 
                    lienAudio, 
                    description 
                FROM spectacle
            ');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $result ?: []; 

        } catch (Exception $e) {
            return [];
        }
    }

    // Methode pour obtenir toutes les soirees
    public function getAllSoirees(): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    idSoiree, 
                    nomSoiree, 
                    dateSoiree, 
                    idLieu,
                    tarif,
                    thematique,
                    horaire
                FROM soiree
            ');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


            return $result ?: [];

        } catch (Exception $e) {
            return [];
        }
    }



    // Methode pour obtenir le nom d'un style par son id
    public function getStyleNom(string $id){
        $stmt = $this->pdo->prepare("SELECT nomStyle FROM style WHERE idStyle = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Methode pour obtenir tous les styles
    public function getAllStyles(){
        $stmt = $this->pdo->prepare('SELECT idStyle, nomStyle FROM style');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $result ?: []; 
    }


    // Methode pour obtenir les images d'un spectacle
    public function getImagesBySpectacleId(int $idSpectacle)
    {
        $query = "SELECT i.nomfichier 
                  FROM Image i
                  JOIN SpectacleImage si ON si.idImage = i.idImage
                  WHERE si.idSpectacle = :idSpectacle";
                  
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':idSpectacle', $idSpectacle, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


    // Methode pour obtenir les artistes d'un spectacle
    public function getArtisteBySpectacleId(int $idSpectacle)
    {
    $artistes = [];
    $query = "SELECT a.nomArtiste 
              FROM Artiste a
              JOIN Performer p ON a.idArtiste = p.idArtiste
              WHERE p.idSpectacle = :idSpectacle";
    
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':idSpectacle', $idSpectacle, PDO::PARAM_INT);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $artistes[] = $row['nomArtiste'];
    }
    
    return $artistes;
    }


    // Methode pour obtenir tous les noms d'artistes
    public function getAllNomArtiste(): array
    {
        $stmt = $this->pdo->prepare("SELECT idArtiste, nomArtiste FROM artiste");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }


    // Methode pour le nom d'un lieu par son id
    public function getLieuNom(mixed $idLieu)
    {
        $stmt = $this->pdo->prepare("SELECT CONCAT(nomLieu, ', ', adresse) AS lieuAdresse FROM lieu WHERE idLieu = :idLieu");
        $stmt->bindParam(':idLieu', $idLieu);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Methode pour obtenir toutes les dates
    public function getAllDates() : array{
        $stmt = $this->pdo->prepare("SELECT dateSoiree FROM soiree");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    // Methode pour obtenir les spectacles d'une date
    public function getAllSpecAtDate(string $datesoir) : array{
        $stmt = $this->pdo->prepare('SELECT idspectacle FROM soiree
                                    inner join spectaclesoiree as ss on ss.idsoiree = soiree.idsoiree
                                    WHERE datesoiree = :datesoir');
        $stmt->bindParam(':datesoir', $datesoir, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }


    // Methode pour obtenir le nom et l'adresse de tous les lieux
    public function getAllLieux() : array {
        $stmt = $this->pdo->prepare('SELECT idLieu, CONCAT(nomLieu, ", ", adresse) AS lieuAdresse FROM lieu');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



    // Methode pour obtenir les spectacles d'un lieu
    public function getAllSpecAtLieu(string $idLieu) : array{
        $stmt = $this->pdo->prepare('SELECT idspectacle FROM soiree
                                    inner join spectaclesoiree as ss on ss.idsoiree = soiree.idsoiree
                                    WHERE idLieu = :idLieu');
        $stmt->bindParam(':idLieu', $idLieu, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }


    // Methode pour toutes les soirées où un spectacle est joué
    public function getAllSoireeForSpec(int $idSpectacle) : array{
        $stmt = $this->pdo->prepare('
                SELECT 
                    soiree.idSoiree, 
                    nomSoiree, 
                    dateSoiree, 
                    idLieu,
                    tarif,
                    thematique,
                    horaire
                FROM soiree
                inner join spectaclesoiree ss on ss.idsoiree = soiree.idsoiree
                where :idSpectacle = ss.idSpectacle

            ');
            $stmt->bindParam(':idSpectacle', $idSpectacle, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


            return $result ?: [];
    }

    // Methode pour obtenir un style par nom
    private function getIdStyleByName(string $style): int
    {
        $stmt = $this->pdo->prepare("SELECT idStyle FROM style WHERE nomStyle = :style");
        $stmt->bindParam(':style', $style);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return (int) $id;
    }


    // Methode pour obtenir un spectacle par id
    public function getSpectacleById(mixed $id)
    {
        $stmt = $this->pdo->prepare("SELECT s.*, GROUP_CONCAT(soiree.idSoiree) AS soirees_id
        FROM spectacle s
        LEFT JOIN spectaclesoiree ss ON s.idSpectacle = ss.idSpectacle
        LEFT JOIN soiree soiree ON ss.idSoiree = soiree.idSoiree
        WHERE s.idSpectacle = :id
        GROUP BY s.idSpectacle");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // Methode pour obtenir les spectacles d'une soiree
    public function getSpecAtSoiree(int $idSoiree){
        $stmt = $this->pdo->prepare("SELECT idSpectacle
                                    FROM spectaclesoiree
                                    where idSoiree = :idSoiree");
        $stmt->bindParam(':idSoiree', $idSoiree, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];

    }


    // Methode pour obtenir tous les lieux de soiree
    public function getAllLieuxDeSoiree(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.idSoiree, s.nomSoiree, s.dateSoiree, s.horaire, s.thematique, s.tarif, 
                    l.idLieu, l.adresse
            FROM soiree s
            INNER JOIN lieu l ON s.idLieu = l.idLieu'
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Methode pour obtenir un lieu par nom
    private function getIdLieuByName(string $lieu): int
    {
        $stmt = $this->pdo->prepare("SELECT idLieu FROM lieu WHERE nomLieu = :lieu");
        $stmt->bindParam(':lieu', $lieu);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return (int) $id;
    }


    // Methode pour obtenir un audio par id
    public function getAudio(string $id){
        $stmt = $this->pdo->prepare("SELECT lienAudio FROM spectacle WHERE idSpectacle = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    // Methode pour obtenir les soirees par id
    public function getSoireeById($id)
    {
        $stmt = $this->pdo->prepare("SELECT soiree.*, GROUP_CONCAT(spectacle.idSpectacle) AS spectacles_id
            FROM soiree
            LEFT JOIN spectaclesoiree ss ON soiree.idSoiree = ss.idSoiree
            LEFT JOIN spectacle ON ss.idSpectacle = spectacle.idSpectacle
            WHERE soiree.idSoiree = :id
            GROUP BY soiree.idSoiree");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    // Methode pour associer une image a un spectacle
    public function associerImageAuSpectacle(int $idImage, int $idSpectacle): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO spectacleimage (idSpectacle, idImage) VALUES (:idSpectacle, :idImage)");
        $stmt->bindParam(':idSpectacle', $idSpectacle);
        $stmt->bindParam(':idImage', $idImage);
        $stmt->execute();
    }


    // Methode pour associer un artiste a un spectacle
    public function associerArtisteAuSpectacle(int $idArtiste, int $idSpectacle): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO performer (idArtiste, idSpectacle) VALUES (:idArtiste, :idSpectacle)");
        $stmt->bindParam(':idArtiste', $idArtiste, PDO::PARAM_INT);
        $stmt->bindParam(':idSpectacle', $idSpectacle, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Methode pour associer un spectacle a une liste de soirees
    public function associeSpectacleSoiree(int $spectacleId, array $soireeIds)
    {
        $stmt = $this->pdo->prepare("INSERT INTO spectaclesoiree (idSpectacle, idSoiree) VALUES (:idSpectacle, :idSoiree)");
        $stmt->bindParam(':idSpectacle', $spectacleId);

        foreach ($soireeIds as $soireeId) {
            $stmt->bindParam(':idSoiree', $soireeId);
            $stmt->execute();
        }
    }


    // Methode pour associer une soiree a une liste de spectacles
    public function associeSoireeSpectacle(int $soireeId, array $spectacleIds)
    {
        $stmt = $this->pdo->prepare("INSERT INTO spectaclesoiree (idSpectacle, idSoiree) VALUES (:idSpectacle, :idSoiree)");
        $stmt->bindParam(':idSpectacle', $spectacleId);

        foreach ($spectacleIds as $spectacleId) {
            $stmt->bindParam(':idSoiree', $soireeId);
            $stmt->execute();
        }
    }


    // Methode pour mettre a jour un spectacle
    public function updateSpectacle(mixed $id, array $array): bool
    {
        $stmt = $this->pdo->prepare("UPDATE spectacle SET 
                                            nomSpectacle = :nom,
                                            description = :description,
                                            idStyle = :style,
                                            horaireDebut = :debut,
                                            horaireFin = :fin,
                                            statut = :statut,
                                            lienAudio = :audio
                                        WHERE idSpectacle = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $array['nomSpectacle']);
        $stmt->bindParam(':description', $array['description']);
        $stmt->bindParam(':style', $array['idStyle']);
        $stmt->bindParam(':debut', $array['horaireDebut']);
        $stmt->bindParam(':fin', $array['horaireFin']);
        $stmt->bindParam(':statut', $array['statut']);
        $stmt->bindParam(':audio', $array['lienAudio']);
        return $stmt->execute();
    }


    // Methode pour mettre a jour les spectacles d'une soiree
    public function updateSoireeSpectacle($id, $soirees)
    {
        // Supprimer les associations existantes pour ce spectacle dans spectaclesoiree
        $stmt = $this->pdo->prepare("DELETE FROM spectaclesoiree WHERE idSpectacle = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Réinsérer les nouvelles associations avec les soirées
        $stmt = $this->pdo->prepare("INSERT INTO spectaclesoiree (idSpectacle, idSoiree) VALUES (:idSpectacle, :idSoiree)");
        $stmt->bindParam(':idSpectacle', $id);
        foreach ($soirees as $soiree) {
            $stmt->bindParam(':idSoiree', $soiree);
            $stmt->execute();
        }
    }


    // Methode pour mettre a jour une soiree
    public function updateSoiree($id, $nom, $date, $horaire, $lieu, $tarif, $thematique, array $spectacles)
    {
        $stmt = $this->pdo->prepare("UPDATE soiree SET 
                                            nomSoiree = :nom,
                                            dateSoiree = :date,
                                            horaire = :horaire,
                                            idLieu = :lieu,
                                            tarif = :tarif,
                                            thematique = :thematique
                                        WHERE idSoiree = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':horaire', $horaire);
        $stmt->bindParam(':lieu', $lieu);
        $stmt->bindParam(':tarif', $tarif);
        $stmt->bindParam(':thematique', $thematique);
        $stmt->execute();

        $stmt = $this->pdo->prepare("DELETE FROM spectaclesoiree WHERE idSoiree = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $stmt = $this->pdo->prepare("INSERT INTO spectaclesoiree (idSoiree, idSpectacle) VALUES (:idSoiree, :idSpectacle)");
        $stmt->bindParam(':idSoiree', $id);
        foreach ($spectacles as $spectacle) {
            $stmt->bindParam(':idSpectacle', $spectacle);
            $stmt->execute();
        }

        return true;
    }


    // Methode pour ajouter une soiree
    public function setSoiree(Soiree $s):int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO soiree (nomSoiree, dateSoiree, idLieu, tarif, thematique, horaire) 
            VALUES (:nomSoiree, :dateSoiree, :idLieu, :tarif, :thematique, :horaire)"
        );

        $nomSoiree = $s->nomSoiree;
        $dateSoiree = $s->dateSoiree;
        $tarif = $s->tarif;
        $thematique = $s->thematique;
        $horaireSoiree = $s->horaire;
        $lieu = $s-> lieu;

        $stmt->bindParam(':nomSoiree', $nomSoiree);
        $stmt->bindParam(':horaire', $horaireSoiree);
        $stmt->bindParam(':dateSoiree', $dateSoiree);
        $stmt->bindParam(':idLieu', $lieu);
        $stmt->bindParam(':tarif', $tarif);
        $stmt->bindParam(':thematique', $thematique);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }


    // Methode pour ajouter spectacle
    public function setSpectacle(Spectacle $s,string $idStyle): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO spectacle (nomSpectacle, horaireDebut, horaireFin, idStyle, statut, lienAudio, description) 
            VALUES (:nomSpectacle, :horaireDebut, :horaireFin, :idStyle, :statut, :lienAudio, :description)"
        );

        $nomSpectacle = $s->nom;
        $horaireDebut = $s->horaireDebut;
        $horaireFin = $s->horaireFin;
        echo $this->getIdStyleByName($s->style);
        // $idStyle = $this->getIdStyleByName($s->style);
        $statut = "à venir"; // Par défaut
        $lienAudio = $s->lienAudio;
        $description = $s->description;

        $stmt->bindParam(':nomSpectacle', $nomSpectacle);
        $stmt->bindParam(':horaireDebut', $horaireDebut);
        $stmt->bindParam(':horaireFin', $horaireFin);
        $stmt->bindParam(':idStyle', $idStyle);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':lienAudio', $lienAudio);
        $stmt->bindParam(':description', $description);

        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }


    // Methode pour ajouter un compte staff
    public function createStaff(string $email, string $mdp)
    {
        $stmt = $this->pdo->prepare("INSERT INTO utilisateur (email, mdp, role, droit) VALUES (:email, :mdp, 'staff', 50)");
        $stmt->bindParam(':email', $email);
        $password_hash = password_hash($mdp, PASSWORD_BCRYPT);
        $stmt->bindParam(':mdp', $password_hash);
        return $stmt->execute();
    }

    // Methode pour ajouter une image
    public function uploadImage(string $nomfichier): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO image (nomfichier) VALUES (:nomfichier)");
        $stmt->bindParam(':nomfichier', $nomfichier);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }


    // Methode pour dissocier les images d'un spectacle
    public function dissocierImagesDuSpectacle(mixed $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM spectacleimage WHERE idSpectacle = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }


}

