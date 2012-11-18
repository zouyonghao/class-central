<?php

namespace ClassCentral\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use ClassCentral\SiteBundle\Entity\Offering;

class InitiativeRepository extends EntityRepository{
    
    public function getOfferingCountByInitative(){
        
        $em = $this->getEntityManager();
        
        $result = $em->createQuery(
                        'SELECT i.name, COUNT(o.id) AS total, i.code  FROM ClassCentralSiteBundle:Offering o JOIN  
                         o.initiative i WHERE o.status != :status  GROUP BY o.initiative ORDER BY total')
                    ->setParameter('status', Offering::COURSE_NA)
                    ->getArrayResult();
        
        
        return $result;
    }
    
}

