<?php

namespace Xelko\TimeSlots;

use Xelko\TimeSlots\Exception;

/**
 * @name Calendar
 * @version 0.1
 * @copyright (c) xelko 2016
 * @Auteur 'denis.bichon@xelko.com'
 * @Date 21/04/2016 
 * @license MIT
 */
class Calendar
{

    private $cache;
    private $aRules;
    private $aDays;
    private $granularity;
    private $midnightAlignment;
    private $cacheSize;
    public $formatDate;

    public function __construct()
    {
        $this->formatDate = "Y-m-d";
        $this->clearRules();
        $this->setGranularity();
        $this->setMidnightAlignment();
        $this->setCacheSize();
        $this->clearCache();
    }

    /**
     * Détermine le nombre maximum d'éléments mis en cache (par fonction)
     * @param int $cacheSize Nombre d'éléments mémorisés par cache
     * @return \Xelko\TimeSlots\Calendar
     */
    public function setCacheSize($cacheSize = 0)
    {
        $this->cacheSize = abs((int) $cacheSize);
        return $this;
    }

    /**
     * Détermine la granularité des crénaux horaires
     * @param int $timeSlotSize Granularité (minutes)
     * @return \Xelko\TimeSlots\Calendar
     */
    public function setGranularity($timeSlotSize = 30)
    {
        $this->granularity = abs((int) $timeSlotSize);
        $this->clearDays();
        return $this;
    }

    /**
     * Détermine si les crénaux horaires doivent être alignés sur 0h00 (multiple de la granularité)
     * @param boolean $midnightAlignment
     * @return \Xelko\TimeSlots\Calendar
     */
    public function setMidnightAlignment($midnightAlignment = false)
    {
        $this->midnightAlignment = (boolean) $midnightAlignment;
        $this->clearDays();
        return $this;
    }

    /**
     * Ajoute un tableau de règles définissant les horaires d'ouverture
     * @param array $aRules Tableau de règles
     * @return \Xelko\TimeSlots\Calendar
     */
    public function addOpenRule(array $aRules)
    {
        foreach ($aRules as $rule) {
            $this->aRules["open"][] = $this->parseRule($rule);
        }
        $this->clearDays();
        return $this;
    }

    /**
     * Ajoute un tableau de règles définissant les horaires de fermeture
     * @param array $aRules  Tableau de règles
     * @return \Xelko\TimeSlots\Calendar
     */
    public function addCloseRule(array $aRules)
    {
        foreach ($aRules as $rule) {
            $this->aRules["close"][] = $this->parseRule($rule);
        }
        $this->clearDays();
        return $this;
    }

    /**
     * Supprime toutes les règles précédemment définies (et les données générées)
     * @return \Xelko\TimeSlots\Calendar
     */
    public function clearRules()
    {
        $this->clearOpenRules();
        $this->clearCloseRules();
        $this->clearDays();
        return $this;
    }

    /**
     * Supprime toutes les règles d'ouverture précédemment définies (et les données générées)
     * @return \Xelko\TimeSlots\Calendar
     */
    public function clearOpenRules()
    {
        $this->aRules["open"] = [];
        $this->clearDays();
        return $this;
    }

    /**
     * Supprime toutes les règles de fermeture précédemment définies (et les données générées)
     * @return \Xelko\TimeSlots\Calendar
     */
    public function clearCloseRules()
    {
        $this->aRules["close"] = [];
        $this->clearDays();
        return $this;
    }

    /**
     * Retourne les périodes ouvertes pour chaque date (entre les dates de début et de fin) 
     * @param \DateTime $dtBegin DateHeure de début
     * @param \DateTime $dtEnd DateHeure de fin (exclu)
     * @param boolean $withAjustment Tient compte des horaires (oui/non)
     * @return array
     */
    public function getPeriodsOfDays(\DateTime $dtBegin, \DateTime $dtEnd, $withAjustment = false)
    {
        $dtBeginDate = clone $dtBegin;
        $dtBeginDate->setTime(0, 0, 0);

        $dtCurrent = clone $dtBeginDate;

        $dtEndDate = clone $dtEnd;
        // $dtEnd est exclu donc on retire une seconde
        $dtEndDate->modify("-1 sec")->setTime(0, 0, 0);

        $days = [];
        while ($dtCurrent <= $dtEndDate) {
            $days[$dtCurrent->format($this->formatDate)] = $this->getPeriodsOfDay($dtCurrent);
            $dtCurrent->modify('+1 day');
        }

        if ((bool) $withAjustment) {
            $sBeginDate = $dtBeginDate->format($this->formatDate);
            $days[$sBeginDate] = $this->ajustPeriods($days[$sBeginDate], $dtBegin, -1);

            $sEndDate = $dtEndDate->format($this->formatDate);
            $days[$sEndDate] = $this->ajustPeriods($days[$sEndDate], $dtEnd, 1);
        }

        return $days;
    }

