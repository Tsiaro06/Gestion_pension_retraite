<?php
session_start();

// Définir le chemin racine du projet
define('BASE_PATH', __DIR__);

// Inclusion des fichiers de configuration et des fonctions
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils/functions.php';

// Déterminer l'onglet actif (ou page active)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Liste des onglets/pages valides pour sécuriser le routage
$validTabs = ['dashboard', 'personnes', 'tarifs', 'paiements', 'conjoints', 'rapports'];

// Inclusion de l'en-tête du site
include BASE_PATH . '/templates/header.php';

// Routage des pages selon l'onglet
$page = BASE_PATH . '/pages/' . $activeTab . '.php';

if (in_array($activeTab, $validTabs) && file_exists($page)) {
    include $page;
} else {
    // Si la page demandée n'existe pas ou n'est pas valide, charger le dashboard par défaut
    include BASE_PATH . '/pages/dashboard.php';
}

// Inclusion du pied de page
include BASE_PATH . '/templates/footer.php';
?>