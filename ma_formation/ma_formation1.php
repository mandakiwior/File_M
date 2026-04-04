<?php
    $recettes = [
        [
            'titre' => 'Cassoulet ',
            'recette' => 'Etape 1 : des flageolets !',
            'auteur' => 'mickael.audrieu@exemple.com',
            'estActive' => true,
        ],
        [
            'titre' => 'Couscous',
            'recette' => 'Etape 1 : de la semoule !',
            'auteur' => 'mickael.audrieu@exemple.com',
            'estActive' => false,
        ],
        [
            'titre' => 'Escalope milanaise',
            'recette' => 'Etape 1 : Prenez une belle escalope',
            'auteur' => 'mathieu.nebra@exemple.com',
            'estActive' => true,
        ],
    ];
    
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affichage des recettes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container"> 
        <h1>Affichage des recettes</h1>
        <!-- Boucke sur les recettes -->
         <?php foreach($recettes as $recette) : ?>
            <!-- Si la clé existe et a pour valeur "vrai", on affiche -->
             <?php if (array_key_exists('estActive', $recette)
                && $recette['estActive'] === true) : ?>
            <article>
                <h3><?php echo $recette['titre']; ?></h3>
                <div><?php echo $recette['recette']; ?></div>
                <i><?php echo $recette['auteur']; ?></i>
            </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
</body>
</html>