    /**
     * Retourne les périodes ouvertes du jour passé en paramètre 
     * @param \DateTime $dtDay
     * @return array
     */
    public function getPeriodsOfDay(\DateTime $dtDay)
    {
        $sDate = $dtDay->format($this->formatDate);

        if (!isset($this->aDays[$sDate]["periods"])) {
            $this->aDays[$sDate]["periods"] = $this->generatePeriodsOfDay($dtDay);
        }

        return $this->aDays[$sDate]["periods"];
    }

    /**
     * Retourne les crénaux horaires d'ouverture pour chaque date (entre les dates de début et de fin) 
     * @param \DateTime $dtBegin DateHeure de début
     * @param \DateTime $dtEnd DateHeure de fin (exclu)
     * @param boolean $withAjustment Tient compte des horaires (oui/non)
     * @return type
     */
    public function getTimeSlotsOfDays(\DateTime $dtBegin, \DateTime $dtEnd, $withAjustment = false)
    {
        $periodsDays = $this->getPeriodsOfDays($dtBegin, $dtEnd, $withAjustment);
        $days = [];
        foreach ($periodsDays as $key => $periods) {
            $days[$key] = $this->getTimeSlotsOfDayPeriods($periods);
        }
        return $days;
    }

    /**
     * Retourne les crénaux horaires des périodes de la journée passées en paramètre  
     * @param array $aPeriod array(array('begin'=>(int) minutes, 'end'=>(int) minutes)) {minutes 0..1440}
     * @return array
     */
    public function getTimeSlotsOfDayPeriods(array $aPeriods)
    {
        return $this->generateTimeSlotsOfDayPeriods($aPeriods);
    }

    /**
     * Génère les périodes du jour passé en parametre 
     * @param \DateTime $dtDay
     * @return array
     */
    private function generatePeriodsOfDay(\DateTime $dtDay)
    {
        $sDate = $dtDay->format($this->formatDate);
        $aOpenPeriods = [];
        $aClosePeriods = [];

        foreach ($this->aRules["open"] as $rule) {
            $aOpenPeriods = array_merge($aOpenPeriods, $this->getActiveDefinedPeriods($dtDay, $rule));
        }
        foreach ($this->aRules["close"] as $rule) {
            $aClosePeriods = array_merge($aClosePeriods, $this->getActiveDefinedPeriods($dtDay, $rule));
        }

        return $this->substractPeriods($aOpenPeriods, $aClosePeriods);
    }

    /**
     * Génère les crénaux horaires pour la periode passée en paramètre 
     * @param array $aPeriods array('begin'=>(int) minutes, 'end'=>(int) minutes) [minutes 0..1439]
     * @return array
     */
    private function generateTimeSlotsOfDayPeriods(array $aPeriods)
    {
        if ($this->cacheSize) {
            $cacheKey = serialize($aPeriods);

            if (isset($this->cache['generateTimeSlotsOfDayPeriods'][$cacheKey])) {
                return $this->cache['generateTimeSlotsOfDayPeriods'][$cacheKey];
            }
        }

        $timeSlots = [];

        foreach ($aPeriods as $aPeriod) {
            if (isset($aPeriod['begin']) && isset($aPeriod['end']) && is_integer($aPeriod['begin']) && is_integer($aPeriod['end'])) {
                $begin = $aPeriod['begin'];
                $granularity = $this->granularity;
                if ($this->midnightAlignment && ($begin % $granularity)) {
                    $begin = floor(($begin / $granularity) + 1) * $granularity;
                }
                for (; $begin + $granularity <= $aPeriod['end']; $begin += $granularity) {
                    $timeSlots[] = [
                      "begin" => $begin,
                      "end" => $begin + $granularity,
                    ];
                }
            }
        }

        if ($this->cacheSize) {
            if (count($this->cache['generateTimeSlotsOfDayPeriods']) > $this->cacheSize) {
                array_shift($this->cache['generateTimeSlotsOfDayPeriods']);
            }

            $this->cache['generateTimeSlotsOfDayPeriods'][$cacheKey] = $timeSlots;
        }

        return $timeSlots;
    }

