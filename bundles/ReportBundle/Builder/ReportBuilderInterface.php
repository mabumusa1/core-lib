<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Builder;

/**
 * Interface ReportBuilderInterface
 */
interface ReportBuilderInterface
{
    /**
     * Gets the query instance with default parameters
     *
     * @param array $options Options array
     *
     * @return \Doctrine\ORM\Query
     */
    public function getQuery(array $options);
}
