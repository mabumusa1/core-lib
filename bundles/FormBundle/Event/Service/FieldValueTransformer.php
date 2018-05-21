<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event\Service;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\SubmissionEvent;

class FieldValueTransformer
{
    /**
     * @param SubmissionEvent $submissionEvent
     * @param Field           $field
     * @param string          $value
     *
     * @return string
     */
    public function transform(SubmissionEvent $submissionEvent, Field $field, $value)
    {
        if ($submissionEvent->getSubmission()->getId()) {
            switch ($field->getType()) {
                case 'file':
                    return $submissionEvent->getRouter()->generate(
                        'mautic_form_file_download',
                        [
                            'submissionId' => $submissionEvent->getSubmission()->getId(),
                            'field'        => $field->getAlias(),
                        ],
                        true
                    );
                    break;
            }
        }

        return $value;
    }
}
