<?php
// 1. LOGIQUE DE CALCUL (À mettre au début)
$resultat = "";
$n1 = "";
$n2 = "";

if (isset($_POST['calculer'])) {
    $n1 = $_POST['n1'];
    $n2 = $_POST['n2'];
    $op = $_POST['op'];

    if (is_numeric($n1) && is_numeric($n2)) {
        if ($op == "+") {
            $resultat = $n1 + $n2;
        } elseif ($op == "-") {
            $resultat = $n1 - $n2;
        }
    } else {
        $resultat = "Erreur : Entrez des nombres";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Calculatrice en ligne</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* Styles rapides pour correspondre à votre écran */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        
        /* Style du menu (Barre bleue sur votre photo) */
        .navbar {
            background-color: #2c3e50;
            overflow: hidden;
            display: flex;
            padding: 10px 50px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
        }
        .navbar a:hover { background-color: #34495e; }

        /* Style de la calculatrice */
        .calc-container {
            text-align: center;
            margin-top: 40px;
        }
        .calc-box {
            display: inline-block;
            border: 2px solid #000;
            padding: 30px;
            border-radius: 5px;
            text-align: left;
        }
        .field { margin-bottom: 15px; }
        label { display: inline-block; width: 100px; font-weight: bold; }
        input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .buttons { margin-top: 20px; }
        button { 
            padding: 10px 20px; 
            cursor: pointer; 
            background-color: #eee; 
            border: 1px solid #999;
            font-weight: bold;
        }
        button:hover { background-color: #ddd; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="#">Home</a>
        <a href="#">News</a>
        <a href="#">Contact</a>
        <a href="#">About</a>
    </div>

    <div class="calc-container">
        <h1><u>Calculatrice en ligne</u></h1>

        <div class="calc-box">
            <form method="post" action="">
                <div class="field">
                    <label>Nombre 1 :</label>
                    <input type="number" step="any" name="n1" value="<?php echo $n1; ?>" required>
                </div>

                <div class="field">
                    <label>Nombre 2 :</label>
                    <input type="number" step="any" name="n2" value="<?php echo $n2; ?>" required>
                </div>

                <div class="field">
                    <label>Résultat :</label>
                    <input type="text" value="<?php echo $resultat; ?>" readonly style="background-color: #f9f9f9;">
                </div>

                <div class="buttons">
                    <span>Choisissez : </span>
                    <button type="submit" name="op" value="+">Addition</button>
                    <button type="submit" name="op" value="-">Soustraction</button>
                    <input type="hidden" name="calculer" value="1">
                </div>
            </form>
        </div>
    </div>

</body>
</html>