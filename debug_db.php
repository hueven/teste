<?php
require_once 'config.php';
$pdo = getDbConnection();
$id = 10;
$stmt = $pdo->prepare("SELECT id, title FROM collections WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
echo json_encode($row);
?>
