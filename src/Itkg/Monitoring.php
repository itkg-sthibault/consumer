<?php

namespace Itkg;

/**
 * Classe de monitoring de Service
 * Cette classe permet le monitoring de différents services et effectue
 * un rapport global
 *
 * @author Pascal DENIS <pascal.denis@businessdecision.com>
 * @author Benoit de JACOBET <benoit.dejacobet@businessdecision.com>
 * @author Cl�ment GUINET <clement.guinet@businessdecision.com>
 *
 * @abstract
 * @package \Itkg
 */
class Monitoring
{

    /**
     * Les monitorings courants
     *
     * @staticvar
     * @var array
     */
    protected static $tests;

    /**
     * Les loggers courants
     *
     * @staticvar
     * @var array
     */
    protected static $loggers;

    /**
     * Le début du test
     * @var int
     */
    protected $start;

    /**
     * La fin du test
     * @var int
     */
    protected $end;

    /**
     * L'exception si elle a été levée par le test
     * @var \Exception
     */
    protected $exception;

    /**
     * L'état du test
     * @var boolean
     */
    protected $working;

    /**
     * La durée du test
     * @var int
     */
    protected $duration;

    /**
     * Identifiant du test
     * @var string
     */
    protected $identifier;

    /**
     * Test courant
     * 
     * @var \Itkg\Monitoring\Test
     */
    protected $test;

    /**
     * Get le début du test
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Get la fin du test
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Get l'exception si une exception a été levée par le test
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Get la dur�e du test
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set le résultat du test
     *
     * @return boolean
     */
    public function isWorking()
    {
        return $this->working;
    }

    /**
     * Set le début du test
     *
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Set la fin du test
     *
     * @param int $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Set la durée du test
     *
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Set l'exception associée
     *
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Ajoute un logger à la pile courante
     *
     * @static
     * @param \Itkg\Log\Writer $logger
     */
    public static function addLogger(\Itkg\Log\Writer $logger, $id)
    {
        self::$loggers[$id] = $logger;
    }

    /**
     * Effectue le monitoring du service et ajoute le monitoring à la pile existante
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Effectue le monitoring du service et ajoute le monitoring à la pile existante
     *
     * @param \Itkg\Service $service
     * @param string $method
     */
    public function addService(\Itkg\Service $service, $method)
    {
        if($service->getConfiguration()->isMonitored()) {
            $this->start = microtime(true);
            // Initialisation des attributs de monitoring + lancement du test et traitement
            try {
                $service->preCall($method);

                $oResponse = $service->$method();
                $this->working = true;

            } catch (\Exception $e) {
                $this->exception = $e;
                $this->working = false;
            }

            $this->end = microtime(true);

            //pour logguer les appels monitoring
            try {
                $service->setStart($this->start);
                $service->setEnd($this->end);
                $service->postCall($oResponse, null, $this->exception);
            } catch (\Exception $e) {
                // on ne fait rien dans le cas du monitoring
            }

            $this->duration = $this->end - $this->start;
        }
        $this->identifier = $service->getConfiguration()->getIdentifier();
        $this->service = $service;
        self::$tests[] = $this;
    }

    /**
     * Getter service
     * Retourne le service courant
     * 
     * @return \Itkg\Service
     */
    public function getService()
    {
        return $this->service;
    }
    
    /**
     * Getter test
     * Retourne le test courant
     * 
     * @return \Itkg\Monitoring\Test
     */
    public function getTest()
    {
        return $this->test;
    }
    
    /**
     * Effectue le test et ajoute le monitoring à la pile existante
     *
     * @param \Itkg\Monitoring\Test $test
     */
    public function addTest(\Itkg\Monitoring\Test $test)
    {
        $this->start = microtime(true);
        // Initialisation des attributs de monitoring + lancement du test et traitement

        try {

            $test->execute();
            $this->working = true;

        } catch (\Exception $e) {

            $this->exception = $e;
            $this->working = false;
        }
        $this->test = $test;
        $this->end = microtime(true);
        $this->duration = $this->end - $this->start;
        $this->identifier = $test->getIdentifier();
        self::$tests[] = $this;
    }

    /**
     * Initialise les tests
     */
    public static function clear()
    {
        // Initialisation des tests
        self::$tests = array();
    }

    /**
     *
     * @param string $work Message si service OK
     * @param string $fail Message si service KO
     * @param string $generalWork Message si general OK
     * @param string $generalFail Message si general KO
     */
    public static function logReport($work = 'OK', $fail = 'KO', $generalWork = '[GLOBAL : OKSFR]', $generalFail = '[GLOBAL : KOSFR]')
    {
        // $working indique l'état général
        $working = true;
        
        $report = '';
        // Log des rapports
        if (is_array(self::$tests)) {
            foreach (self::$tests as $test) {
                if(isset($test->service)){
                    $serviceConfiguration = $test->service->getConfiguration();
                }
                if (!isset($serviceConfiguration) || $serviceConfiguration->isMonitored()) {
                    //si le service est supervisé
                    if(!isset($serviceConfiguration) || $serviceConfiguration->isEnabled()){
                        //si le service est activé
                        if ($test->isWorking()) {
                            $report .= '<span class="libelle working">' . $test->getIdentifier() . '</span><br /><span class="working">' . $work . ' (' . number_format($test->getDuration(), 4) . 'sec) </span><br />';
                        } else {
                            $e=$test->getException();
                            $report .= '<span class="libelle error">' . $test->getIdentifier() . '</span><br /><span class="error">' . $fail . ' (' . number_format($test->getDuration(), 4) . 'sec)' . (!empty($e)?(" - ".$e->getMessage()):"") . '</span><br />';
                            //si un service supervisé ne répond pas => état général KO
                            $working = false;
                        }
                    } else {
                        //si le service est désactivé
                        if ($test->isWorking()) {
                            $report .= '<span class="libelle working disabled">' . $test->getIdentifier() . ' (d&eacute;sactiv&eacute;)</span><br /><span class="working disabled">' . $work . ' (' . number_format($test->getDuration(), 4) . 'sec) </span><br />';
                        } else {
                            $e=$test->getException();
                            $report .= '<span class="libelle error disabled">' . $test->getIdentifier() . ' (d&eacute;sactiv&eacute;)</span><br /><span class="error disabled">' . $fail . ' (' . number_format($test->getDuration(), 4) . 'sec)' . (!empty($e)?(" - ".$e->getMessage()):"") . '</span><br />';
                            //on ne prend pas en compte ce service dans l'état général car il est désactivé
                        }
                    }
                } else {
                    //si le service n'est pas supervisé
                    $report .= '<span class="libelle nomon">' . $test->getIdentifier() . ' (non supervis&eacute;)</span><br /><br />';
                }
            }
        }

        //Etat général
        if($working) {
            $report .= '<br />'.$generalWork;
        }else {
            $report .= '<br />'.$generalFail;
        }

        if(is_array(self::$loggers)) {

            foreach(self::$loggers as $index => $logger) {
                // Les balises html ne s'affichent que dans le cas d'un echo
                if($index == 'echo') {
                    $logger->write($report);
                }else {
                    $report = str_replace('<br />', "\r\n", $report);
                    $report = strip_tags($report);
                    $logger->write($report);
                }
            }
        }
    }
    
    /**
     * Getter tests
     * 
     * @static
     * @return array
     */
    public static function getTests()
    {
        if(!is_array(self::$tests)) {
            self::$tests = array();
        }
        return self::$tests;
    }
}