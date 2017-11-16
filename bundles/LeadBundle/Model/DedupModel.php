<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class DedupModel
{
    /**
     * @var FieldModel
     */
    protected $fieldModel;

    /**
     * @var MergeModel
     */
    protected $mergeModel;

    /**
     * @var LeadRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $availableFields;

    /**
     * @var bool
     */
    protected $mergeNewerIntoOlder = false;

    /**
     * DedupModel constructor.
     *
     * @param FieldModel     $fieldModel
     * @param MergeModel     $mergeModel
     * @param LeadRepository $repository
     * @param EntityManager  $entityManager
     */
    public function __construct(FieldModel $fieldModel, MergeModel $mergeModel, LeadRepository $repository, EntityManager $entityManager)
    {
        $this->fieldModel = $fieldModel;
        $this->mergeModel = $mergeModel;
        $this->repository = $repository;
        $this->em         = $entityManager;
    }

    /**
     * @param bool                 $mergeNewerIntoOlder
     * @param OutputInterface|null $output
     */
    public function dedup($mergeNewerIntoOlder = false, OutputInterface $output = null)
    {
        $this->mergeNewerIntoOlder = $mergeNewerIntoOlder;
        $lastContactId             = 0;
        $totalContacts             = $this->repository->getIdentifiedContactCount();
        $progress                  = null;

        if ($output) {
            $progress = new ProgressBar($output, $totalContacts);
        }

        while ($contact = $this->repository->getNextIdentifiedContact($lastContactId)) {
            $lastContactId = $contact->getId();
            $fields        = $contact->getProfileFields();
            $duplicates    = $this->checkForDuplicateContacts($fields);

            if ($progress) {
                $progress->advance();
            }

            // Were duplicates found?
            if (count($duplicates) > 1) {
                $loser = reset($duplicates);
                while ($winner = next($duplicates)) {
                    $this->mergeModel->merge($loser, $winner);

                    if ($progress) {
                        // Advance the progress bar for the deleted contacts that are no longer in the total count
                        $progress->advance();
                    }

                    $loser = $winner;
                }
            }

            // Clear all entities in memory for RAM control
            $this->em->clear();
        }
    }

    /**
     * @param array $queryFields
     * @param bool  $returnWithQueryFields
     *
     * @return array|Lead
     */
    public function checkForDuplicateContacts(array $queryFields)
    {
        $duplicates = [];
        if ($uniqueData = $this->getUniqueData($queryFields)) {
            $duplicates = $this->repository->getLeadsByUniqueFields($uniqueData);

            // By default, duplicates are ordered by newest first
            if (!$this->mergeNewerIntoOlder) {
                // Reverse the array so that oldeset are on "top" in order to merge oldest into the next until they all have been merged into the
                // the newest record
                $duplicates = array_reverse($duplicates);
            }
        }

        return $duplicates;
    }

    /**
     * @param array $queryFields
     *
     * @return array
     */
    protected function getUniqueData(array $queryFields)
    {
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentifierFields();
        $uniqueLeadFieldData = [];
        $inQuery             = array_intersect_key($queryFields, $this->getAvailableFields());
        foreach ($inQuery as $k => $v) {
            if (empty($queryFields[$k])) {
                unset($inQuery[$k]);
            }

            if (array_key_exists($k, $uniqueLeadFields)) {
                $uniqueLeadFieldData[$k] = $v;
            }
        }

        return $uniqueLeadFieldData;
    }

    /**
     * @return array
     */
    protected function getAvailableFields()
    {
        if (null === $this->availableFields) {
            $this->availableFields = $this->fieldModel->getFieldList(
                false,
                false,
                [
                    'isPublished' => true,
                ]
            );
        }

        return $this->availableFields;
    }
}