    /**
     * Retourne les périodes déduites à partir des règles applicables au jour passé en paramètre 
     * @param \DateTime $dtDay
     * @param array $rule
     * @return type
     * @throws Exception
     */
    private function getActiveDefinedPeriods(\DateTime $dtDay, array $rule)
    {
        if (!isset($rule['periods'])) {
            return [];
        }

        $dayValid = true;
        $aPeriods = [];
        foreach ($rule as $name => $values) {
            switch ($name) {
                case "periods" :
                case "p" :
                    foreach ($values as $value) {
                        if (is_array($value) && isset($value['min']) && isset($value['max']) && (int) $value['min'] < (int) $value['max']) {
                            $aPeriods[] = [
                              "begin" => (int) $value['min'],
                              "end" => (int) $value['max'],
                            ];
                        } else {
                            throw new Exception("($name) Invalid rule parameter.");
                        }
                    }
                    break;
                case "days" :
                case "d" :
                    $sDate = $dtDay->format("Ymd");
                    $dayValid = false;
                    foreach ($values as $value) {
                        if (is_array($value) && isset($value['min']) && isset($value['max']) && $sDate >= $value['min'] && $sDate <= $value['max']) {
                            $dayValid = true;
                            break;
                        } elseif (is_string($value) && $value === $sDate) {
                            $dayValid = true;
                            break;
                        }
                    }
                    if (!$dayValid) {
                        return [];
                    }
                    break;
                case "weekdays" :
                case "wd" :
                    $wd = (int) $dtDay->format("w") % 7;
                    $dayValid = false;
                    foreach ($values as $value) {
                        if (is_array($value) && isset($value['min']) && isset($value['max']) && $wd >= (int) $value['min'] && $wd <= (int) $value['max']) {
                            $dayValid = true;
                            break;
                        } elseif (is_string($value) && $wd === (int) $value) {
                            $dayValid = true;
                            break;
                        }
                    }
                    if (!$dayValid) {
                        return [];
                    }
                    break;
                case "birddays" :
                case "bd" :
                    $bd = $dtDay->format("md");
                    $dayValid = false;
                    foreach ($values as $value) {
                        if (is_array($value) && isset($value['min']) && isset($value['max']) && $bd >= $value['min'] && $bd <= $value['max']) {
                            $dayValid = true;
                            break;
                        } elseif (is_string($value) && $bd === $value) {
                            $dayValid = true;
                            break;
                        }
                    }
                    if (!$dayValid) {
                        return [];
                    }
                    break;
                case "yeardays" :
                case "yd" :
                    $yd = (int) $dtDay->format("z") + 1;
                    $dayValid = false;
                    foreach ($values as $value) {
                        if (is_array($value) && isset($value['min']) && isset($value['max']) && $yd >= (int) $value['min'] && $yd <= (int) $value['max']) {
                            $dayValid = true;
                            break;
                        } elseif (is_string($value) && $yd === (int) $value) {
                            $dayValid = true;
                            break;
                        }
                    }
                    if (!$dayValid) {
                        return [];
                    }
                    break;
                case "specialdays" :
                case "sd" :
                    $year = $dtDay->format("Y");
                    $ecart = easter_days($year);
                    $dtEaster = (new DateTime("$year-03-21"))->modify("+ $ecart d");
                    $dtEasterMonday = clone $dtEaster;
                    $dtEasterMonday->modify("+1d");
                    $dtAscension = clone $dtEaster;
                    $dtAscension->modify("+39d");
                    $dtPentecost = clone $dtEaster;
                    $dtPentecost->modify("+88d");

                    $dayValid = false;
                    foreach ($values as $value) {
                        if (is_string($value)) {
                            if (($value === "easter" || $value === "paques" || $value === "pâques") && $dtDay->format("ymd") == $dtEaster->format("ymd")) {
                                $dayValid = true;
                                break;
                            } elseif (($value === "easterMonday" || $value === "lundiPaques" || $value === "lundiPâques") && $dtDay->format("ymd") == $dtEasterMonday->format("ymd")) {
                                $dayValid = true;
                                break;
                            } elseif (($value === "ascension" ) && $dtDay->format("ymd") == $dtAscension->format("ymd")) {
                                $dayValid = true;
                                break;
                            } elseif (($value === "pentecost" || $value === "pentecote" || $value === "pentecôte") && $dtDay->format("ymd") == $dtPentecost->format("ymd")) {
                                $dayValid = true;
                                break;
                            }
                        } else {
                            throw new Exception("($name) Invalid rule parameter.");
                        }
                    }
                    if (!$dayValid) {
                        return [];
                    }
                    break;
                default :
                    throw new Exception("($name) Invalid rule parameter.");
            }
        }

        if ($dayValid) {
            return $aPeriods;
        } else {
            return [];
        }
    }

