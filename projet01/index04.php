<?php
session_start();

// 1. Liste des 10 élèves
$eleves = [
    "Dupont, Jean",
    "Martin, Léa",
    "Bernard, Lucas",
    "Petit, Manon",
    "Robert, Hugo",
    "Richard, Chloé",
    "Durand, Louis",
    "Moreau, Emma",
    "Simon, Gabriel",
    "Laurent, Alice"
];

// 2. Initialisation des statuts si la session est vide (au premier lancement)
if (!isset($_SESSION['statuts_eleves'])) {
    $_SESSION['statuts_eleves'] = [];
    foreach ($eleves as $eleve) {
        $_SESSION['statuts_eleves'][$eleve] = "Absent"; // Par défaut, tout le monde est absent
    }
    // On s'assure que la liste en session correspond à la liste actuelle
    $_SESSION['statuts_eleves'] = array_intersect_key($_SESSION['statuts_eleves'], array_flip($eleves));
}

// 3. Traitement des actions (changement de statut)
if (isset($_GET['action']) && isset($_GET['eleve'])) {
    $eleve_a_modifier = $_GET['eleve'];
    $nouvel_etat = $_GET['action'];

    // On vérifie que l'élève existe et que l'action est valide
    if (in_array($eleve_a_modifier, $eleves) && ($nouvel_etat == 'present' || $nouvel_etat == 'absent')) {
        $_SESSION['statuts_eleves'][$eleve_a_modifier] = ($nouvel_etat == 'present') ? "Présent" : "Absent";
    }
    // On redirige pour nettoyer l'URL et éviter de répéter l'action si on rafraîchit
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 4. Action de réinitialisation pour remettre tout le monde à "Absent"
if (isset($_GET['reset'])) {
    foreach ($eleves as $eleve) {
        $_SESSION['statuts_eleves'][$eleve] = "Absent";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 5. Compter les présents et les absents pour l'affichage
$comptes = array_count_values($_SESSION['statuts_eleves']);
$presents = $comptes['Présent'] ?? 0;
$absents = $comptes['Absent'] ?? 0;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des Présences</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f2f5; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #1d2129; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; padding: 15px; background-color: #f7f7f7; border-radius: 8px; font-size: 1.1em; font-weight: 500; }
        .summary-present { color: #28a745; }
        .summary-absent { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #007bff; color: white; }
        td:first-child { text-align: left; font-weight: 500; }
        .status { font-weight: bold; padding: 6px 12px; border-radius: 16px; color: white; font-size: 0.9em; }
        .status-present { background-color: #28a745; }
        .status-absent { background-color: #dc3545; }
        .actions a { text-decoration: none; padding: 8px 12px; margin: 0 5px; border-radius: 5px; color: white; transition: opacity 0.2s; }
        .actions a:hover { opacity: 0.8; }
        .btn-present { background-color: #28a745; }
        .btn-absent { background-color: #dc3545; }
        .btn-reset { display: block; width: 150px; margin: 30px auto 10px; text-align: center; background: #6c757d; color: white; padding: 12px; border-radius: 5px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Feuille de Présence de la Classe</h1>

        <div class="summary">
            <span class="summary-present">Présents : <?php echo $presents; ?></span>
            <span class="summary-absent">Absents : <?php echo $absents; ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nom de l'élève</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eleves as $eleve): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($eleve); ?></td>
                        <td>
                            <?php
                                $statut = $_SESSION['statuts_eleves'][$eleve];
                                $class = ($statut == 'Présent') ? 'status-present' : 'status-absent';
                                echo "<span class='status $class'>$statut</span>";
                            ?>
                        </td>
                        <td class="actions">
                            <a href="?eleve=<?php echo urlencode($eleve); ?>&action=present" class="btn-present">Présent</a>
                            <a href="?eleve=<?php echo urlencode($eleve); ?>&action=absent" class="btn-absent">Absent</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="?reset=true" class="btn-reset">Réinitialiser</a>
    </div>

</body>
</html>