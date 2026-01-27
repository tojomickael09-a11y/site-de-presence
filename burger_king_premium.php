<?php
session_start();

// --- 1. BACKEND : DONNÉES ET LOGIQUE ---

// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Base de données simulée (Tableaux associatifs)
$menu = [
    'burgers' => [
        1 => ['name' => 'Le Smoky King', 'price' => 12.50, 'desc' => 'Double bœuf Angus, cheddar affiné, sauce barbecue fumée, oignons caramélisés.', 'img' => '🍔'],
        2 => ['name' => 'Truffle Deluxe', 'price' => 14.00, 'desc' => 'Steak haché minute, sauce à la truffe noire, roquette, parmesan.', 'img' => '🥓'],
        3 => ['name' => 'Spicy Chicken', 'price' => 11.90, 'desc' => 'Poulet pané maison, jalapeños, sauce sriracha-mayo, salade iceberg.', 'img' => '🍗'],
    ],
    'sides' => [
        4 => ['name' => 'Frites Rustiques', 'price' => 4.50, 'desc' => 'Pommes de terre coupées au couteau, double cuisson.', 'img' => '🍟'],
        5 => ['name' => 'Onion Rings', 'price' => 5.00, 'desc' => 'Oignons doux frits dans une pâte à la bière.', 'img' => '🧅'],
    ],
    'drinks' => [
        6 => ['name' => 'Cola Craft', 'price' => 3.50, 'desc' => 'Cola artisanal aux épices naturelles.', 'img' => '🥤'],
        7 => ['name' => 'Limonade Maison', 'price' => 4.00, 'desc' => 'Citrons pressés, menthe fraîche, peu sucrée.', 'img' => '🍋'],
    ]
];

