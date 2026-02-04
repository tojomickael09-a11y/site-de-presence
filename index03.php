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

// 3. Calculs pour l'affichage et le graphe
$statuts = $_SESSION[$SESSION_KEY];
$comptes = array_count_values($statuts);
$presents = $comptes['Présent'] ?? 0;
$absents = $comptes['Absent'] ?? 0;
$total = count($eleves);
$taux_presence = ($total > 0) ? round(($presents / $total) * 100) : 0;

// --- LOGIQUE EMPLOI DU TEMPS ---
$jours = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi"];
$creneaux = [
    "08:00 - 09:30", "09:45 - 11:15", "11:30 - 13:00",
    "13:00 - 14:00", // Pause
    "14:00 - 15:30", "15:45 - 17:15"
];
$planning = [
    "08:00 - 09:30" => ["Lundi" => "Algo 1 (CM)", "Mardi" => "Analyse 1 (CM)", "Mercredi" => "", "Jeudi" => "Archi 1 (CM)", "Vendredi" => "Algo 1 (TP)"],
    "09:45 - 11:15" => ["Lundi" => "Algo 1 (TD)", "Mardi" => "Analyse 1 (TD)", "Mercredi" => "Anglais (TD)", "Jeudi" => "Archi 1 (TD)", "Vendredi" => "Algo 1 (TP)"],
    "11:30 - 13:00" => ["Lundi" => "Algèbre 1 (CM)", "Mardi" => "", "Mercredi" => "Méthodologie", "Jeudi" => "SE 1 (CM)", "Vendredi" => ""],
    "13:00 - 14:00" => ["Lundi" => "PAUSE", "Mardi" => "PAUSE", "Mercredi" => "PAUSE", "Jeudi" => "PAUSE", "Vendredi" => "PAUSE"],
    "14:00 - 15:30" => ["Lundi" => "Algèbre 1 (TD)", "Mardi" => "SE 1 (TP)", "Mercredi" => "", "Jeudi" => "SE 1 (TD)", "Vendredi" => "Projet Tutoré"],
    "15:45 - 17:15" => ["Lundi" => "", "Mardi" => "SE 1 (TP)", "Mercredi" => "", "Jeudi" => "", "Vendredi" => "Projet Tutoré"]
];
function getCoursClass($cours) {
    if (strpos($cours, 'PAUSE') !== false) return 'cours-pause';
    if (strpos($cours, '(CM)') !== false) return 'cours-cm';
    if (strpos($cours, '(TD)') !== false) return 'cours-td';
    if (strpos($cours, '(TP)') !== false) return 'cours-tp';
    return '';
}

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

        /* --- Styles Emploi du Temps --- */
        .timetable-card { grid-column: 1 / -1; /* Prend toute la largeur de la grille */ }
        .timetable-card h2 { text-align: center; }
        .timetable-card table { font-size: 0.9em; }
        .timetable-card th, .timetable-card td { padding: 10px; }
        .timetable-card th { background-color: rgba(212, 175, 55, 0.1); color: var(--primary-color); }
        .timetable-card td:first-child { font-weight: bold; background-color: rgba(255,255,255,0.05); }
        .cours-cm { background-color: rgba(40, 167, 69, 0.3); color: #a7e0b6; font-weight: bold; }
        .cours-td { background-color: rgba(23, 162, 184, 0.3); color: #9eeaf9; }
        .cours-tp { background-color: rgba(255, 193, 7, 0.3); color: #ffeeba; }
        .cours-pause { background-color: rgba(108, 117, 125, 0.2); color: #888; font-style: italic; }

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
    <style>
        /* --- Styles du Menu --- */
        .main-nav {
            background: var(--glass-bg);
            border-bottom: 1px solid var(--glass-border);
            padding: 0 20px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .main-nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .main-nav a {
            display: block;
            padding: 20px;
            color: var(--text-color);
            text-decoration: none;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
            position: relative;
        }
        .main-nav a:hover, .main-nav a.active {
            color: var(--primary-color);
        }
        .main-nav a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10px;
            right: 10px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        .main-nav i { margin-right: 8px; }

        /* --- Styles de la Modale --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); display: none; justify-content: center; align-items: center; z-index: 2000; backdrop-filter: blur(5px); }
        .modal-content { background: var(--glass-bg); padding: 40px; border-radius: 15px; border: 1px solid var(--glass-border); text-align: center; position: relative; width: 90%; max-width: 400px; }
        .modal-close { position: absolute; top: 15px; right: 20px; color: #fff; font-size: 30px; font-weight: bold; cursor: pointer; transition: color 0.2s; }
        .modal-close:hover { color: var(--primary-color); }
        .modal-content h3 { font-family: 'Montserrat', sans-serif; color: var(--primary-color); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; text-transform: capitalize; }

        /* --- Styles Photo 3D --- */
        .photo-container { perspective: 1000px; }
        .emoji-3d {
            width: 250px;
            height: 250px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transition: transform 0.1s ease-out;
            border: 3px solid var(--primary-color);
            /* Nouveaux styles pour l'emoji */
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 150px; /* Grande taille pour l'emoji */
            background: rgba(255, 255, 255, 0.05); /* Léger fond pour voir la boîte */
        }

        /* Rendre le nom de l'élève cliquable */
        .eleve-name { cursor: pointer; transition: color 0.2s; }
        .eleve-name:hover { color: var(--primary-color); }
    </style>
</head>
<body>

    <nav class="main-nav">
        <ul>
            <li><a href="index03.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="presence.php"><i class="fas fa-user-check"></i> Présence</a></li>
        </ul>
    </nav>

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

                <div class="card timetable-card">
                    <h2><i class="fas fa-calendar-alt"></i> Emploi du Temps - L1 Informatique</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <?php foreach ($jours as $jour) echo "<th>$jour</th>"; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creneaux as $heure): ?>
                            <tr>
                                <td><?php echo $heure; ?></td>
                                <?php foreach ($jours as $jour): ?>
                                    <?php
                                        $cours = $planning[$heure][$jour] ?? "";
                                        $class = getCoursClass($cours);
                                    ?>
                                    <td class='<?php echo $class; ?>'><?php echo $cours; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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