<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EmailSendType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class StageActionChangeType extends AbstractType
{
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('stage', 'stageaction_list', array(
            'label'       => 'mautic.stage.selectstage',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.stage.choose.stage_descr',
            ),
            'multiple'    => false,
            'required'    => true,
            'constraints' => array(
                new NotBlank(
                    array('message' => 'mautic.core.value.required')
                )
            )
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('update_select'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "stageaction_change";
    }
}