    /**
     * Simplifie la définition des périodes 
     * @param array $aPeriods
     * @return array
     */
    private function reducePeriods(array $aPeriods)
    {
        if ($this->cacheSize) {
            $cacheKey = serialize($aPeriods);

            if (isset($this->cache['reducePeriods'][$cacheKey])) {
                return $this->cache['reducePeriods'][$cacheKey];
            }
        }

        usort($aPeriods, function($a, $b) {
            if ($a['begin'] == $b['begin']) {
                return 0;
            }
            return ($a['begin'] < $b['begin']) ? -1 : 1;
        });

        $aNewPeriods = [];
        foreach ($aPeriods as $key => $aCurrentPeriod) {
            if ($aCurrentPeriod['begin'] < 0)
                $aCurrentPeriod['begin'] = 0;
            if ($aCurrentPeriod['begin'] > 1440)
                $aCurrentPeriod['begin'] = 1440;
            if ($aCurrentPeriod['end'] < 0)
                $aCurrentPeriod['end'] = 0;
            if ($aCurrentPeriod['end'] > 1440)
                $aCurrentPeriod['end'] = 1440;

            if (isset($refPeriod) && $aCurrentPeriod['begin'] <= $refPeriod['end'] && $aCurrentPeriod['end'] > $refPeriod['end']) {
                // on étend la période précédente
                $refPeriod['end'] = $aCurrentPeriod['end'];
            } elseif (isset($refPeriod) && $aCurrentPeriod['begin'] >= $refPeriod['begin'] && $aCurrentPeriod['end'] <= $refPeriod['end']) {
                // on supprime la période incluse 
                unset($aPeriods[$key]);
            } else {
                // on supprime la référence éventuelle
                unset($refPeriod);
                // on crée une nouvelle période 
                $refPeriod = [
                  'begin' => $aCurrentPeriod['begin'],
                  'end' => $aCurrentPeriod['end'],
                ];
                $aNewPeriods[] = &$refPeriod;
            }
        }
        unset($refPeriod);

        if ($this->cacheSize) {
            if (count($this->cache['reducePeriods']) > $this->cacheSize) {
                array_shift($this->cache['reducePeriods']);
            }

            $this->cache['reducePeriods'][$cacheKey] = $aNewPeriods;
        }

        return $aNewPeriods;
    }

    /**
     * Ajuste les périodes en fonction de l'horodatage passé en paramètre
     * @param array $periods Périodes à ajuster
     * @param \DateTime $datetime Horodatage 
     * @param int $type Type d'ajustement (début si -1) et (fin si +1)
     * @return array Les périodes une fois ajustées
     */
    private function ajustPeriods(array $periods, \DateTime $datetime, $type)
    {
        list($hours, $minutes) = explode(":", $datetime->format("H:i"));
        $minutesDay = ($hours * 60) + $minutes;

        foreach ($periods as $key => $period) {
            if ((int) $type < 0) {
                if ($period['end'] <= $minutesDay) {
                    unset($periods[$key]);
                } elseif ($period['begin'] < $minutesDay) {
                    $periods[$key]['begin'] = $minutesDay;
                }
            } elseif ((int) $type > 0) {
                if ($period['begin'] > $minutesDay) {
                    unset($periods[$key]);
                } elseif ($period['end'] > $minutesDay) {
                    $periods[$key]['end'] = $minutesDay;
                }
            }
        }

        return $periods;
    }

