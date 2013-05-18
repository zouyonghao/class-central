<?php

namespace ClassCentral\SiteBundle\Repository;


use Doctrine\ORM\EntityRepository;
use ClassCentral\SiteBundle\Entity\Offering;

class StreamRepository extends EntityRepository {

    /**
     * Used in the navabar
     */
    public function getCourseCountByStream()
    {
        $em = $this->getEntityManager();

        $result = $em->createQuery(
            'SELECT s.name as name, COUNT(DISTINCT c.id) AS total, s.slug as slug  FROM ClassCentralSiteBundle:Course c JOIN
             c.stream s  JOIN c.offerings o WHERE o.status != ' .Offering::COURSE_NA. ' GROUP BY c.stream ORDER BY total')
            ->getArrayResult();


        return $result;
    }
}