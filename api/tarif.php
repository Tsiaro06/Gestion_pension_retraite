<?php
// require_once '../config/database.php';
require_once '../utils/functions.php';

function getDatabaseConnection() {
    $host = '127.0.0.1';
    $port = '3307';
    $dbname = 'bdpension';
    $username = 'root';
    $password = '';

    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

$db = getDatabaseConnection();

header('Content-Type: application/json');

// Récupérer les infos d'un tarif par son numéro
if (isset($_GET['num_tarif'])) {
    $num_tarif = $_GET['num_tarif'];

    try {
        $stmt = $db->prepare("SELECT * FROM tarif WHERE num_tarif = :num_tarif");
        $stmt->bindParam(':num_tarif', $num_tarif);
        $stmt->execute();
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tarif) {
            echo json_encode([
                'success' => true,
                'tarif' => $tarif
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Tarif non trouvé'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Si aucune action n'est spécifiée
echo json_encode([
    'success' => false,
    'message' => 'Action non spécifiée'
]);
?>