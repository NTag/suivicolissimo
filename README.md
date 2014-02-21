suivicolissimo
==============

Ce script PHP vous permet de suivre vos colis Colissimo.

## Installation
_Attention_ :
- Le dossier `letters` doit être dans le même dossier que `colissimo.class.php` ;
- Créez un dossier `colis` dans le même dossier que `colissimo.class.php` ;
- `chmod -R 777 letters` ;
- `chmod -R 777 colis`.

## Utilisation
```php
<?php
require('colissimo.class.php');
$colis = new suiviColissimo('IDDUCOLIS');
$infosSuivi = $colis->getSuivi();
```

Le fichier `index.php` est un exemple d'utilisation.

## Fonctionnement
Le site de Colissimo utilise des images pour indiquer les différentes étapes du suivi du colis. Voici comment fonctionne le script :

1. Il télécharge toutes les images de suivi (et les enregistre dans le dossier `colis/IDDUCOLIS/`) ;
2. Il prend chaque image ;
3. Il découpe celle-ci en lettres (il distingue les lettres en cherchant les espaces) (et les enregistre dans le dossier `colis/IDDUCOLIS/letters/`) ;
4. Il calcule le hash md5 de chaque image contenue dans le dossier `colis/IDDUCOLIS/letters/` ;
5. Il regarde si un fichier dont le nom est le hash md5 existe dans le dossier `letters/ref/`. Si oui alors ce fichier contient la lettre correspondant à l'image. Sinon il enregistre un fichier `.png` dans le dossier `letters/ref/` dont le nom est le hash md5 plus l'extension png.

Il faut donc regarder régulièrement le dossier `letters/ref` pour voir s'il y a des nouvelles lettres non identifiées. Pour simplifier ce travail il suffit d'accéder au fichier `upref.php` qui affiche les images non identifiées et permet d'indiquer à quelle lettre elles correspondent.

Heureusement pour vous en utilisant 8 000 numéros de colis j'ai pu créer une bibliothèque très complète de lettres.
