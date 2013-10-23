<?php

namespace Oro\Bundle\TagBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TagBundle\Datagrid\ResultsDatagridManager;

class TagController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_tag_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_tag_view",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/create", name="oro_tag_create")
     * @Acl(
     *      id="oro_tag_create",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="CREATE"
     * )
     * @Template("OroTagBundle:Tag:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Tag());
    }

    /**
     * @Route("/update/{id}", name="oro_tag_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Acl(
     *      id="oro_tag_update",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="EDIT"
     * )
     * @Template
     */
    public function updateAction(Tag $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("/search/{id}", name="oro_tag_search", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @AclAncestor("oro_tag_view")
     */
    public function searchAction(Tag $entity, Request $request)
    {
        $from = $request->get('from');
        $datagrid = $this->getSearchResultsDatagrid($from, $entity);

        /** @var \Oro\Bundle\TagBundle\Provider\SearchProvider $provider */
        $provider = $this->get('oro_tag.provider.search_provider');

        return array(
            'tag'            => $entity,
            'from'           => $from,
            'groupedResults' => $provider->getGroupedResults($entity->getId()),
        );
    }

    /**
     * Return search results in json for datagrid
     *
     * @Route("/ajax/{id}", name="oro_tag_search_ajax", requirements={"id"="\d+"}, defaults={"id"=0})
     * @AclAncestor("oro_tag_view")
     */
    public function searchResultsAjaxAction(Tag $entity, Request $request)
    {
        $from   = $request->get('from');
        $datagrid = $this->getSearchResultsDatagrid($from, $entity);

        return '';
    }

    /**
     * @param  string   $from
     * @param  Tag      $tag
     * @return Datagrid
     */
    protected function getSearchResultsDatagrid($from, Tag $tag)
    {
        /** @var $datagridManager ResultsDatagridManager */
        //$datagridManager = $this->get('oro_tag.datagrid_results.datagrid_manager');

        $datagridManager->setSearchEntity($from);
        $datagridManager->setTag($tag);
        $datagridManager->getRouteGenerator()->setRouteParameters(
            array(
                'from'   => $from,
                'id'     => $tag->getId(),
            )
        );

        return $datagridManager->getDatagrid();
    }

    /**
     * @param Tag $entity
     * @return array|RedirectResponse
     */
    protected function update(Tag $entity)
    {
        if ($this->get('oro_tag.form.handler.tag')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.tag.controller.tag.saved.message')
            );

            return $this->redirect($this->generateUrl('oro_tag_index'));
        }

        return array(
            'form' => $this->get('oro_tag.form.tag')->createView(),
        );
    }
}
