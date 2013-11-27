<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Stream;
use ClassCentral\SiteBundle\Form\StreamType;
use ClassCentral\SiteBundle\Entity\Offering;

/**
 * Stream controller.
 *
 */
class StreamController extends Controller
{
    /**
     * Lists all Stream entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Stream')->findAll();

        return $this->render('ClassCentralSiteBundle:Stream:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Stream entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Stream')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Stream entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Stream:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Stream entity.
     *
     */
    public function newAction()
    {
        $entity = new Stream();
        $form   = $this->createForm(new StreamType(), $entity);

        return $this->render('ClassCentralSiteBundle:Stream:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Stream entity.
     *
     */
    public function createAction()
    {
        $entity  = new Stream();
        $request = $this->getRequest();
        $form    = $this->createForm(new StreamType(), $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('stream_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Stream:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Stream entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Stream')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Stream entity.');
        }

        $editForm = $this->createForm(new StreamType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Stream:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Stream entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Stream')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Stream entity.');
        }

        $editForm   = $this->createForm(new StreamType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('stream_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Stream:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Stream entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Stream')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Stream entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('stream'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
    
    public function viewAction($slug) 
    {
          $em = $this->getDoctrine()->getManager();
          $stream = $em->getRepository('ClassCentralSiteBundle:Stream')->findOneBySlug($slug);
          
          if(!$stream || !$stream->getShowInNav())
          {
              // TODO: Show an error page
              return;
          }
          
        $cache = $this->get('Cache');
        $offerings = $cache->get('stream_offerings_' . $slug, array($this, 'getOfferingsByStream'), array($stream));

        $pageInfo = PageHeaderFactory::get($stream);
        $pageInfo->setPageUrl(
            $this->container->getParameter('baseurl'). $this->get('router')->generate('ClassCentralSiteBundle_stream', array('slug' => $slug))
        );
        return $this->render('ClassCentralSiteBundle:Stream:view.html.twig', array(
                'stream' => $stream->getName(),
                'offerings' => $offerings,
                'page' => 'stream',
                'slug' => $slug,
                'offeringTypes' => Offering::$types,
                'pageInfo' => $pageInfo
            ));
    }
    
    public function getOfferingsByStream(\ClassCentral\SiteBundle\Entity\Stream $stream) {
        $courses = $stream->getCourses();

        $courseIds = array();
        foreach ($courses as $course) {
            $courseIds[] = $course->getId();
        }

        return $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds($courseIds);
    }
}
