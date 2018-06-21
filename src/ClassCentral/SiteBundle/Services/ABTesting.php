<?php


namespace ClassCentral\SiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ABTesting
{
    private $container;

    private $tests = [];

    private $variations = [];

    private $sessionNumber;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getTestVariation($test)
    {
        if(isset($this->tests[$test]))
        {
            $numVariations = count($this->tests[$test]);
            $variationNum = $this->getSessionNumber()%$numVariations;
            $variationToRun = $this->tests[$test][$variationNum];
            $this->variations[$test] = $variationToRun;
            return $variationToRun;
        }

        return null;
    }

    /**
     * @param $test name of the test
     * @param array $varations list of test variations and their frequencies
     */
    public function initializeTest($test, $varations = [])
    {
        $varationsBasedOnFrequency = [];
        foreach ($varations as $variation => $frequency)
        {
            do {
                $varationsBasedOnFrequency[] = $variation;
                $frequency--;
            } while ($frequency > 0);
        }

        // Initializes the test.
        $this->tests[$test] = $varationsBasedOnFrequency;
    }

    /**
     * Generate a random number for this session and store it in session.
     */
    private function getSessionNumber()
    {
        if($this->sessionNumber)
        {
            return $this->sessionNumber;
        }

        $session = $this->container->get('session');
        if(!$session->isStarted())
        {
            // Start the session if its not already started
            $session->start();
        }

        if($session->get('sessionNumber'))
        {
            return $session->get('sessionNumber');
        }
        else
        {
            $sessionNumber = mt_rand();
            $session->set('sessionNumber',$sessionNumber);
            return $sessionNumber;
        }

    }

    public function getVariations()
    {
        return $this->variations;
    }


}