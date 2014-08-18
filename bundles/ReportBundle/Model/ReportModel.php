<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ReportModel extends FormModel
{

    public function getRepository()
    {
        return $this->em->getRepository('MauticReportBundle:Report');
    }

    public function getPermissionBase()
    {
        return 'report:reports';
    }

    public function getNameGetter()
    {
        return "getTitle";
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        //note for later
        $parent = $entity->getVariantParent();
        $parentId = ($parent) ? $parent->getId() : 0;

        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = strtolower(InputHelper::alphanum($entity->getTitle(), true));
            } else {
                $alias = strtolower(InputHelper::alphanum($alias, true));
            }

            //make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkUniqueAlias($testAlias, $entity);
            $aliasTag  = $count;

            while ($count) {
                $testAlias = $alias . $aliasTag;
                $count     = $repo->checkUniqueAlias($testAlias, $entity);
                $aliasTag++;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        $now = new \DateTime();

        //set the author for new pages
        if ($entity->isNew()) {
            $user = $this->factory->getUser();
            $entity->setAuthor($user->getName());
        } else {
            //increase the revision
            $revision = $entity->getRevision();
            $revision++;
            $entity->setRevision($revision);

            //reset the variant hit and start date if there are any changes
            $changes = $entity->getChanges();
            if (!empty($changes) && empty($this->inConversion)) {
                $entity->setVariantHits(0);
                $entity->setVariantStartDate($now);
            }
        }

        parent::saveEntity($entity, $unlock);

        //also reset variants if applicable due to changes
        if (!empty($changes) && empty($this->inConversion)) {
            $parent   = $entity->getVariantParent();
            $children = (!empty($parent)) ? $parent->getVariantChildren() : $entity->getVariantChildren();

            $variants = array();
            if (!empty($parent)) {
                $parent->setVariantHits(0);
                $parent->setVariantStartDate($now);
                $variants[] = $parent;
            }

            if (count($children)) {
                foreach ($children as $child) {
                    $child->setVariantHits(0);
                    $child->setVariantStartDate($now);
                    $variants[] = $child;
                }
            }

            //if the parent was changed, then that parent/children must also be reset
            if (isset($changes['variantParent']) && $parentId) {
                $parent = $this->getEntity($parentId);
                if (!empty($parent)) {
                    $parent->setVariantHits(0);
                    $parent->setVariantStartDate($now);
                    $variants[] = $parent;

                    $children = $parent->getVariantChildren();
                    if (count($children)) {
                        foreach ($children as $child) {
                            $child->setVariantHits(0);
                            $child->setVariantStartDate($now);
                            $variants[] = $child;
                        }
                    }
                }
            }

            if (!empty($variants)) {
                $this->saveEntities($variants, false);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('page', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Page();
            $entity->setSessionId('new_' . uniqid());
        } else {
            $entity = parent::getEntity($id);
            $entity->setSessionId($entity->getId());
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }

        switch ($action) {
            case "pre_save":
                $name = PageEvents::PAGE_PRE_SAVE;
                break;
            case "post_save":
                $name = PageEvents::PAGE_POST_SAVE;
                break;
            case "pre_delete":
                $name = PageEvents::PAGE_PRE_DELETE;
                break;
            case "post_delete":
                $name = PageEvents::PAGE_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'page':
                $viewOther = $this->security->isGranted('page:pages:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->factory->getUser());
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
            case 'category':
                $results = $this->factory->getModel('page.category')->getRepository()->getCategoryList($filter, $limit, 0);
                break;
        }

        return $results;
    }

    /**
     * Generate url for a page
     *
     * @param $entity
     * @param $absolute
     * @return mixed
     */
    public function generateUrl($entity, $absolute = true)
    {
        $pageSlug = $entity->getId() . ':' . $entity->getAlias();

        //should the url include the category
        $catInUrl    = $this->factory->getParameter('cat_in_page_url');
        if ($catInUrl) {
            $category = $entity->getCategory();
            $catSlug = (!empty($category)) ? $category->getId() . ':' . $category->getAlias() :
                $this->translator->trans('mautic.core.url.uncategorized');
        }

        $parent = $entity->getTranslationParent();
        if ($parent) {
            //multiple languages so tak on the language
            $slugs = array(
                'slug1' => $entity->getLanguage(),
                'slug2' => (!empty($catSlug)) ? $catSlug : $pageSlug,
                'slug3' => (!empty($catSlug)) ? $pageSlug : ''
            );
        } else {
            $slugs = array(
                'slug1' => (!empty($catSlug)) ? $catSlug : $pageSlug,
                'slug2' => (!empty($catSlug)) ? $pageSlug : '',
                'slug3' => ''
            );
        }

        $pageUrl  = $this->factory->getRouter()->generate('mautic_page_public', $slugs, $absolute);

        return $pageUrl;
    }

    /**
     * @param        $page
     * @param        $request
     * @param string $code
     */
    public function hitPage($page, $request, $code = '200')
    {
        $hit = new Hit();
        $hit->setDateHit(new \Datetime());

        //check for the tracking cookie
        $trackingId = $request->cookies->get('mautic_session_id');
        if (empty($trackingId)) {
            $trackingId = uniqid();
        } else {
            $lastHit = $request->cookies->get('mautic_referer_id');
            if (!empty($lastHit)) {
                //this is not a new session so update the last hit if applicable with the date/time the user left
                $repo = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
                $repo->updateHitDateLeft($lastHit);
            }
        }

        //create a tracking cookie
        $expire = time() + 1800;
        setcookie('mautic_session_id', $trackingId, $expire);
        $hit->setTrackingId($trackingId);

        if (!empty($page)) {
            $hit->setPage($page);

            $hitCount = $page->getHits();
            $hitCount++;
            $page->setHits($hitCount);

            //check for a hit from tracking id
            $countById = $this->em
                ->getRepository('MauticPageBundle:Hit')->getHitCountForTrackingId($page->getId(), $trackingId);
            if (empty($countById)) {
                $uniqueHitCount = $page->getUniqueHits();
                $uniqueHitCount++;
                $page->setUniqueHits($uniqueHitCount);

                $variantHitCount = $page->getVariantHits();
                $variantHitCount++;
                $page->setVariantHits($variantHitCount);
            }

            $this->em->persist($page);

            $hit->setPageLanguage($page->getLanguage());
        }

        //check for existing IP
        $ip = $request->server->get('REMOTE_ADDR');
        $ipAddress = $this->em->getRepository('MauticCoreBundle:IpAddress')
            ->findOneByIpAddress($ip);

        if ($ipAddress === null) {
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($ip, $this->factory->getSystemParameters());
        }

        $hit->setIpAddress($ipAddress);

        //gleam info from the IP address
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        $hit->setCode($code);
        $hit->setReferer($request->server->get('HTTP_REFERER'));
        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));

        //get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if ($pos = strpos(';q=', $l) !== false) {
                    //remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        $pageURL = 'http';
        if ($request->server->get("HTTPS") == "on") {$pageURL .= "s";}
        $pageURL .= "://";
        if ($request->server->get("SERVER_PORT") != "80") {
            $pageURL .= $request->server->get("SERVER_NAME").":".$request->server->get("SERVER_PORT").
                $request->server->get("REQUEST_URI");
        } else {
            $pageURL .= $request->server->get("SERVER_NAME").$request->server->get("REQUEST_URI");
        }
        $hit->setUrl($pageURL);

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_HIT)) {
            $event = new PageHitEvent($hit, $request, $code);
            $this->dispatcher->dispatch(PageEvents::PAGE_ON_HIT, $event);
        }

        $this->em->persist($hit);
        $this->em->flush();

        //save hit to the cookie to use to update the exit time
        setcookie('mautic_referer_id', $hit->getId(), $expire);
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD
     *
     * @param $component null | pageTokens | abTestWinnerCriteria
     *
     * @return mixed
     */
    public function getBuilderComponents($component = null)
    {
        static $components;

        if (empty($components)) {
            $components = array();
            $event      = new PageBuilderEvent($this->translator);
            $this->dispatcher->dispatch(PageEvents::PAGE_ON_BUILD, $event);
            $components['pageTokens']           = $event->getTokenSections();
            $components['abTestWinnerCriteria'] = $event->getAbTestWinnerCriteria();
        }

        return ($component !== null && isset($components[$component])) ? $components[$component] : $components;
    }

    /**
     * Get number of page bounces
     *
     * @param Page $page
     *
     * @return int
     */
    public function getBounces(Page $page)
    {
        return $this->em->getRepository('MauticPageBundle:Hit')->getBounces($page->getId());
    }


    /**
     * Get number of page bounces
     *
     * @param Page $page
     *
     * @return int
     */
    public function getDwellTimeStats(Page $page)
    {
        return $this->em->getRepository('MauticPageBundle:Hit')->getDwellTimes($page->getId());
    }

    /**
     * Get the variant parent/children
     *
     * @param Page $page
     *
     * @return array
     */
    public function getVariants(Page $page)
    {
        $parent = $page->getVariantParent();

        if (!empty($parent)) {
            $children = $parent->getVariantChildren();
        } else {
            $parent   = $page;
            $children = $page->getVariantChildren();
        }

        if (empty($children)) {
            $children = false;
        }

        return array($parent, $children);
    }

    /**
     * Get translation parent/children
     *
     * @param Page $page
     *
     * @return array
     */
    public function getTranslations(Page $page)
    {
        $parent = $page->getTranslationParent();

        if (!empty($parent)) {
            $children = $parent->getTranslationChildren();
        } else {
            $parent   = $page;
            $children = $page->getTranslationChildren();
        }

        if (empty($children)) {
            $children = false;
        }

        return array($parent, $children);
    }

    /**
     * Converts a variant to the main page and the main page a variant
     *
     * @param Page $page
     */
    public function convertVariant(Page $page)
    {
        //let saveEntities() know it does not need to set variant start dates
        $this->inConversion = true;

        list($parent, $children) = $this->getVariants($page);

        $save = array();

        //set this page as the parent for the original parent and children
        if ($parent) {
            if ($parent->getId() != $page->getId()) {
                $parent->setIsPublished(false);
                $page->addVariantChild($parent);
                $parent->setVariantParent($page);
            }

            $parent->setVariantStartDate(null);
            $parent->setVariantHits(0);

            foreach ($children as $child) {
                //capture child before it's removed from collection
                $save[] = $child;

                $parent->removeVariantChild($child);
            }
        }

        if (count($save)) {
            foreach ($save as $child) {
                if ($child->getId() != $page->getId()) {
                    $child->setIsPublished(false);
                    $page->addVariantChild($child);
                    $child->setVariantParent($page);
                } else {
                    $child->removeVariantParent();
                }

                $child->setVariantHits(0);
                $child->setVariantStartDate(null);
            }
        }

        $save[] = $parent;
        $save[] = $page;

        //save the entities
        $this->saveEntities($save, false);
    }
}
