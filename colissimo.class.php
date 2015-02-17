<?php

/**
 * Retourne les informations de suivi
 * d'un colis Colissimo
 * @class suiviColissimo
 */
class suiviColissimo
{
    /**
     * @var string Le numéro du colis
     */
    private $id;

    /**
    * @var string Le dossier où stocker les images
    * des lettres
    */
    private $libLetters;

    /**
    * @var string Le dossier où enregistrer
    * les images des colis
    */
    private $dirImg;

    public function __construct($id)
    {
        if (!preg_match('#^[0-9a-zA-Z]+$#', $id)) {
            throw new Exception("Numéro invalide");
        }
        $this->id = $id;

        $this->libLetters = __DIR__ . '/letters/ref/';
        $this->dirImg = __DIR__ . '/colis/';
    }

    /**
    * Retourne un tableau contenant les informations
    * de suivi du colis
    * @return array(
    * array('date' => '01/01/2014',
    * 'message' => 'Colis pris en charge par la poste',
    * 'lieu' => 'Plate-Forme Est'
    * ),
    * array( ...
    * ),
    * ...
    * )
    */
    public function getSuivi()
    {
        // Création des dossiers
        $dir = $this->dirImg . $this->id;
        $letters = $dir . '/letters';
        if (is_dir($letters)) {
            exec('rm -rf ' . $dir);
        }
        mkdir($dir);
        mkdir($letters);

        // Téléchargement de  la page
        $getIndex = curl_init();
        curl_setopt($getIndex, CURLOPT_URL, "http://www.colissimo.fr/portail_colissimo/suivre.do?colispart=" . $this->id);
        curl_setopt($getIndex, CURLOPT_HEADER, true);
        curl_setopt($getIndex, CURLOPT_RETURNTRANSFER, true);
        $pageIndex = curl_exec($getIndex);
        curl_close($getIndex);

        // Extraction du cookie
        $cookie = preg_replace('#^.+Set-Cookie: (.+) Path=.+$#isU', '$1', $pageIndex);

	$pageIndex = preg_replace('#<div id="horaires2".+<div class="bottom">#isU', '', $pageIndex);
        $table = preg_replace('#^.+<table class="dataArray" summary="Suivi de votre colis" width="100%">(.+)</table>.+$#isU', '$1', $pageIndex);
        $table = preg_replace('#^.+<tbody>(.+)</tbody>.+$#isU', '$1', $table);

        preg_match_all('#<tr (.+)</tr>#isU', $table, $stapes);
        $stapes = $stapes[0];
        $s = 0;
        $finalText = array();
        $nameInfos = array(
            1 => 'date',
            2 => 'message',
            3 => 'lieu',
        );
        foreach ($stapes as $stape) {
            $s++;
            $d = 0;
            preg_match_all('#<td headers="(.+)</td>#isU', $stape, $details);
            $details = $details[0];
            $infosDetails = array();
            foreach ($details as $detail) {
                $d++;
                if (preg_match('#<img (style="cursor:pointer" )?src#isU', $detail)) {
                    $img = preg_replace("#^.+<img (style=\"cursor:pointer\" )?src='(.+)'.+$#isU", '$2', $detail);
                    $getImg = curl_init();
                    curl_setopt($getImg, CURLOPT_URL, "http://www.colissimo.fr/portail_colissimo/" . $img);
                    curl_setopt($getImg, CURLOPT_COOKIE, $cookie);
                    curl_setopt($getImg, CURLOPT_RETURNTRANSFER, true);
                    $pageImg = curl_exec($getImg);
                    curl_close($getImg);

                    $pathImg = $dir . '/stape_' . $s . '_' . $d . '.png';
                    file_put_contents($pathImg, $pageImg);

                    $text = utf8_encode($this->readImg($pathImg, $letters));
                    if ($d == 1) {
                        $text = str_replace(' ', '', $text);
                    }
                    // Petit bug avec les .
                    $text = preg_replace('#\.([A-Z])#', '. $1', $text);
                    $text = str_replace('Ilest', 'Il est', $text);
                } else {
                    $text = utf8_encode(trim($detail));
                }
                $infosDetails[$nameInfos[$d]] = $text;
            }
            $finalText[] = $infosDetails;
        }

        return $finalText;
    }

    /**
    * Retourne le texte contenu dans
    * une image
    * $img L'image GD
    * $letters string Le dossier où enregistrer
    * les lettres extraites de l'image
    */
    private function readImg($img, $letters)
    {
        list($width, $height, $type, $attr) = getimagesize($img);

        $in = imagecreatefrompng($img);
        $out = @imagecreate($width, $height)
        or die("Impossible d'initialiser la bibliothèque GD");
        $white = imagecolorallocate($out, 0, 0, 0);
        $black = imagecolorallocate($out, 255, 255, 255);

        $l = 205;

        // "Améliore" ou rend plus simple l'image
        imagefilter($in, IMG_FILTER_MEAN_REMOVAL);
        imagefilter($in, IMG_FILTER_CONTRAST, -10);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($in, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r > $l and $g > $l and $b > $l) {
                    imagesetpixel($out, $x, $y, $black);
                }

            }
        }

        // Analyses des lettres
        $nbLines = ceil($height/13);
        // 13 pixels correspond à la hauteur d'une ligne

        $imgLetters = array();
        $nbLetter = 0;
        for ($i = 1; $i <= $nbLines; $i++) {
            // On commence pas par une lettre
            $inLetter = false;
            $x0 = 0;
            for ($x = 0; $x < $width; $x++) {
                if ($inLetter) {
                    if (!$this->verifVertical($out, $x, 13*($i-1), 13*$i)) {
                        $inLetter = false;
                        $letter = imagecreatetruecolor(($x - $x0), 13);
                        imagecopy($letter, $out, 0, 0, $x0, 13*($i-1), ($x - $x0), 13);
                        $imgLetters[$nbLetter] = $letter;
                        $nbLetter++;
                        $x0 = $x;
                    }
                } else {
                    if ($this->verifVertical($out, $x, 13*($i-1), 13*$i)) {
                        if (($x - $x0) >= 4) {
                            $imgLetters[$nbLetter] = ' ';
                            $nbLetter++;
                        }
                        $inLetter = true;
                        $x0 = $x;
                    }
                }
            }

            $imgLetters[$nbLetter] = ' ';
            $nbLetter++;
        }

        $outText = '';
        foreach ($imgLetters as $k => $letter) {
            if ($letter === ' ') {
                $outText .= ' ';
                continue;
            }
            $file = $letters . '/' . $k . '.png';
            imagepng($letter, $file);

            $hashl = md5_file($file);
            if (file_exists($this->libLetters . $hashl)) {
                $outText .= file_get_contents($this->libLetters . $hashl);
            } else {
                copy($file, $this->libLetters . $hashl . '.png');
            }
        }

        // Le i majuscule et le L minuscule sont identiques dans la police de la Poste...
        $outText = preg_replace('#^ll #', 'Il ', $outText);
        $outText = preg_replace('# ll #', ' Il ', $outText);
        $outText = trim($outText);
        return $outText;
    }
    /**
    * Vérifie si une ligne verticale
    * comprise entre $y0 et $y1
    * contient au moins un pixel noir
    * @param $img L'image GD
    * @param $x L'abscisse x
    * @param $y0 L'ordonnée inférieure
    * @param $y1 L'ordonnée supérieure
    * @return boolean
    */
    private function verifVertical($img, $x, $y0, $y1) {
        for ($y = $y0; $y < $y1; $y++) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            if ($b == 0) {
                return true;
            }
        }
        return false;
    }
}
