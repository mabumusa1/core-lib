<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadfield');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.lead.field.header.index'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', [
    'templateButtons' => [
        'new' => true,
    ],
    'routeBase' => 'contactfield',
    'langVar'   => 'lead.field',
]));
?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', [
        'searchValue'     => $searchValue,
        'action'          => $currentRoute,
        'langVar'         => 'lead.field',
        'routeBase'       => 'contactfield',
        'templateButtons' => [
            'delete' => $permissions['lead:fields:full'],
        ],
    ]); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>