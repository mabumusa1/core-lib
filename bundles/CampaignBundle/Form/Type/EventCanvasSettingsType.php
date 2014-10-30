<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EventCanvasSettingsType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class EventCanvasSettingsType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('droppedX', 'hidden');

        $builder->add('droppedY', 'hidden');

        $builder->add('yes', 'campaignevent_canvassettings_endpoints');
        $builder->add('no', 'campaignevent_canvassettings_endpoints');
        $builder->add('bottom', 'campaignevent_canvassettings_endpoints');
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignevent_canvassettings";
    }
}