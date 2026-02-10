<?php
session_start();

// --- SÉCURITÉ ---
// Vérifier si l'utilisateur est connecté, sinon le renvoyer vers la page de connexion
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// --- CONFIGURATION ---
$SESSION_KEY_STATUS = 'statuts_eleves_luxe';
$SESSION_KEY_ELEVES = 'eleves_list_luxe';
$SESSION_KEY_EMOJIS = 'eleves_emojis_luxe';
$SESSION_KEY_ABSENCE_LOG = 'absence_log_luxe';
$ITEMS_PER_PAGE = 10;
$available_emojis = ['🧑‍💻', '👩‍🎨', '👨‍🔬', '🧑‍🚀', '👩‍🏫', '👨‍⚖️', '🧑‍⚕️', '👩‍🎤', '🧑‍🎓', '👩‍💼', '👨‍🔧', '🧑‍🌾'];
$user_role = $_SESSION['user']['role'];
$current_user = $_SESSION['user']['username'];

// --- INITIALISATION ---
// 1. Liste des élèves (depuis la session ou par défaut)
if (!isset($_SESSION[$SESSION_KEY_ELEVES])) {
    $_SESSION[$SESSION_KEY_ELEVES] = [
        "nomena", "tojo", "ricardo", "toky", "jedidia", "jean", "mickael", "ben"
    ];
}

// 2. Initialisation des statuts et emojis si de nouveaux élèves sont dans la liste
if (!isset($_SESSION[$SESSION_KEY_STATUS])) $_SESSION[$SESSION_KEY_STATUS] = [];
if (!isset($_SESSION[$SESSION_KEY_EMOJIS])) $_SESSION[$SESSION_KEY_EMOJIS] = [];

$_SESSION[$SESSION_KEY_STATUS] = array_merge(array_fill_keys($_SESSION[$SESSION_KEY_ELEVES], "Absent"), $_SESSION[$SESSION_KEY_STATUS]);
$_SESSION[$SESSION_KEY_STATUS] = array_intersect_key($_SESSION[$SESSION_KEY_STATUS], array_flip($_SESSION[$SESSION_KEY_ELEVES]));

if (!isset($_SESSION[$SESSION_KEY_ABSENCE_LOG])) $_SESSION[$SESSION_KEY_ABSENCE_LOG] = [];

// --- TRAITEMENT DES ACTIONS (POST & GET) ---

