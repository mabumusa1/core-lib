<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", "Dashboard");
$view['slots']->set('mauticContent', 'dashboard');
?>
<div class="box-layout">
    <div class="np col-md-9 height-auto bg-white">
    	<div class="bg-auto bg-dark-xs">
    		<div class="pa-md mb-lg">
    			<div class="row">
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
                            <div class="text-center" style="position:relative; height: 150px; padding: 20px">
                                <canvas id="open-rate" width="110" height="110" data-sent-count="<?php echo $sentReadCount['sentCount'] ?>" data-read-count="<?php echo $sentReadCount['readCount'] ?>"></canvas>
        			            <div style="font-size:x-small;text-align:center;padding:2px;color:#4E5D9D;position:absolute;top:50%;left:50%;margin-left:-26px;margin-top:-15px">Open Rate<br><?php echo $openRate ?>%</div>
    			            </div>
                            <ul class="list-group">
    			                <li class="list-group-item">New Visitors <span class="badge pull-right"><?php echo $newReturningVisitors['new']; ?></span></li>
    			                <li class="list-group-item">Returning Visitors <span class="badge pull-right"><?php echo $newReturningVisitors['returning']; ?></span></li>
    			            </ul>
    			        </div>
    			    </div>
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
    			            <div class="text-center" style="position:relative; height: 150px; padding: 20px">
                                <canvas id="click-rate" width="110" height="110" data-read-count="<?php echo $sentReadCount['readCount'] ?>" data-click-count="<?php echo $sentReadCount['clickCount'] ?>"></canvas>
                                <div style="font-size:x-small;color:#35B4B9;position:absolute;top:50%;left:50%;margin-left:-20px;margin-top:-13px">Click Rate<br><?php echo $clickRate ?>%</div>
                            </div>
    			            <ul class="list-group">
                                <li class="list-group-item">Email Delivered <span class="badge pull-right"><?php echo $allSentEmails; ?></span></li>
                                <li class="list-group-item">Total Click <span class="badge pull-right"><?php echo $sentReadCount['clickCount'] ?></span></li>
    			            </ul>
    			        </div>
    			    </div>
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
                            <div class="text-center pa-20 jumbo-font h150" id="active-visitors" style="text-shadow: #fff 0px 0px 50px;transition: text-shadow 1.2s ease;" >0</div>
                            <ul class="list-group">
                                <li class="list-group-item">Most Visits this Week<span class="badge pull-right"><?php echo $weekVisitors; ?></span></li>
                                <li class="list-group-item">Most Visits all Time <span class="badge pull-right"><?php echo $allTimeVisitors; ?></span></li>
                            </ul>
    			        </div>
    			    </div>
    			</div>
    		</div>
    		<div class="pa-md mb-lg">
    			<div class="row">
    				<div class="col-sm-12">
    					<div id="dashboard-map" style="height: 350px;"></div>
    				</div>
    			</div>
    		</div>
    		<ul class="nav nav-tabs pr-md pl-md">
    	        <li class="active"><a href="#email-stats-container" role="tab" data-toggle="tab">Stats</a></li>
    	        <li class=""><a href="#page-stats-container" role="tab" data-toggle="tab">Inbox</a></li>
    	    </ul>
    	</div>
        <div class="tab-content pa-md">
                <!-- #email-stats-container -->
                <div class="tab-pane active fade in bdr-w-0" id="email-stats-container">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                	<h3 class="panel-title">Most Popular Pages</h3>
                                </div>
                                <?php if ($popularPages) : ?>
                                <div class="panel-body">
                                    <table class="table table-striped">
                                    <tr>
                                        <th>Title</th>
                                        <th>Lang</th>
                                        <th>Hits</th>
                                    </tr>
                                    <?php foreach ($popularPages as $page) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['page_id'])); ?>" data-toggle="ajax">
                                                    <?php echo $page['title']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo $page['lang']; ?>
                                            </td>
                                            <td>
                                                <?php echo $page['hits']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                           	<div class="panel panel-default">
                                <div class="panel-heading">
                                	<h3 class="panel-title">Most Popular Assets</h3>
                                </div>
                                <?php if ($popularAssets) : ?>
                                <div class="panel-body">
                                    <table class="table table-striped">
                                    <tr>
                                        <th>Title</th>
                                        <th>Downloads</th>
                                    </tr>
                                    <?php foreach ($popularAssets as $asset) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $view['router']->generate('mautic_asset_action', array('objectAction' => 'view', 'objectId' => $asset['id'])); ?>" data-toggle="ajax">
                                                    <?php echo $asset['title']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo $asset['downloadCount']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                	<h3 class="panel-title">Most Popular Campaigns</h3>
                                </div>
                                <?php if ($popularCampaigns) : ?>
                                <div class="panel-body">
                                    <table class="table table-striped">
                                    <tr>
                                        <th>Title</th>
                                        <th>Hits</th>
                                    </tr>
                                    <?php foreach ($popularCampaigns as $campaign) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $view['router']->generate('mautic_campaign_action', array('objectAction' => 'view', 'objectId' => $campaign['campaign_id'])); ?>" data-toggle="ajax">
                                                    <?php echo $campaign['name']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo $campaign['hits']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ #email-stats-container -->

                <!-- #page-stats-container -->
                <div class="tab-pane fade bdr-w-0" id="page-stats-container">
                    <div class="pa-md clearfix">
                        <h6 class="pull-left mr-lg"><span class="fa fa-square text-primary mr-xs"></span> Hit</h6>
                        <h6 class="pull-left mr-lg"><span class="fa fa-square text-warning mr-xs"></span> Conversion</h6>
                        <h6 class="pull-left"><span class="fa fa-square text-success mr-xs"></span> View</h6>
                    </div>
                    <ul class="list-group mb-0">
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014 <span>[current]</span> <span>[parent]</span></a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">5729</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">3426</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">354</a>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014</a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">652</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">115</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">342</a>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Copenhagen Conference 2014</a></h5>
                                    <span class="text-white dark-sm">copenhagen-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">943</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">7598</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">551</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <!--/ #page-stats-container -->
            </div>
    </div>
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!--
        we can leverage data from audit_log table
        and build activity feed from it
        -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm">
            <div class="panel-heading">
                <div class="panel-title">Recent Activity</div>
            </div>
            <div class="panel-body pt-sm">
                <ul class="media-list media-list-feed">
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Create <strong class="text-primary">Awesome Campaign Name</strong>
                            <p class="fs-12 text-white dark-sm">Jan 16, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Ima Steward Update <strong class="text-primary">Awesome Campaign Name</strong> action
                            <p class="fs-12 text-white dark-sm">May 1, 2015</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left mt-xs">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Ima Steward Update <strong class="text-primary">Awesome Campaign Name</strong> leads
                            <p class="fs-12 text-white dark-sm">Aug 2, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left">
                            <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Publish <strong class="text-primary">Awesome Campaign Name</strong>
                            <p class="fs-12 text-white dark-sm">Sep 23, 2014</p>
                        </div>
                    </li>
                    <li class="media">
                        <div class="media-object pull-left">
                            <span class="figure"></span>
                        </div>
                        <div class="media-body">
                            Dan Counsell Unpublish <strong class="text-primary">Awesome Campaign Name</strong>
                            <p class="fs-12 text-white dark-sm">Sep 29, 2014</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>