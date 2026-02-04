<?php
session_start();

// --- LOGIQUE DE POINTAGE ---

// 1. Liste des élèves
$eleves = [
    "nomena", "tojo", "ricardo", "toky", "jedidia", "jean", "mickael", "ben"
];
$SESSION_KEY = 'statuts_eleves_luxe';

// 2. Initialisation des statuts
if (!isset($_SESSION[$SESSION_KEY])) {
    $_SESSION[$SESSION_KEY] = array_fill_keys($eleves, "Absent");
}

// 3. Traitement des actions (changement de statut)
if (isset($_GET['action']) && isset($_GET['eleve'])) {
    $eleve_a_modifier = $_GET['eleve'];
    $nouvel_etat = $_GET['action'];

    if (array_key_exists($eleve_a_modifier, $_SESSION[$SESSION_KEY]) && in_array($nouvel_etat, ['present', 'absent'])) {
        $_SESSION[$SESSION_KEY][$eleve_a_modifier] = ($nouvel_etat == 'present') ? "Présent" : "Absent";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 4. Action de réinitialisation
if (isset($_GET['reset'])) {
    $_SESSION[$SESSION_KEY] = array_fill_keys($eleves, "Absent");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 5. Calculs pour l'affichage et le graphe
$statuts = $_SESSION[$SESSION_KEY];
$comptes = array_count_values($statuts);
$presents = $comptes['Présent'] ?? 0;
$absents = $comptes['Absent'] ?? 0;
$total = count($eleves);
$taux_presence = ($total > 0) ? round(($presents / $total) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pointage de Classe | Édition Luxe</title>
    <!-- Ajout de Chart.js pour les graphes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@300;400;500&display=swap');

        :root {
            --primary-color: #D4AF37; /* Or premium */
            --secondary-color: #1a1a1a; /* Noir presque pur */
            --text-color: #cccccc;
            --background-color: #0a0a0a;
            --glass-bg: rgba(26, 26, 26, 0.6); /* Fond verre semi-transparent */
            --glass-border: rgba(255, 255, 255, 0.1);
            --present-color: #28a745;
            --absent-color: #dc3545;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
            background-image: linear-gradient(145deg, #111 0%, #000 100%);
            color: var(--text-color);
            line-height: 1.7;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            padding: 40px 20px;
            color: white;
            position: relative;
        }

        header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            color: var(--primary-color);
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            padding: 30px;
            background: var(--glass-bg);
            border-radius: 15px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            text-align: center;
        }

        .card h2 {
            margin-top: 0;
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .summary-item { font-size: 1.5rem; font-weight: 700; }
        .summary-item .label { display: block; font-size: 0.9rem; font-weight: 400; color: var(--text-color); }
        .summary-item .present { color: var(--present-color); }
        .summary-item .absent { color: var(--absent-color); }
        .summary-item .rate { color: var(--primary-color); }

        #graph-container { height: 250px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border-bottom: 1px solid var(--glass-border); text-align: left; }
        th { font-family: 'Montserrat', sans-serif; background-color: rgba(212, 175, 55, 0.1); color: var(--primary-color); text-align: center; }
        td:first-child { font-weight: 500; color: #fff; text-transform: capitalize; }
        td { text-align: center; }

        .status { font-weight: bold; padding: 6px 12px; border-radius: 16px; color: white; font-size: 0.9em; min-width: 80px; display: inline-block; }
        .status-present { background-color: var(--present-color); }
        .status-absent { background-color: var(--absent-color); }

        .actions a { text-decoration: none; padding: 8px 12px; margin: 0 5px; border-radius: 5px; color: white; transition: all 0.2s; border: 1px solid transparent; }
        .actions a:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.4); }
        .btn-present { background-color: var(--present-color); border-color: var(--present-color); }
        .btn-absent { background-color: var(--absent-color); border-color: var(--absent-color); }
        
        .btn-reset { display: block; width: 200px; margin: 40px auto 10px; text-align: center; background: transparent; color: #aaa; padding: 12px; border-radius: 5px; text-decoration: none; font-weight: bold; border: 1px solid #555; transition: all 0.3s ease; }
        .btn-reset:hover { background: var(--primary-color); color: var(--secondary-color); border-color: var(--primary-color); box-shadow: 0 0 15px rgba(212, 175, 55, 0.5);
        }

        footer {
            text-align: center;
            padding: 30px;
            background-color: var(--secondary-color);
            color: #888;
            font-size: 0.9rem;
            margin-top: 40px;
            border-top: 1px solid var(--glass-border);
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <h1><i class="fas fa-clipboard-user"></i> Feuille de Présence</h1>
        </header>

        <main>
            <div class="dashboard">
                <div class="card">
                    <h2>Vue d'Ensemble</h2>
                    <div class="summary-grid">
                        <div class="summary-item"><span class="present"><?php echo $presents; ?></span><span class="label">Présents</span></div>
                        <div class="summary-item"><span class="absent"><?php echo $absents; ?></span><span class="label">Absents</span></div>
                        <div class="summary-item"><span class="rate"><?php echo $taux_presence; ?>%</span><span class="label">Présence</span></div>
                    </div>
                </div>
                <div class="card">
                    <h2>Répartition Graphique</h2>
                    <div id="graph-container"><canvas id="presenceChart"></canvas></div>
                </div>
            </div>

            <div class="card">
                <h2>Liste des Élèves</h2>
                <table>
                    <thead>
                        <tr><th>Élève</th><th>Statut Actuel</th><th>Mettre à Jour</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves as $eleve): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(ucfirst(trim($eleve))); ?></td>
                                <td>
                                    <?php
                                        $statut = $statuts[$eleve];
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
            </div>

            <a href="?reset=true" class="btn-reset">Réinitialiser la journée</a>
        </main>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Système de Pointage. Tous droits réservés.</p>
    </footer>

    <script>
        const ctx = document.getElementById('presenceChart').getContext('2d');
        const presenceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Présents', 'Absents'],
                datasets: [{
                    data: [<?php echo $presents; ?>, <?php echo $absents; ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderColor: ['#28a745', '#dc3545'],
                    borderWidth: 1,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: 'white', font: { family: 'Roboto' } }
                    }
                }
            }
        });
    </script>

</body>
</html>