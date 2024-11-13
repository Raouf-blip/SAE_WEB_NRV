<?php


namespace iutnc\nrv\action;

use iutnc\nrv\festival\Spectacle;
use iutnc\nrv\festival\Soiree;
use iutnc\nrv\render\SpectacleRenderer;
use iutnc\nrv\render\SoireeRenderer;
use iutnc\nrv\repository\NrvRepository;
use PDO;

class AddSoireeAction extends Action
{
    protected function get(): string
    {
        $repository = NrvRepository::getInstance();
        $lieux = $repository->getAllLieuxDeSoiree();

        $lieuxListe = '';
        foreach ($lieux as $lieu) {
            $lieuxListe .= "<label><input type='radio' name='soiree_lieu' value='{$lieu['idLieu']}' required> {$lieu['adresse']}</label><br>";
        }

        return <<<HTML
        <form method="post" action="?action=add-soiree" enctype="multipart/form-data">
            <label for="soiree-name">Nom de la soirée :</label>
            <input type="text" id="soiree-name" name="soiree_name" required>

            <label for="theme-soiree">Thématique :</label>
            <input type="text" id="theme-soiree" name="theme_soiree" required>

            <label for="date-soiree">Date de la soirée :</label>
            <input type="date" id="date-soiree" name="date_soiree" required>

            <label for="soiree-horaireDebut">Heure de début (HH:MM) :</label>
            <input type="time" id="soiree-horaireDebut" name="soiree_horaireDebut" required>
            
            <fieldset>
                <legend>Lieu :</legend>
                $lieuxListe
            </fieldset>

            <label for="tarif-soiree">Tarif d'un billet :</label>
            <input type="number" step="0.01" min="0" id="tarif-soiree" name="tarif_soiree" required>
            
            <button type="submit">Créer la soiree</button>
        </form>
        HTML;
    }

    protected function post(): string
    {
        $nom = filter_var($_POST['soiree_name'], FILTER_SANITIZE_STRING);
        $dateSoiree = filter_var($_POST['date_soiree'], FILTER_SANITIZE_STRING);
        $horaireDebut = filter_var($_POST['soiree_horaireDebut'], FILTER_SANITIZE_STRING);
        $lieu = filter_var($_POST['soiree_lieu'], FILTER_SANITIZE_STRING);
        $tarif = filter_var($_POST['tarif_soiree'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $theme = filter_var($_POST['theme_soiree'] ?? 'Aucun thème', FILTER_SANITIZE_STRING);


        $repository = NrvRepository::getInstance();

        $soiree = new Soiree(
            $nom,
            $dateSoiree,
            $lieu,
            $theme,
            $horaireDebut,
            $tarif
        );

        $idSoiree = $repository->setSoiree($soiree);

    
        $url = "Location: index.php?action=list-soirees&id=" . $idSoiree;
        header($url);
        exit;

        return "";
    }
}