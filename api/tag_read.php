<?php 
// Fichier : api/tag_read.php
include_once("../db/index.php");

// Lire les données brutes envoyées
$raw_data = file_get_contents('php://input');

// Débogage : Afficher les données reçues (désactiver en prod si besoin)
var_dump($raw_data);

// Convertir les données JSON en tableau PHP
$data = json_decode($raw_data, true);

// Vérifier si la conversion a réussi
if (!$data) {
    echo json_encode(['error' => 'Données mal formatées']);
    exit;
}

// Vérifier la présence des paramètres nécessaires
if (!isset($data['tag_id']) || !isset($data['adresse_MAC_esp'])) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$tag_id = $data['tag_id'];
$esp_mac = $data['adresse_MAC_esp'];

// 1. Trouver le service correspondant à cette adresse MAC (service actuel/destination)
$stmt = $db->prepare("SELECT id FROM services WHERE adresse_MAC_esp = ?");
if (!$stmt) {
    echo json_encode(['error' => 'Erreur dans la préparation de la requête']);
    exit;
}

$stmt->bind_param("s", $esp_mac);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['error' => 'ESP non enregistré']);
    exit;
}

$service_row = $result->fetch_assoc();
$current_service_id = $service_row['id']; // service_destination

// 2. Trouver l'équipement par son tag RFID
$stmt = $db->prepare("SELECT id, service_source_id FROM equipements WHERE tag_id = ?");
if (!$stmt) {
    echo json_encode(['error' => 'Erreur dans la préparation de la requête']);
    exit;
}

$stmt->bind_param("s", $tag_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['error' => 'Tag inconnu']);
    exit;
}

$equipment = $result->fetch_assoc();
$equipment_id = $equipment['id'];
$service_source_id = $equipment['service_source_id'];

// 3. Trouver le dernier déplacement (si existant)
$stmt = $db->prepare("SELECT service_destination_id FROM deplacements WHERE equipement_id = ? ORDER BY date_heure DESC LIMIT 1");
if (!$stmt) {
    echo json_encode(['error' => 'Erreur dans la préparation de la requête']);
    exit;
}

$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

// Déterminer le service source pour ce déplacement
$service_source_for_this_move = $service_source_id; // par défaut

if ($result->num_rows > 0) {
    $last_move = $result->fetch_assoc();
    $service_source_for_this_move = $last_move['service_destination_id'];
}

// Vérifier si le service source est identique au service de destination
if ($service_source_for_this_move == $current_service_id) {
    // Si l'équipement est déjà dans ce service, on n'enregistre pas le déplacement
    echo json_encode(['info' => 'Équipement déjà dans ce service - pas de déplacement enregistré']);
    exit;
}

// 4. Enregistrer le déplacement uniquement si changement de service
$db->begin_transaction();

try {
    $stmt = $db->prepare("INSERT INTO deplacements (equipement_id, date_heure, service_source_id, service_destination_id) VALUES (?, NOW(), ?, ?)");
    if (!$stmt) {
        throw new Exception("Erreur dans la préparation de la requête d'insertion des déplacements");
    }
    
    $stmt->bind_param("iii", $equipment_id, $service_source_for_this_move, $current_service_id);
        
    if (!$stmt->execute()) {
        throw new Exception("Erreur d'enregistrement dans la table des déplacements");
    }
    
    $db->commit();
    echo json_encode(['success' => 'Déplacement enregistré']);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>
