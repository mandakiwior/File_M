<?php
  // On définit une variable en PHP
  $nom_utilisateur = "Apprenti Développeur";
  $date_du_jour = date("d/m/Y");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ma première page dynamique</title>
</head>
<body>
    <h1>Bienvenue, <?php echo $nom_utilisateur; ?> !</h1>
    <p>Aujourd'hui, nous sommes le <strong><?php echo $date_du_jour; ?></strong>.</p>
    
    <?php
      if (date("H") < 18) {
          echo "<p>Passe une excellente journée de code !</p>";
      } else {
          echo "<p>Bonne soirée de programmation !</p>";
      }
    ?>
</body>
</html>