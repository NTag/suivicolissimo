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

    public function __construct($id)
    {
        if (!preg_match('#^[0-9a-zA-Z]+$#', $id)) {
            throw new Exception("Numéro invalide");
        }
        $this->id = $id;
    }

    /**
     * Retourne un tableau contenant les informations
     * de suivi du colis
     * @return array(
     *    array('date' => '01/01/2014',
     *        'message' => 'Colis pris en charge par la poste',
     *        'lieu' => 'Plate-Forme Est'
     *    ),
     *    array( ...
     *    ),
     *    ...
     *    )
    */
    public function getSuivi()
    {
        // Téléchargement de  la page
        $getIndex = curl_init();
        curl_setopt($getIndex, CURLOPT_URL, "http://www.colissimo.fr/portail_colissimo/suivre.do?colispart=" . $this->id);
        curl_setopt($getIndex, CURLOPT_HEADER, true);
        curl_setopt($getIndex, CURLOPT_RETURNTRANSFER, true);
        $pageIndex = curl_exec($getIndex);
        curl_close($getIndex);

        // Extraction du cookie
        $cookie = preg_replace('#^.+Set-Cookie: (.+) Path=.+$#isU', '$1', $pageIndex);

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
            preg_match_all('#<td headers=".+">(.+)</td>#isU', $stape, $details);
            $details = $details[1];
            $infosDetails = array();
            foreach ($details as $detail) {
                $d++;
                $text = utf8_encode(trim($detail));
		        $infosDetails[$nameInfos[$d]] = $text;
            }
            $finalText[] = $infosDetails;
        }

        return $finalText;
    }
}
