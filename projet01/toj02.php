<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Tableaux PHP</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; padding: 20px; }
        h2 { color: #333; }
        table { width: 100%; max-width: 600px; border-collapse: collapse; margin-bottom: 20px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #007BFF; color: white; }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

<?php
// Création d'un tableau simple (syntaxe courte moderne)
$monTableau = ["pomme", "banane", "orange"];

// Affichage du tableau
echo "<h2>Liste des fruits</h2>";
echo "<table>";
echo "<tr><th>Index</th><th>Fruit</th></tr>";
foreach ($monTableau as $index => $fruit) {
    echo "<tr><td>$index</td><td>$fruit</td></tr>";
}
echo "</table>";

// Création d'un tableau associatif (syntaxe courte moderne)
$monTableauAssociatif = [
  "nom" => "Doe",
  "prenom" => "John",
  "age" => 30
];

// Affichage du tableau associatif
echo "<h2>Détails de la personne</h2>";
echo "<table>";
echo "<tr><th>Caractéristique</th><th>Valeur</th></tr>";
foreach ($monTableauAssociatif as $cle => $valeur) {
    // ucfirst met la première lettre de la clé en majuscule (ex: nom -> Nom)
    echo "<tr><td>" . ucfirst($cle) . "</td><td>$valeur</td></tr>";
}
echo "</table>";
?>

</body>
</html>