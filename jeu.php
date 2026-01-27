<?php
session_start();

// --- LOGIQUE JEU DE COURSE (PHP RACER) ---

// 1. Reset / Nouvelle partie
if (isset($_POST['reset'])) {
    unset($_SESSION['race']); // On garde la session pour le High Score
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 2. Initialisation
if (!isset($_SESSION['race'])) {
    $_SESSION['race'] = [
        'lane' => 2,       // Voie du joueur (0 à 4)
        'speed' => 0,      // Vitesse en km/h
        'distance' => 0,   // Distance parcourue
        'road' => [],      // La route (tableau des obstacles)
        'game_over' => false,
        'msg' => "Moteur prêt. Appuyez sur ACCÉLÉRER !"
    ];
    
    // Initialisation du High Score si inexistant
    if (!isset($_SESSION['high_score'])) $_SESSION['high_score'] = 0;

    // On génère une route vide de 20 segments pour commencer
    for ($i = 0; $i < 20; $i++) {
        $_SESSION['race']['road'][] = 0; // 0 = vide
    }
}

$r = &$_SESSION['race']; // Raccourci

// 3. Traitement des actions
if (isset($_POST['action']) && !$r['game_over']) {
    $act = $_POST['action'];
    
    // Direction (Gauche / Droite)
    if ($act == 'left' && $r['lane'] > 0) $r['lane']--;
    if ($act == 'right' && $r['lane'] < 4) $r['lane']++;
    
    // Vitesse (Accélérer / Freiner)
    if ($act == 'accel') $r['speed'] = min(250, $r['speed'] + 30);
    if ($act == 'brake') $r['speed'] = max(0, $r['speed'] - 50);
    
    // Friction naturelle (la voiture ralentit si on ne fait rien)
    if ($act == 'left' || $act == 'right') $r['speed'] = max(0, $r['speed'] - 10);

    // Moteur physique (Simulation de l'avancement)
    if ($r['speed'] > 0) {
        // Plus on va vite, plus on avance de "cases" par tour
        $steps = floor($r['speed'] / 40) + 1; 
        
        for ($s = 0; $s < $steps; $s++) {
            $r['distance'] += 0.1;
            
            // Faire avancer la route (on enlève le bas, on ajoute en haut)
            array_pop($r['road']);
            
            // Génération de trafic (30% de chance d'avoir une voiture)
            if (rand(1, 100) <= 30) {
                // On place une voiture sur une voie aléatoire (1 à 5)
                array_unshift($r['road'], rand(1, 5)); 
            } else {
                array_unshift($r['road'], 0);
            }
            
            // Détection de collision
            // Le joueur est à l'index 18 (proche du bas de l'écran)
            // Si la voie de l'obstacle == voie du joueur + 1
            if ($r['road'][18] == ($r['lane'] + 1)) {
                $r['game_over'] = true;
                $r['speed'] = 0;
                // Check High Score
                if ($r['distance'] > $_SESSION['high_score']) {
                    $_SESSION['high_score'] = $r['distance'];
                }
                $r['msg'] = "💥 CRASH ! Accident à " . number_format($r['distance'], 1) . " km.";
                break; // On arrête la boucle de mouvement
            }
        }
    }
    
    if (!$r['game_over'] && $r['speed'] > 0) {
        $r['msg'] = "Vitesse : " . $r['speed'] . " km/h | Distance : " . number_format($r['distance'], 1) . " km";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PHP Street Racer</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');

        body { 
            font-family: 'Press Start 2P', cursive; 
            background: #120024;
            color: white; text-align: center; margin: 0; height: 100vh; overflow: hidden;
        }
        
        /* --- MOTEUR GRAPHIQUE CSS --- */
        .scene {
            perspective: 300px; /* Perspective forte pour immersion */
            width: 100%;
            height: 100%;
            position: relative;
            background: linear-gradient(#2b0642 50%, #7600bc 50%, #ff00d4 100%); /* Synthwave Sky */
            overflow: hidden;
        }
        
        /* Soleil Retro */
        .sun {
            position: absolute; top: 10%; left: 50%; transform: translateX(-50%);
            width: 200px; height: 200px;
            background: linear-gradient(to bottom, #ffeb3b, #ff00d4);
            border-radius: 50%;
            box-shadow: 0 0 40px #ff00d4;
            z-index: 0;
        }

        .road-container {
            width: 800px;
            height: 1000px;
            position: absolute;
            left: 50%;
            bottom: -100px;
            transform-origin: 50% 100%; /* On pivote depuis le bas (le joueur) */
            transform: translateX(-50%) rotateX(80deg); /* Route vers l'horizon */
            transform-style: preserve-3d;
            background: #000;
            /* Effet grille sur la route */
            background-image: linear-gradient(transparent 95%, #ff00d4 95%), linear-gradient(90deg, transparent 95%, #ff00d4 95%);
            background-size: 50px 50px;
            box-shadow: 0 0 50px #ff00d4;
            border-left: 5px solid #ff00d4;
            border-right: 5px solid #ff00d4;
        }
        
        /* Lignes de la route */
        .lane-marker {
            position: absolute; top: 0; bottom: 0; width: 4px; 
            background: repeating-linear-gradient(to bottom, #0ff 0, #0ff 40px, transparent 40px, transparent 80px);
            box-shadow: 0 0 10px #0ff;
        }
        
        /* Les objets sur la route */
        .object {
            width: 20%; height: 0;
            position: absolute; display: flex; justify-content: center; align-items: flex-end;
            font-size: 60px;
            transform-style: preserve-3d;
            transform: rotateX(-80deg) translateY(-30px); /* Sprite debout face caméra */
            text-shadow: 0 0 10px #ff0000;
            filter: drop-shadow(0 10px 10px rgba(0,0,0,0.8));
        }
        
        /* Tableau de bord */
        .dashboard {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: rgba(0, 0, 0, 0.8); padding: 15px;
            border-top: 2px solid #0ff;
            box-shadow: 0 -5px 20px #0ff;
            display: flex; justify-content: space-around; align-items: center;
            z-index: 100;
            backdrop-filter: blur(5px);
        }
        
        .gauge {
            border: 2px solid #0ff; border-radius: 50%; width: 100px; height: 100px;
            display: flex; flex-direction: column; justify-content: center;
            background: #000; color: #0ff; 
            box-shadow: 0 0 15px #0ff inset;
            text-shadow: 0 0 5px #0ff;
        }
        
        button {
            padding: 15px 20px; font-size: 20px; font-weight: bold; font-family: inherit;
            border: 2px solid #ff00d4; border-radius: 5px; cursor: pointer;
            background: transparent; color: #ff00d4;
            text-shadow: 0 0 5px #ff00d4;
            box-shadow: 0 0 10px #ff00d4;
            transition: all 0.1s;
        }
        button:active { transform: scale(0.95); background: #ff00d4; color: white; }
        button:hover { background: rgba(255, 0, 212, 0.2); }
        
        .btn-gas { border-color: #0f0; color: #0f0; box-shadow: 0 0 10px #0f0; text-shadow: 0 0 5px #0f0; width: 180px; }
        .btn-gas:hover { background: rgba(0, 255, 0, 0.2); }
        .btn-brake { border-color: #f00; color: #f00; box-shadow: 0 0 10px #f00; text-shadow: 0 0 5px #f00; }
        .btn-brake:hover { background: rgba(255, 0, 0, 0.2); }
        
        .msg { position: absolute; top: 20px; width: 100%; font-size: 20px; text-shadow: 0 0 10px #fff; z-index: 200; color: #fff; }
        .high-score { position: absolute; top: 50px; right: 20px; font-size: 14px; color: #ffeb3b; text-shadow: 0 0 5px #ffeb3b; z-index: 200; text-align: right; }
        
        /* Animation de secousse pour le crash */
        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }
        .shake { animation: shake 0.5s; animation-iteration-count: 1; }
    </style>
</head>
<body>

    <div class="high-score">
        HIGH SCORE<br>
        <?php echo number_format($_SESSION['high_score'] ?? 0, 1); ?> KM
    </div>

    <div class="msg"><?php echo $_SESSION['race']['msg']; ?></div>

    <div class="scene">
        <div class="sun"></div>
        <div class="road-container">
            <!-- Marquage au sol -->
            <div class="lane-marker" style="left: 20%;"></div>
            <div class="lane-marker" style="left: 40%;"></div>
            <div class="lane-marker" style="left: 60%;"></div>
            <div class="lane-marker" style="left: 80%;"></div>

            <?php
            // Rendu de la route (Objets)
            // On parcourt le tableau 'road'. Index 0 = Loin, Index 19 = Proche.
            foreach ($_SESSION['race']['road'] as $rowIndex => $content) {
                // Calcul de la position Y (en pourcentage ou pixels)
                // 20 lignes. On espace plus pour couvrir la longue route
                $topPos = $rowIndex * 50; 
                
                // Si c'est une voiture ennemie (1 à 5)
                if ($content > 0) {
                    $leftPos = ($content - 1) * 20; // 0%, 20%, 40%...
                    echo "<div class='object' style='top: {$topPos}px; left: {$leftPos}%;'>🚘</div>";
                }
                
                // Si c'est la ligne du joueur (index 18)
                if ($rowIndex == 18) {
                    $playerLeft = $_SESSION['race']['lane'] * 20;
                    echo "<div class='object' style='top: {$topPos}px; left: {$playerLeft}%; z-index: 10; font-size:80px; filter: drop-shadow(0 0 20px #0ff);'>🏎️</div>";
                }
            }
            ?>
        </div>
    </div>

    <!-- Tableau de bord / Contrôles -->
    <form method="post" class="dashboard">
        <?php if (!$_SESSION['race']['game_over']): ?>
            <div class="gauge">
                <span style="font-size:24px"><?php echo $_SESSION['race']['speed']; ?></span>
                <span style="font-size:10px">KM/H</span>
            </div>
            
            <button type="submit" name="action" value="left" class="btn-left">⬅️</button>
            
            <div style="display:flex; flex-direction:column; gap:10px;">
                <button type="submit" name="action" value="accel" class="btn-gas">TURBO</button>
                <button type="submit" name="action" value="brake" class="btn-brake">FREIN</button>
            </div>
            
            <button type="submit" name="action" value="right" class="btn-right">➡️</button>
            
            <div class="gauge">
                <span style="font-size:24px"><?php echo floor($_SESSION['race']['distance']); ?></span>
                <span style="font-size:10px">DIST.</span>
            </div>
        <?php else: ?>
            <button type="submit" name="reset" style="background:#f00; color:white; width:100%; border: 2px solid white; box-shadow: 0 0 20px #f00;">GAME OVER - REJOUER</button>
        <?php endif; ?>
    </form>

    <script>
        // --- SYSTÈME AUDIO JAVASCRIPT (Synthétiseur) ---
        // On utilise l'API Web Audio pour générer des sons sans fichiers mp3
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioCtx = new AudioContext();

        function playSound(type) {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            const now = audioCtx.currentTime;
            
            if (type === 'engine') {
                // Son de moteur (dent de scie)
                osc.type = 'sawtooth';
                // La fréquence dépend de la vitesse PHP injectée
                osc.frequency.setValueAtTime(50 + (<?php echo $_SESSION['race']['speed']; ?> * 2), now);
                gain.gain.setValueAtTime(0.05, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc.start(now);
                osc.stop(now + 0.3);
            } else if (type === 'crash') {
                // Son de crash (onde carrée distordue)
                osc.type = 'square';
                osc.frequency.setValueAtTime(150, now);
                osc.frequency.exponentialRampToValueAtTime(10, now + 0.5);
                gain.gain.setValueAtTime(0.3, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                osc.start(now);
                osc.stop(now + 0.5);
            }
        }

        // Déclenchement des effets au chargement de la page
        window.addEventListener('load', () => {
            const isCrash = <?php echo $_SESSION['race']['game_over'] ? 'true' : 'false'; ?>;
            const speed = <?php echo $_SESSION['race']['speed']; ?>;

            if (isCrash) {
                document.body.classList.add('shake');
                playSound('crash');
            } else if (speed > 0) {
                playSound('engine');
            }
        });

        // Script pour contrôler le jeu avec le clavier (plus réactif que la souris)
        document.addEventListener('keydown', function(e) {
            // On empêche le défilement de la page avec les flèches
            if(["ArrowUp","ArrowDown","ArrowLeft","ArrowRight"].includes(e.key)) {
                e.preventDefault();
            }

            const click = (sel) => { let b = document.querySelector(sel); if(b) b.click(); };

            if (e.key === "ArrowLeft") click('.btn-left');
            if (e.key === "ArrowRight") click('.btn-right');
            if (e.key === "ArrowUp") click('.btn-gas');
            if (e.key === "ArrowDown") click('.btn-brake');
        });
    </script>
</body>
</html>