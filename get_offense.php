<?php
require 'db.php';

if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT fine_amount FROM offenses WHERE id = ?");
    $stmt->execute([$id]);
    $offense = $stmt->fetch(PDO::FETCH_ASSOC);

    if($offense){
        echo json_encode($offense);
    } else {
        echo json_encode(["error" => "Not found"]);
    }
}