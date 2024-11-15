<?php

namespace iutnc\nrv\dispatch;

use iutnc\nrv\action\AddSpectacleAction;
use iutnc\nrv\action\ChangeSoireeAction;
use iutnc\nrv\action\ChangeSpectacleAction;
use iutnc\nrv\action\CreateStaffAction;
use iutnc\nrv\action\DefaultAction;
use iutnc\nrv\action\DisplaySoireesAction;
use iutnc\nrv\action\DisplaySpectaclesAction;
use iutnc\nrv\action\LoginAction;
use iutnc\nrv\action\LogoutAction;
use iutnc\nrv\action\DisplayStaffMenu;
use iutnc\nrv\action\AddSoireeAction;
use iutnc\nrv\auth\Authz;

// Classe Dispatcher : gère l'exécution des actions en fonction de l'URL
class Dispatcher {

    private string $action; // Nom de l'action à exécuter

    // Constructeur : initialise l'action
    public function __construct(string $action) {
        $this->action = $action;
    }

    // Méthode principale pour exécuter l'action
    public function run(): void {
        $html = '';

        // Choix de l'action en fonction de la valeur fournie
        switch ($this->action) {
            case 'modify-soiree':
                $actionInstance = new ChangeSoireeAction();
                break;

            case 'modify-spectacle':
                $actionInstance = new ChangeSpectacleAction();
                break;

            case 'menu-staff':
                $actionInstance = new DisplayStaffMenu();
                break;

            case 'list-soirees':
                $actionInstance = new DisplaySoireesAction();
                break;

            case 'createStaff':
                $actionInstance = new CreateStaffAction();
                break;

            case 'programme':
                $actionInstance = new DisplaySpectaclesAction();
                break;

            case 'login':
                $actionInstance = new LoginAction();
                break;

            case 'logout':
                $actionInstance = new LogoutAction();
                break;

            case 'add-spectacle':
                $actionInstance = new AddSpectacleAction();
                break;

            case 'add-soiree':
                $actionInstance = new AddSoireeAction();
                break;

            default:
                $actionInstance = new DefaultAction();
                break;
        }

        // Exécute l'action et récupère le résultat HTML
        $html = $actionInstance->execute();

        // Affiche la page avec le résultat
        $this->renderPage($html);
    }

    // Méthode pour générer la page avec Bulma
    private function renderPage(string $html): void {

        // Menu de navigation simplifié sans menu hamburger
        $menu = <<<HTML
<nav class="navbar is-spaced is-size-5 py-3" style="background-color: #1C1C5E; color: white;">
    <div class="navbar-brand">
        <a class="navbar-item has-text-weight-bold is-size-4" style="color: #F4F4F4" href="?action=default">Accueil</a>
        <a class="navbar-item has-text-weight-semibold" style="color: #F4F4F4" href="?action=programme">Programme</a>
        <a class="navbar-item has-text-weight-semibold" style="color: #F4F4F4" href="?action=list-soirees">Liste des Soirées</a>
        <a class="navbar-item has-text-weight-semibold" style="color: #F4F4F4" href="?action=programme&trie=preferences">Votre liste de préférence</a>
    </div>

    <!-- Menu classique sans burger (affichage en desktop) -->
    <div class="navbar-end is-hidden-mobile">
HTML;

        // Vérifie si l'utilisateur a un rôle spécifique
        $user = Authz::checkRole(50);
        if (!is_string($user)) {
            $menu .= <<<HTML
                <a class="navbar-item has-text-weight-semibold has-text-white" href="?action=menu-staff">Menu Gestion</a>
HTML;
        }

        // Ajoute les options en fonction de l'état de connexion
        if (Authz::estCo()) {
            $menu .= <<<HTML
                <span class="navbar-item has-text-white is-size-5">Connecté en tant que : {$_SESSION['user']['email']}</span>
                <a class="navbar-item button is-danger" href="?action=logout">Se Déconnecter</a>
HTML;
        } else {
            $menu .= <<<HTML
                <a class="navbar-item button is-primary" href="?action=login">Se Connecter</a>
HTML;
        }

        $menu .= "</div>";

// Menu responsive (affichage en mobile)
        $menu .= <<<HTML
    <div class="navbar-menu is-hidden-desktop">
        <div class="navbar-start">
            <a class="navbar-item has-text-weight-bold" href="?action=default">Accueil</a>
            <a class="navbar-item has-text-weight-semibold" href="?action=programme">Programme</a>
            <a class="navbar-item has-text-weight-semibold" href="?action=list-soirees">Liste des Soirées</a>
            <a class="navbar-item has-text-weight-semibold" href="?action=programme&trie=preferences">Votre liste de préférence</a>
HTML;

        // Vérifie si l'utilisateur a un rôle spécifique pour le menu staff
        if (!is_string($user)) {
            $menu .= <<<HTML
                <a class="navbar-item has-text-weight-semibold has-text-white" href="?action=menu-staff">Menu Gestion</a>
                HTML;
        }

        // Ajoute les options en fonction de l'état de connexion
        if (Authz::estCo()) {
            $menu .= <<<HTML
            <span class="navbar-item has-text-white is-size-5">Connecté en tant que : {$_SESSION['user']['email']}</span>
            <a class="navbar-item button is-danger is-danger" href="?action=logout">Se Déconnecter</a>
            HTML;
        } else {
            $menu .= <<<HTML
            <a class="navbar-item button is-primary is-succes" href="?action=login">Se Connecter</a>
            HTML;
        }

        $menu .= "</div></div></nav>";

        // Génère la page HTML complète
        echo <<<HTML
        <!DOCTYPE html>
        <html lang='fr' class="has-background-link-light">
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <link rel='stylesheet' href='src/assets/css/style.css'>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css'>
            <title>Festival de Musique - Administration</title>
        </head>
        <body>
            <script src="src/assets/js/index.js"></script>
            $menu
            <main class="section">
                <div class="container">
                    <p></p> <!-- espace -->
                    $html
                </div>
            </main>
        </body>
        </html>
        HTML;
    }
}