    /**
     * Retourne les périodes $aOpenPeriods réduites des périodes $aClosePeriods 
     * @param array $aOpenPeriods
     * @param array $aClosePeriods
     * @return array
     */
    private function substractPeriods(array $aOpenPeriods, array $aClosePeriods)
    {
        if ($this->cacheSize) {
            $cacheKey = serialize($aOpenPeriods) . "/" . serialize($aClosePeriods);
            if (isset($this->cache['substractPeriods'][$cacheKey])) {
                return $this->cache['substractPeriods'][$cacheKey];
            }
        }

        $aOpenPeriods = $this->reducePeriods($aOpenPeriods);
        $aClosePeriods = $this->reducePeriods($aClosePeriods);

        foreach ($aClosePeriods as $aClosePeriod) {
            foreach ($aOpenPeriods as $key => $aOpenPeriod) {
                // test rognage du debut
                if ($aClosePeriod['end'] > $aOpenPeriod['begin'] && $aClosePeriod['begin'] < $aOpenPeriod['begin']) {
                    if ($aClosePeriod['end'] == $aOpenPeriod['end']) {
                        unset($aOpenPeriods[$key]);
                    } else {
                        $aOpenPeriods[$key]['begin'] = $aClosePeriod['end'];
                    }
                }
                // test rognage de la fin
                if ($aClosePeriod['begin'] < $aOpenPeriod['end'] && $aClosePeriod['end'] > $aOpenPeriod['end']) {
                    if ($aClosePeriod['begin'] == $aOpenPeriod['begin']) {
                        unset($aOpenPeriods[$key]);
                    } else {
                        $aOpenPeriods[$key]['end'] = $aClosePeriod['begin'];
                    }
                }
                // test rognage total
                if ($aClosePeriod['begin'] < $aOpenPeriod['begin'] && $aClosePeriod['end'] > $aOpenPeriod['end']) {
                    unset($aOpenPeriods[$key]);
                }
                // test decoupage en deux periodes
                if ($aClosePeriod['begin'] > $aOpenPeriod['begin'] && $aClosePeriod['end'] < $aOpenPeriod['end']) {
                    // cree la deuxieme periode
                    if ($aClosePeriod['end'] != $aOpenPeriod['end']) {
                        $aOpenPeriods[] = [
                          'begin' => $aClosePeriod['end'],
                          'end' => $aOpenPeriod['end'],
                        ];
                    }
                    // modifie premiere periode
                    if ($aClosePeriod['begin'] == $aOpenPeriod['begin']) {
                        unset($aOpenPeriods[$key]);
                    } else {
                        $aOpenPeriods[$key]['end'] = $aClosePeriod['begin'];
                    }
                }
            }
        }

        usort($aOpenPeriods, function($a, $b) {
            if ($a['begin'] == $b['begin']) {
                return 0;
            }
            return ($a['begin'] < $b['begin']) ? -1 : 1;
        });

        if ($this->cacheSize) {
            if (count($this->cache['substractPeriods']) > $this->cacheSize) {
                array_shift($this->cache['substractPeriods']);
            }

            $this->cache['substractPeriods'][$cacheKey] = $aOpenPeriods;
        }

        return $aOpenPeriods;
    }

    /**
     * Parse la règle (string) et la retourne sur forme de tableau
     * @param string $sRule
     * @return array
     * @throws Exception
     */
    private function parseRule($sRule)
    {
        if (!is_string($sRule)) {
            throw new Exception('La règle doit être de type string');
        }
        $aParse = [];
        $aRules = explode(";", $sRule);
        foreach ($aRules as $rule) {
            if (trim($rule) === "") {
                continue;
            }
            list($ruleName, $ruleValues) = explode("=", $rule . "=");
            $aValues = explode(",", $ruleValues);
            foreach ($aValues as $value) {
                if (strpos($value, "-") !== FALSE) {
                    $aBoundaries = explode("-", $value);
                    $aParse[trim($ruleName)][] = ["min" => trim($aBoundaries[0]), "max" => trim($aBoundaries[1])];
                } else {
                    $aParse[trim($ruleName)][] = trim($value);
                }
            }
        }

        return $aParse;
    }

    /**
     * Vide le cache
     * @return \Xelko\TimeSlots\Calendar
     */
    private function clearCache()
    {
        $this->cache = [
          'generateTimeSlotsOfDayPeriods' => [],
          'substractPeriods' => [],
          'reducePeriods' => [],
        ];
        return $this;
    }

    /**
     * Supprime toutes les données générées
     * @return \Xelko\TimeSlots\Calendar
     */
    private function clearDays()
    {
        $this->aDays = null;
        $this->clearCache();
        return $this;
    }

}
