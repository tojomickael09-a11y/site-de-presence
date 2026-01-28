<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Emploi du Temps</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f4f4; 
            padding: 20px; 
        }
        h1 { color: #333; text-align: center; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px auto; 
            background: white; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }
        th, td { 
            padding: 15px; 
            border: 1px solid #ddd; 
            text-align: center; 
        }
        th { background-color: #007BFF; color: white; }
        /* Style pour la première colonne (les heures) */
        td:first-child { 
            font-weight: bold; 
            background-color: #e9ecef; 
            color: #555;
        }
        tr:hover { background-color: #f1f1f1; }
    </style>
</head>
<body>

    <h1>Mon Emploi du Temps de la Semaine</h1>

    <?php
    // 1. Définition des jours et des créneaux horaires
    $jours = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi"];
    $creneaux = ["08h - 10h", "10h - 12h", "12h - 14h", "14h - 16h", "16h - 18h"];

    // 2. Remplissage des données (Tableau multidimensionnel : Heure => [Jour => Matière])
    $planning = [
        "08h - 10h" => [
            "Lundi" => "Mathématiques", "Mardi" => "Anglais", "Mercredi" => "Histoire", "Jeudi" => "Mathématiques", "Vendredi" => "Sport"
        ],
        "10h - 12h" => [
            "Lundi" => "Physique", "Mardi" => "Français", "Mercredi" => "SVT", "Jeudi" => "Anglais", "Vendredi" => "Philo"
        ],
        "12h - 14h" => [
            "Lundi" => "Pause", "Mardi" => "Pause", "Mercredi" => "Pause", "Jeudi" => "Pause", "Vendredi" => "Pause"
        ],
        "14h - 16h" => [
            "Lundi" => "Informatique", "Mardi" => "Sport", "Mercredi" => "Libre", "Jeudi" => "Physique", "Vendredi" => "Informatique"
        ],
        "16h - 18h" => [
            "Lundi" => "Étude", "Mardi" => "Libre", "Mercredi" => "Libre", "Jeudi" => "SVT", "Vendredi" => "Sortie"
        ]
    ];

    // 3. Affichage du tableau HTML
    echo "<table>";
    
    // En-tête du tableau (Les jours)
    echo "<tr>";
    echo "<th>Heure</th>"; // Coin haut gauche
    foreach ($jours as $jour) {
        echo "<th>$jour</th>";
    }
    echo "</tr>";

    // Corps du tableau (Les créneaux et les cours)
    foreach ($creneaux as $heure) {
        echo "<tr>";
        echo "<td>$heure</td>"; // Colonne des heures
        
        foreach ($jours as $jour) {
            // On récupère le cours s'il existe, sinon on met vide
            $cours = isset($planning[$heure][$jour]) ? $planning[$heure][$jour] : "";
            echo "<td>$cours</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    ?>
</body>
</html>