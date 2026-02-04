<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps - L1 Informatique</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f4f4; 
            color: #333;
            padding: 20px; 
        }
        h1 { color: #005a9e; text-align: center; }
        h2 { color: #555; text-align: center; font-weight: normal; margin-top: -15px; }
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
        th { background-color: #005a9e; color: white; }
        /* Style pour la première colonne (les heures) */
        td:first-child { 
            font-weight: bold; 
            background-color: #e9ecef; 
            color: #555;
        }
        /* Couleurs par type de cours */
        .cours-cm { background-color: #d1e7dd; color: #0f5132; font-weight: bold; } /* Vert */
        .cours-td { background-color: #cff4fc; color: #055160; } /* Bleu clair */
        .cours-tp { background-color: #fff3cd; color: #664d03; } /* Jaune */
        .cours-pause { background-color: #f8f9fa; color: #6c757d; font-style: italic; } /* Gris */
    </style>
</head>
<body>

    <h1>Emploi du Temps</h1>
    <h2>Licence 1 - Informatique</h2>

    <?php
    // 1. Définition des jours et des créneaux universitaires
    $jours = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi"];
    $creneaux = [
        "08:00 - 09:30",
        "09:45 - 11:15",
        "11:30 - 13:00",
        "13:00 - 14:00", // Pause déjeuner
        "14:00 - 15:30",
        "15:45 - 17:15"
    ];

    // 2. Planning type pour une L1 Informatique
    $planning = [
        "08:00 - 09:30" => [
            "Lundi" => "Algo 1 (CM)", "Mardi" => "Analyse 1 (CM)", "Mercredi" => "", "Jeudi" => "Archi 1 (CM)", "Vendredi" => "Algo 1 (TP)"
        ],
        "09:45 - 11:15" => [
            "Lundi" => "Algo 1 (TD)", "Mardi" => "Analyse 1 (TD)", "Mercredi" => "Anglais (TD)", "Jeudi" => "Archi 1 (TD)", "Vendredi" => "Algo 1 (TP)"
        ],
        "11:30 - 13:00" => [
            "Lundi" => "Algèbre 1 (CM)", "Mardi" => "", "Mercredi" => "Méthodologie", "Jeudi" => "SE 1 (CM)", "Vendredi" => ""
        ],
        "13:00 - 14:00" => [
            "Lundi" => "PAUSE", "Mardi" => "PAUSE", "Mercredi" => "PAUSE", "Jeudi" => "PAUSE", "Vendredi" => "PAUSE"
        ],
        "14:00 - 15:30" => [
            "Lundi" => "Algèbre 1 (TD)", "Mardi" => "SE 1 (TP)", "Mercredi" => "", "Jeudi" => "SE 1 (TD)", "Vendredi" => "Projet Tutoré"
        ],
        "15:45 - 17:15" => [
            "Lundi" => "", "Mardi" => "SE 1 (TP)", "Mercredi" => "", "Jeudi" => "", "Vendredi" => "Projet Tutoré"
        ]
    ];

    // 3. Fonction pour déterminer la classe CSS en fonction du cours
    function getCoursClass($cours) {
        if (strpos($cours, 'PAUSE') !== false) return 'cours-pause';
        if (strpos($cours, '(CM)') !== false) return 'cours-cm';
        if (strpos($cours, '(TD)') !== false) return 'cours-td';
        if (strpos($cours, '(TP)') !== false) return 'cours-tp';
        return '';
    }

    // 4. Affichage du tableau HTML
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
            $cours = $planning[$heure][$jour] ?? "";
            $class = getCoursClass($cours);
            echo "<td class='$class'>$cours</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    ?>
</body>
</html>