// Seul l'administrateur peut effectuer les actions de gestion
if ($user_role === 'admin') {
    // A. Ajouter un nouvel élève
    if (isset($_POST['add_eleve']) && !empty(trim($_POST['new_eleve_name']))) {
        $new_eleve = strtolower(trim(htmlspecialchars($_POST['new_eleve_name'])));
        if (!in_array($new_eleve, $_SESSION[$SESSION_KEY_ELEVES])) {
            $_SESSION[$SESSION_KEY_ELEVES][] = $new_eleve;
            $_SESSION[$SESSION_KEY_STATUS][$new_eleve] = "Absent"; // Statut par défaut
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // B. Changer le statut (Présent/Absent)
    if (isset($_GET['action']) && in_array($_GET['action'], ['present', 'absent']) && isset($_GET['eleve'])) {
        $eleve_a_modifier = $_GET['eleve'];
        $nouvel_etat = $_GET['action'];

        if (array_key_exists($eleve_a_modifier, $_SESSION[$SESSION_KEY_STATUS])) {
            $new_status_string = ($nouvel_etat == 'present') ? "Présent" : "Absent";
            $_SESSION[$SESSION_KEY_STATUS][$eleve_a_modifier] = $new_status_string;

            // --- NOUVEAU : Journalisation des absences ---
            $today = date('Y-m-d');
            // D'abord, on supprime l'entrée du jour pour cet élève pour éviter les doublons
            $_SESSION[$SESSION_KEY_ABSENCE_LOG] = array_filter($_SESSION[$SESSION_KEY_ABSENCE_LOG], function($log) use ($today, $eleve_a_modifier) {
                return !($log['date'] === $today && $log['student'] === $eleve_a_modifier);
            });
            // Si l'élève est absent, on ajoute une nouvelle entrée
            if ($new_status_string === 'Absent') {
                $_SESSION[$SESSION_KEY_ABSENCE_LOG][] = ['date' => $today, 'student' => $eleve_a_modifier];
            }
        }
        // On préserve la page actuelle lors de la redirection
        $page = isset($_GET['page']) ? '?page=' . $_GET['page'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . $page);
        exit;
    }

    // D. Expulser un élève
    if (isset($_GET['action']) && $_GET['action'] == 'kick' && isset($_GET['eleve'])) {
        $eleve_a_expulser = $_GET['eleve'];

        if (($key = array_search($eleve_a_expulser, $_SESSION[$SESSION_KEY_ELEVES])) !== false) {
            unset($_SESSION[$SESSION_KEY_ELEVES][$key]);
            $_SESSION[$SESSION_KEY_ELEVES] = array_values($_SESSION[$SESSION_KEY_ELEVES]);
        }
        unset($_SESSION[$SESSION_KEY_STATUS][$eleve_a_expulser]);
        unset($_SESSION[$SESSION_KEY_EMOJIS][$eleve_a_expulser]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // E. Action de réinitialisation
    if (isset($_GET['reset'])) {
        $_SESSION[$SESSION_KEY_STATUS] = array_fill_keys($_SESSION[$SESSION_KEY_ELEVES], "Absent");
        
        // Journaliser l'absence pour tout le monde pour aujourd'hui
        $today = date('Y-m-d');
        $_SESSION[$SESSION_KEY_ABSENCE_LOG] = array_filter($_SESSION[$SESSION_KEY_ABSENCE_LOG], fn($log) => $log['date'] !== $today);
        foreach ($_SESSION[$SESSION_KEY_ELEVES] as $eleve) {
            $_SESSION[$SESSION_KEY_ABSENCE_LOG][] = ['date' => $today, 'student' => $eleve];
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// C. Changer l'emoji (via AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'change_emoji' && isset($_GET['eleve']) && isset($_GET['emoji'])) {
    $eleve_to_change = $_GET['eleve'];
    $new_emoji = $_GET['emoji'];

    // Un étudiant ne peut changer que son propre emoji
    if ($user_role === 'student' && $eleve_to_change !== $current_user) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    if (in_array($eleve_to_change, $_SESSION[$SESSION_KEY_ELEVES]) && in_array($new_emoji, $available_emojis)) {
        $_SESSION[$SESSION_KEY_EMOJIS][$eleve_to_change] = $new_emoji;
        echo json_encode(['success' => true]); // Réponse pour JavaScript
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// --- PRÉPARATION POUR L'AFFICHAGE ---
$statuts = $_SESSION[$SESSION_KEY_STATUS];

if ($user_role === 'admin') {
    $total_eleves = count($_SESSION[$SESSION_KEY_ELEVES]);
    $total_pages = ceil($total_eleves / $ITEMS_PER_PAGE);
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $current_page = max(1, min($total_pages, $current_page));
    $offset = ($current_page - 1) * $ITEMS_PER_PAGE;
    $eleves_page = array_slice($_SESSION[$SESSION_KEY_ELEVES], $offset, $ITEMS_PER_PAGE);
} else { // Vue Étudiant
    // Calculer les absences de la semaine pour l'étudiant connecté
    $absences_this_week = 0;
    $start_of_week = strtotime('monday this week');
    foreach ($_SESSION[$SESSION_KEY_ABSENCE_LOG] as $log) {
        if ($log['student'] === $current_user && strtotime($log['date']) >= $start_of_week) {
            $absences_this_week++;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de la Présence | Édition Luxe</title>
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
            --absent-color-text: #ff8a8a;
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

        .container { max-width: 1200px; margin: 0 auto; }
        header { text-align: center; padding: 40px 20px; color: white; position: relative; }
        header h1 { font-family: 'Montserrat', sans-serif; font-size: 2.5rem; color: var(--primary-color); text-shadow: 0 2px 10px rgba(0,0,0,0.5); }

        .card {
            padding: 30px;
            background: var(--glass-bg);
            border-radius: 15px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

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
        .btn-kick {
            background-color: transparent;
            border: 1px solid #a02c2c;
            color: #a02c2c;
            padding: 8px 15px;
        }
        .btn-kick:hover {
            background-color: #a02c2c;
            color: white;
            box-shadow: 0 4px 10px rgba(160, 44, 44, 0.4);
        }
        
        .btn-reset { display: block; width: 200px; margin: 40px auto 10px; text-align: center; background: transparent; color: #aaa; padding: 12px; border-radius: 5px; text-decoration: none; font-weight: bold; border: 1px solid #555; transition: all 0.3s ease; }
        .btn-reset:hover { background: var(--primary-color); color: var(--secondary-color); border-color: var(--primary-color); box-shadow: 0 0 15px rgba(212, 175, 55, 0.5); }

        footer { text-align: center; padding: 30px; background-color: var(--secondary-color); color: #888; font-size: 0.9rem; margin-top: 40px; border-top: 1px solid var(--glass-border); }
        
        .main-nav { background: var(--glass-bg); border-bottom: 1px solid var(--glass-border); padding: 0 20px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 1000; }
        .main-nav ul { display: flex; justify-content: center; list-style: none; margin: 0; padding: 0; }
        .main-nav a { display: block; padding: 20px; color: var(--text-color); text-decoration: none; font-family: 'Montserrat', sans-serif; transition: all 0.3s ease; position: relative; }
        .main-nav a:hover, .main-nav a.active { color: var(--primary-color); }
        .main-nav a.active::after { content: ''; position: absolute; bottom: 0; left: 10px; right: 10px; height: 3px; background: var(--primary-color); border-radius: 3px; }
        .main-nav i { margin-right: 8px; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); display: none; justify-content: center; align-items: center; z-index: 2000; backdrop-filter: blur(5px); }
        .modal-content { background: var(--glass-bg); padding: 40px; border-radius: 15px; border: 1px solid var(--glass-border); text-align: center; position: relative; width: 90%; max-width: 400px; }
        .modal-close { position: absolute; top: 15px; right: 20px; color: #fff; font-size: 30px; font-weight: bold; cursor: pointer; transition: color 0.2s; }
        .modal-close:hover { color: var(--primary-color); }
        .modal-content h3 { font-family: 'Montserrat', sans-serif; color: var(--primary-color); font-size: 1.8rem; margin-top: 0; margin-bottom: 20px; text-transform: capitalize; }

        .photo-container { perspective: 1000px; }
        .emoji-3d { width: 250px; height: 250px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transition: transform 0.1s ease-out; border: 3px solid var(--primary-color); display: flex; justify-content: center; align-items: center; font-size: 150px; background: rgba(255, 255, 255, 0.05); }
        .eleve-name { cursor: pointer; transition: color 0.2s; }
        .eleve-name:hover { color: var(--primary-color); }

        /* --- Nouveaux Styles --- */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 30px; font-family: 'Montserrat', sans-serif; }
        .pagination a { color: var(--primary-color); text-decoration: none; padding: 8px 15px; border: 1px solid var(--primary-color); border-radius: 5px; transition: all 0.2s; }
        .pagination a:hover { background: var(--primary-color); color: var(--secondary-color); }
        .pagination span { color: var(--text-color); }

        .add-student-form { margin-bottom: 40px; }
        .add-student-form h2 { text-align: center; color: var(--primary-color); font-family: 'Montserrat', sans-serif; }
        .add-student-form form { display: flex; gap: 15px; justify-content: center; margin-top: 20px; }
        .add-student-form input { background: rgba(255,255,255,0.1); border: 1px solid var(--glass-border); padding: 12px; border-radius: 5px; color: white; font-size: 1rem; flex-grow: 1; max-width: 400px; }
        .add-student-form button { background: var(--primary-color); color: var(--secondary-color); border: none; padding: 12px 25px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: transform 0.2s; }
        .add-student-form button:hover { transform: scale(1.05); }

        #emoji-selector { margin-top: 20px; display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
        #emoji-selector .emoji-choice { font-size: 2.5rem; cursor: pointer; transition: transform 0.2s; }
        #emoji-selector .emoji-choice:hover { transform: scale(1.3); }
        .modal-content h4 {
            font-family: 'Montserrat', sans-serif;
            color: #aaa;
            font-weight: normal;
            font-size: 1rem;
            margin-top: 30px;
            border-top: 1px solid var(--glass-border);
            padding-top: 20px;
        }

        /* --- Styles Vue Étudiant --- */
        .student-profile { display: flex; align-items: center; justify-content: center; gap: 40px; text-align: left; }
        .student-emoji {
            font-size: 100px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .student-emoji:hover { transform: scale(1.1); }
        .student-info h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: var(--primary-color);
            margin: 0 0 10px 0;
            text-transform: capitalize;
        }
        .student-info p { margin: 5px 0; font-size: 1.1rem; }
        .absent-count { color: var(--absent-color-text); font-weight: bold; font-size: 1.2em; }


    </style>
</head>
<body>

    <nav class="main-nav">
        <ul>
            <li><a href="index03.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="presence.php" class="active"><i class="fas fa-user-check"></i> Présence</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>

    <div class="container">
        <header>
            <h1><i class="fas fa-user-check"></i> Appel des Élèves</h1>
        </header>

        <main>
            <?php if ($user_role === 'admin'): ?>
                <!-- VUE ADMINISTRATEUR -->
                <div class="card add-student-form">
                    <h2>Ajouter un nouvel élève</h2>
                    <form method="post" action="presence.php">
                        <input type="text" name="new_eleve_name" placeholder="Nom de l'élève" required autocomplete="off">
                        <button type="submit" name="add_eleve"><i class="fas fa-user-plus"></i> Ajouter</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Liste des Élèves</h2>
                    <table>
                        <thead>
                            <tr><th>Élève</th><th>Statut</th><th>Actions</th><th>Gérer</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves_page as $eleve): ?>
                                <?php
                                    $global_index = array_search($eleve, $_SESSION[$SESSION_KEY_ELEVES]);
                                    $current_emoji = $_SESSION[$SESSION_KEY_EMOJIS][$eleve] ?? $available_emojis[$global_index % count($available_emojis)];
                                ?>
                                <tr>
                                    <td class="eleve-name" data-eleve-name="<?php echo htmlspecialchars($eleve); ?>" data-current-emoji="<?php echo $current_emoji; ?>"><?php echo htmlspecialchars(ucfirst(trim($eleve))); ?></td>
                                    <td>
                                        <?php
                                            $statut = $statuts[$eleve];
                                            $class = ($statut == 'Présent') ? 'status-present' : 'status-absent';
                                            echo "<span class='status $class'>$statut</span>";
                                        ?>
                                    </td>
                                    <td class="actions">
                                        <a href="?eleve=<?php echo urlencode($eleve); ?>&action=present&page=<?php echo $current_page; ?>" class="btn-present">Présent</a>
                                        <a href="?eleve=<?php echo urlencode($eleve); ?>&action=absent&page=<?php echo $current_page; ?>" class="btn-absent">Absent</a>
                                    </td>
                                    <td class="actions">
                                        <a href="?eleve=<?php echo urlencode($eleve); ?>&action=kick" class="btn-kick" onclick="return confirm('Êtes-vous sûr de vouloir expulser cet élève ?\nCette action est irréversible.');"><i class="fas fa-user-slash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <a href="?reset=true" class="btn-reset">Réinitialiser la journée</a>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>"><i class="fas fa-arrow-left"></i> Précédent</a>
                    <?php endif; ?>
                    <span>Page <?php echo $current_page; ?> / <?php echo $total_pages; ?></span>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>">Suivant <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- VUE ÉTUDIANT -->
                <div class="card">
                    <?php
                        $global_index = array_search($current_user, $_SESSION[$SESSION_KEY_ELEVES]);
                        $current_emoji = $_SESSION[$SESSION_KEY_EMOJIS][$current_user] ?? $available_emojis[$global_index % count($available_emojis)];
                        $statut = $statuts[$current_user];
                        $class = ($statut == 'Présent') ? 'status-present' : 'status-absent';
                    ?>
                    <div class="student-profile">
                        <div class="student-emoji eleve-name" data-eleve-name="<?php echo htmlspecialchars($current_user); ?>" data-current-emoji="<?php echo $current_emoji; ?>">
                            <?php echo $current_emoji; ?>
                        </div>
                        <div class="student-info">
                            <h3><?php echo ucfirst($current_user); ?></h3>
                            <p>Statut aujourd'hui : <span class='status <?php echo $class; ?>'><?php echo $statut; ?></span></p>
                            <p>Absences cette semaine : <span class="absent-count"><?php echo $absences_this_week; ?></span></p>
                        </div>
                    </div>
                    <p style="text-align:center; margin-top: 30px; font-size: 0.9em; color: #aaa;">Cliquez sur votre icône pour la personnaliser.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal pour la photo 3D -->
    <div id="photoModal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3 id="modalEleveName"></h3>
            <div class="photo-container"><div id="modalEleveEmoji" class="emoji-3d"></div></div>
            <h4>Changer l'icône</h4>
            <div id="emoji-selector">
                <?php foreach($available_emojis as $emoji): ?>
                    <span class="emoji-choice"><?php echo $emoji; ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Système de Pointage. Tous droits réservés.</p>
    </footer>

    <script>
        // --- LOGIQUE DE LA MODALE EMOJI 3D ---
        const modal = document.getElementById('photoModal');
        const modalClose = document.querySelector('.modal-close');
        const modalEleveName = document.getElementById('modalEleveName');
        const modalEleveEmoji = document.getElementById('modalEleveEmoji');
        const photoContainer = document.querySelector('.photo-container');
        const studentNameCells = document.querySelectorAll('.eleve-name');

        studentNameCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const eleveName = cell.dataset.eleveName;
                const currentEmoji = cell.dataset.currentEmoji;
                
                modalEleveName.textContent = eleveName;
                modalEleveEmoji.textContent = currentEmoji;
                
                modal.style.display = 'flex';
            });
        });

        // Logique pour changer l'emoji
        const emojiChoices = document.querySelectorAll('.emoji-choice');
        emojiChoices.forEach(choice => {
            choice.addEventListener('click', () => {
                const newEmoji = choice.textContent;
                const eleveName = modalEleveName.textContent;

                // Appel AJAX pour sauvegarder le changement
                fetch(`presence.php?action=change_emoji&eleve=${encodeURIComponent(eleveName)}&emoji=${encodeURIComponent(newEmoji)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mettre à jour l'emoji dans la modale
                            modalEleveEmoji.textContent = newEmoji;
                            // Mettre à jour l'attribut data dans le tableau pour la prochaine ouverture
                            document.querySelector(`[data-eleve-name="${eleveName}"]`).dataset.currentEmoji = newEmoji;
                        }
                    });
            });
        });

        const closeModal = () => {
            modal.style.display = 'none';
            modalEleveEmoji.style.transform = 'rotateX(0) rotateY(0) scale(1)';
        };

        modalClose.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        photoContainer.addEventListener('mousemove', (e) => {
            const { width, height, left, top } = photoContainer.getBoundingClientRect();
            const x = e.clientX - left;
            const y = e.clientY - top;

            const rotateX = -((y - height / 2) / (height / 2)) * 15;
            const rotateY = ((x - width / 2) / (width / 2)) * 15;

            modalEleveEmoji.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.1)`;
        });

        photoContainer.addEventListener('mouseleave', () => {
            modalEleveEmoji.style.transform = 'rotateX(0) rotateY(0) scale(1)';
        });
    </script>

</body>
</html>