// Traitement des actions (Ajout / Reset)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_id'])) {
        $id = $_POST['add_id'];
        $category = $_POST['category'];
        
        // On vérifie si le produit existe
        if (isset($menu[$category][$id])) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty']++;
            } else {
                $_SESSION['cart'][$id] = [
                    'name' => $menu[$category][$id]['name'],
                    'price' => $menu[$category][$id]['price'],
                    'qty' => 1
                ];
            }
        }
    }
    
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Redirection pour éviter la resoumission du formulaire (Pattern PRG)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Calcul du total
$total = 0;
$countItems = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['qty'];
    $countItems += $item['qty'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KING BURGER | Premium Fast Food</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* --- 2. FRONTEND : STYLE PREMIUM (CSS) --- */
        :root {
            --primary: #D62300; /* Flame Orange */
            --dark: #121212;
            --dark-card: #1E1E1E;
            --light: #F4F4F4;
            --gray: #AAAAAA;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }

        h1, h2, h3 { font-family: 'Oswald', sans-serif; text-transform: uppercase; }

        /* Header */
        header {
            background: rgba(18, 18, 18, 0.95);
            padding: 20px 40px;
            position: sticky; top: 0; z-index: 100;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #333;
            backdrop-filter: blur(10px);
        }

        .logo { font-size: 24px; font-weight: bold; color: var(--primary); letter-spacing: 2px; }
        .logo span { color: white; }

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-weight: bold;
        }
        .badge {
            background: var(--primary); color: white;
            border-radius: 50%; padding: 2px 8px; font-size: 12px;
            position: absolute; top: -10px; right: -15px;
        }

        /* Hero Section */
        .hero {
            height: 60vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1586190848861-99c9574548e3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            text-align: center;
        }
        .hero h1 { font-size: 4rem; margin-bottom: 10px; text-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .hero p { font-size: 1.5rem; color: var(--gray); }
        .btn-cta {
            margin-top: 20px; padding: 15px 40px;
            background: var(--primary); color: white;
            text-decoration: none; font-weight: bold; border-radius: 50px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(214, 35, 0, 0.4); }

        /* Menu Grid */
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        .section-title {
            font-size: 2.5rem; margin: 40px 0 20px;
            border-left: 5px solid var(--primary); padding-left: 15px;
        }

        .grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;
        }

        .card {
            background: var(--dark-card);
            border-radius: 15px; overflow: hidden;
            transition: transform 0.3s;
            border: 1px solid #333;
            display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-5px); border-color: var(--primary); }

        .card-img {
            height: 200px; background: #2a2a2a;
            display: flex; justify-content: center; align-items: center;
            font-size: 80px;
        }

        .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-title { font-size: 1.4rem; margin-bottom: 10px; display: flex; justify-content: space-between; }
        .price { color: var(--primary); }
        .card-desc { color: var(--gray); font-size: 0.9rem; margin-bottom: 20px; flex-grow: 1; }

        .btn-add {
            width: 100%; padding: 12px;
            background: transparent; border: 2px solid var(--light); color: var(--light);
            font-weight: bold; cursor: pointer; transition: 0.3s;
            text-transform: uppercase; font-family: 'Oswald', sans-serif;
        }
        .btn-add:hover { background: var(--light); color: var(--dark); }

        /* Cart Sidebar (Simple version embedded) */
        .cart-panel {
            position: fixed; bottom: 20px; right: 20px;
            background: var(--light); color: var(--dark);
            width: 350px; padding: 20px; border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 200;
            display: <?php echo empty($_SESSION['cart']) ? 'none' : 'block'; ?>;
            animation: slideIn 0.5s;
        }

        @keyframes slideIn { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .cart-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--dark); padding-bottom: 10px; margin-bottom: 10px; }
        .cart-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
        .cart-total { font-size: 1.2rem; font-weight: bold; margin-top: 15px; text-align: right; color: var(--primary); }
        
        .btn-clear {
            background: #333; color: white; border: none; padding: 5px 10px; font-size: 0.8rem; cursor: pointer;
        }
        .btn-checkout {
            display: block; width: 100%; background: var(--primary); color: white;
            text-align: center; padding: 15px; margin-top: 15px; text-decoration: none; font-weight: bold;
        }

        footer { text-align: center; padding: 40px; color: var(--gray); font-size: 0.8rem; margin-top: 50px; border-top: 1px solid #333; }
    </style>
</head>
<body>

    <!-- Navigation -->
    <header>
        <div class="logo">KING <span>BURGER</span></div>
        <div class="cart-icon">
            PANIER
            <?php if($countItems > 0): ?>
                <span class="badge"><?php echo $countItems; ?></span>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <h1>Le Goût du Feu</h1>
        <p>Ingrédients premium. Cuisson flamme. Saveur légendaire.</p>
        <a href="#menu" class="btn-cta">COMMANDER MAINTENANT</a>
    </section>

    <!-- Menu -->
    <div class="container" id="menu">
        
        <!-- Burgers -->
        <h2 class="section-title">Nos Burgers Signature</h2>
        <div class="grid">
            <?php foreach($menu['burgers'] as $id => $item): ?>
            <form method="POST" class="card">
                <div class="card-img"><?php echo $item['img']; ?></div>
                <div class="card-body">
                    <div class="card-title">
                        <?php echo $item['name']; ?>
                        <span class="price"><?php echo number_format($item['price'], 2); ?>€</span>
                    </div>
                    <p class="card-desc"><?php echo $item['desc']; ?></p>
                    <input type="hidden" name="add_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="category" value="burgers">
                    <button type="submit" class="btn-add">Ajouter au panier</button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>

        <!-- Sides -->
        <h2 class="section-title">Accompagnements</h2>
        <div class="grid">
            <?php foreach($menu['sides'] as $id => $item): ?>
            <form method="POST" class="card">
                <div class="card-img" style="height: 150px; font-size: 60px;"><?php echo $item['img']; ?></div>
                <div class="card-body">
                    <div class="card-title">
                        <?php echo $item['name']; ?>
                        <span class="price"><?php echo number_format($item['price'], 2); ?>€</span>
                    </div>
                    <p class="card-desc"><?php echo $item['desc']; ?></p>
                    <input type="hidden" name="add_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="category" value="sides">
                    <button type="submit" class="btn-add">Ajouter</button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>

        <!-- Drinks -->
        <h2 class="section-title">Boissons Fraîches</h2>
        <div class="grid">
            <?php foreach($menu['drinks'] as $id => $item): ?>
            <form method="POST" class="card">
                <div class="card-img" style="height: 150px; font-size: 60px;"><?php echo $item['img']; ?></div>
                <div class="card-body">
                    <div class="card-title">
                        <?php echo $item['name']; ?>
                        <span class="price"><?php echo number_format($item['price'], 2); ?>€</span>
                    </div>
                    <p class="card-desc"><?php echo $item['desc']; ?></p>
                    <input type="hidden" name="add_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="category" value="drinks">
                    <button type="submit" class="btn-add">Ajouter</button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Panier Flottant -->
    <?php if (!empty($_SESSION['cart'])): ?>
    <div class="cart-panel">
        <div class="cart-header">
            <h3>Votre Commande</h3>
            <form method="POST" style="display:inline;">
                <button type="submit" name="clear_cart" class="btn-clear">Vider</button>
            </form>
        </div>
        
        <?php foreach ($_SESSION['cart'] as $item): ?>
            <div class="cart-item">
                <span><?php echo $item['qty']; ?>x <?php echo $item['name']; ?></span>
                <span><?php echo number_format($item['price'] * $item['qty'], 2); ?>€</span>
            </div>
        <?php endforeach; ?>

        <div class="cart-total">
            TOTAL : <?php echo number_format($total, 2); ?>€
        </div>
        <a href="#" class="btn-checkout" onclick="alert('Merci pour votre commande ! La cuisine prépare votre repas.');">VALIDER LA COMMANDE</a>
    </div>
    <?php endif; ?>

    <footer>
        &copy; <?php echo date('Y'); ?> KING BURGER. Fait avec 🔥 et du PHP.
    </footer>

</body>
</html>