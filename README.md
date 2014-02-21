suivicolissimo
==============

Ce script PHP vous permet de suivre vos colis Colissimo.

# Utilisation
```php
<?php
require('colissimo.class.php');
$colis = new suiviColissimo('IDDUCOLIS');
$infosSuivi = $colis->getSuivi();
```

Le fichier `index.php` est un exemple d'utilisation.
