<?php
header("Content-Type: application/json; charset=utf-8");
require 'colissimo.class.php';

try {
    $colis = new suiviColissimo($_GET['id']);
} catch(Exception $e) {
    $error = array('error' => "Ce numéro de colis est invalide");
    echo json_encode($error);
    exit();
}
echo json_encode($colis->getSuivi(), JSON_PRETTY_PRINT);