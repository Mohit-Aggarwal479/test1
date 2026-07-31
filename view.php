<?php function showRecordsListing(&$rows)
{
	global $component, $database, $pagingObject, $page;

	$totalRecords = count($rows);
	//print_r($totalRecords); exit;

	if ($_GET['pageNo'] > 1) {
		$page = $_GET['pageNo'];
	} else {
		$page = 1;
	}
	if ($page != 1) {
		$srno = (PAGELIMIT * $page) - PAGELIMIT;
	} else {
		$srno = 0;
	}

	$sqlmenuid = "select * from tbl_components where component_option='" . $database->filter($_GET['c']) . "'";
	$getmenuid = $database->get_results($sqlmenuid);
	$menuid = $getmenuid[0];
	$sqlpermission = "select * from tbl_rights_groups where rights_group_id='" . $database->filter($_SESSION['admin_groupid']) . "' and rights_menu_id='" . $database->filter($menuid['component_id']) . "'";
	$permissions = $database->get_results($sqlpermission);
	$permission = $permissions[0];
	//print_r($permission); exit;
?>
	<form name="adminForm" action="?c=<?php echo $component ?>" method="get">
		<div class="main-content">
			<div class="container">

				<?php
				$sql = "SELECT cto.* FROM tbl_cto_cho cto, tbl_users u where cto.created_by = u.user_id and cto.view_status = 1";
				$admin_groupid = explode(",", $_SESSION['admin_groupid']);
				$where = "";
				if (in_array(2, $admin_groupid)) {
					$where .= " AND (cto.created_by = '" . $database->filter($_SESSION['admin_user_id']) . "' OR cto.higher_authority_id = '" . $database->filter($_SESSION['admin_user_id']) . "' OR cto.officer_user_id = '" . $database->filter($_SESSION['admin_user_id']) . "')";
				} else {
					if (in_array(1, $admin_groupid)) {
						$where .= " ";
					}
				}

				if ($where != "") {
					$sql = $sql . $where;
				}
				$totaltransferin = $database->get_results($sql);

				$totaltransferin_approved = $database->get_results($sql . " AND (cto.status = '2' AND cto.status != 5) ");
				$totaltransferin_pending = $database->get_results($sql . " AND (cto.status = '0' OR cto.status = '1') AND cto.status != 5");
				//$totaltransferin_rejected = $database->get_results($sql . " AND (cto.status = '2')");
				$totaltransferin_withdrawn = $database->get_results($sql . " AND (cto.status = '12' AND cto.status != 5) ");



				if (in_array(2, $admin_groupid)) {
					$totaltransferin_action = $database->get_results($sql . " AND (cto.stage = '1'
					OR cto.stage = '5' OR cto.stage = '2') and cto.status != 12 AND cto.status != 5");
				}
				?>



				<div class="row">
					<div class="col-md-6">
						<h3>Handing over/Taking over of charge</h3>
					</div>
					<div class="col-md-2">
						<?php
						$admin_groupid = $_SESSION['admin_groupid'];
						$groupIds = explode(',', $admin_groupid);
						$whereParts = [];
						foreach ($groupIds as $gid) {
							$gid = trim($gid);
							$whereParts[] = "FIND_IN_SET('$gid', roles)";
						}
						$sqldoc = "SELECT * FROM tbl_module_checklist WHERE module_id = 44 AND status = 1 AND (" . implode(' OR ', $whereParts) . ") ";
						$resultsdoc = $database->get_results($sqldoc); ?>

						<div class="dropdown">
							<a href="#" class="btn btn-primary" data-toggle="dropdown" aria-expanded="false">
								Select Check List
								<i style="padding-left: 8px" class="feather feather-chevron-down"></i>
							</a>
							<ul class="dropdown-menu dropdown-menu-right" role="menu">
								<?php
								if (!empty($resultsdoc)) {
									foreach ($resultsdoc as $row) {
										// Decode the serialized & base64-encoded arrays
										$titles = unserialize(base64_decode($row['title']));
										$documents = unserialize(base64_decode($row['document']));

										if (!empty($titles)) {
											foreach ($titles as $i => $title) {
												$doc = isset($documents[$i]) ? $documents[$i] : '';
												if (!empty($doc)) {
								?>
													<li>
														<a href="<?php echo URL . 'images/module-checklist/processing.php?file=' . htmlspecialchars($doc); ?>" target="_blank">
															<?php echo htmlspecialchars($title); ?>
														</a>
													</li>
								<?php
												}
											}
										}
									}
								}
								?>
							</ul>
						</div>
					</div>
					<div class="col-md-2">
						<?php
						$admin_groupid = explode(",", $_SESSION['admin_groupid']);
						if (in_array(2, $admin_groupid)) { ?>
							<a class="btn btn-primary" href="index.php?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>&task=add" style="margin-left:10px;"><i class="icon-plus"></i> Add Request</a>
						<?php } ?>
					</div>


					<div class="col-md-2">
						<div class="row">
							<a href="export/cho_cto.php" class="btn" target="_blank" style="color: #fff!important; background-color: #2E8B57; border-color: #2E8B57; padding: 5px 0 5px 0px;     margin-left: 40px; margin-top: -3px;"><img src="<?php echo icon_excel(); ?>"
									style="max-width: 23%; margin-right: 5px;">Export</a>
						</div>
					</div>
				</div>

				<div style="height:20px;"></div>
				<div class="row">
					<div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?c=cho_cto">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-7">
											<div class="mt-0 text-left">
												<h5 class="">Total Requests</h5>
												<h3 class="mb-0 mt-1 text-primary  fs-25"><?= count($totaltransferin) ?></h3>
											</div>
										</div>
										<div class="col-5">
											<div class="icon1 bg-primary-transparent my-auto  float-right"> <i class="feather feather-clipboard"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>
					<div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?status=Charge Accepted&c=cho_cto&hidCheckedBoxes=0">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-7">
											<div class="mt-0 text-left">
												<h5 class="">Charge Accepted</h5>
												<h3 class="mb-0 mt-1 text-success fs-25"><?= count($totaltransferin_approved) ?></h3>
											</div>
										</div>
										<div class="col-5">
											<div class="icon1 bg-success-transparent my-auto  float-right"> <i class="feather feather-check"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>

					<!-- <div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?status=Sent Back&c=cho_cto&hidCheckedBoxes=0">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-7">
											<div class="mt-0 text-left">
												<h5 class="">Sent Back</h5>
												<h3 class="mb-0 mt-1 text-orange fs-25"><?= count($totaltransferin_rejected) ?></h3>
											</div>
										</div>
										<div class="col-5">
											<div class="icon1 bg-orange-transparent my-auto  float-right"> <i class="feather feather-check"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div> -->


					<div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?status=Withdrawn&c=cho_cto&hidCheckedBoxes=0">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-7">
											<div class="mt-0 text-left">
												<h5 class="">Withdrawn Requests</h5>
												<h3 class="mb-0 mt-1 text-warning fs-25"><?= count($totaltransferin_withdrawn) ?></h3>
											</div>
										</div>
										<div class="col-5">
											<div class="icon1 bg-warning-transparent my-auto  float-right"> <i class="feather feather-check"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>

					<div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?dashboard=1&c=cho_cto&hidCheckedBoxes=0">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-8">
											<div class="mt-0 text-left">
												<h5 class="">Pending/In Process Requests</h5>
												<h3 class="mb-0 mt-1 text-warning  fs-25"><?= count($totaltransferin_pending) ?></h3>
											</div>
										</div>
										<div class="col-4">
											<div class="icon1 bg-secondary-transparent my-auto  float-right"> <i class="feather feather-info"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>





					<div class="col-md-4">
						<div class="card">
							<a href="<?php echo URL ?>admin/?status_action=1&c=cho_cto&hidCheckedBoxes=0">
								<div class="card-body" style="padding:10px;">
									<div class="row">
										<div class="col-8">
											<div class="mt-0 text-left">
												<h5 class="">Action to be taken</h5>
												<h3 class="mb-0 mt-1 text-orange  fs-25"><?= count($totaltransferin_action) ?></h3>
											</div>
										</div>
										<div class="col-4">
											<div class="icon1 bg-orange-transparent my-auto  float-right"> <i class="feather feather-info"></i> </div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>



				</div>


				<div class="row">
					<div class="col-lg-12">
						<div class="card">
							<div class="card-body">
								<div class="row">

									<div class="col-md-2">
										<div class="row">
											<div class="col-md-12"><label class="form-label" style="line-height:36px;">Request ID </label></div>
											<div class="col-md-12">
												<div class="form-group w-100">
													<div class="input-icon">
														<span class="input-icon-addon">
															<i class="fe fe-search"></i>
														</span>
														<input type="text" class="form-control" name="reqt_id" placeholder="Request ID" value="<?= isset($_GET['reqt_id']) ? $_GET['reqt_id'] : '' ?>">
													</div>
												</div>
											</div>
										</div>
									</div>

									<div class="col-md-4">
										<div class="col-md-12"><label class="form-label" style="line-height:36px;">Charge Taking Officer Name</label>
										</div>
										<div class="form-group w-100">
											<div class="input-icon">
												<span class="input-icon-addon">
													<i class="fe fe-search"></i>
												</span>
												<input type="text" class="form-control" name="officer_name" placeholder="Applicant Name" value="<?= isset($_GET['officer_name']) ? $_GET['officer_name'] : '' ?>">
											</div>
										</div>
									</div>

									<div class="col-md-4">
										<div class="col-md-12"><label class="form-label" style="line-height:36px;">Charge to be Handover</label>
										</div>
										<div class="form-group w-100">
											<div class="input-icon">
												<span class="input-icon-addon">
													<i class="fe fe-search"></i>
												</span>
												<input type="text" class="form-control" name="charge_handover" placeholder="Applicant Name" value="<?= isset($_GET['charge_handover']) ? $_GET['charge_handover'] : '' ?>">
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<div class="col-md-12"><label class="form-label" style="line-height:36px;"> From </label>
											</div>
											<div class="col-md-12">
												<div class="form-group w-100">
													<div class="input-icon">
														<input class="form-control" type="date" placeholder="From" name="datafrom" value="<?= @$_GET['datafrom'] ?>">
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<div class="col-md-12"><label class="form-label" style="line-height:36px;"> To </label>
											</div>
											<div class="col-md-12">
												<div class="form-group w-100">
													<div class="input-icon">
														<input class="form-control" type="date" placeholder="To" name="datato" value="<?= @$_GET['datato'] ?>">
													</div>
												</div>
											</div>
										</div>
									</div>





	<div class="col-md-2">
										<div class="row">
											<div class="col-md-12"><label class="form-label" style="line-height:36px;">CHO HRMS Code </label></div>
											<div class="col-md-12">
												<div class="form-group w-100">
													<div class="input-icon">
														<span class="input-icon-addon">
															<i class="fe fe-search"></i>
														</span>
														<input type="text" class="form-control" name="cho_hrms" placeholder="CHO HRMS Code" value="<?= isset($_GET['cho_hrms']) ? $_GET['cho_hrms'] : '' ?>">
													</div>
												</div>
											</div>
										</div>
									</div>


	<div class="col-md-2">
										<div class="row">
											<div class="col-md-12"><label class="form-label" style="line-height:36px;">CTO HRMS Code </label></div>
											<div class="col-md-12">
												<div class="form-group w-100">
													<div class="input-icon">
														<span class="input-icon-addon">
															<i class="fe fe-search"></i>
														</span>
														<input type="text" class="form-control" name="cto_hrms" placeholder="CTO HRMS Code" value="<?= isset($_GET['cto_hrms']) ? $_GET['cto_hrms'] : '' ?>">
													</div>
												</div>
											</div>
										</div>
									</div>

									<div class="col-md-2">
										<div class="col-md-12"><label class="form-label" style="line-height:36px;">Status</label>
										</div>
										<div class="form-group w-100">
											<div class="input-icon">
												<select name="status" class="form-control">
													<option value="">All</option>
													<option value="Pending" <?= @$_GET['status'] == "Pending" ? "selected" : "" ?>>Pending</option>
													<option value="Charge Accepted" <?= @$_GET['status'] == "Charge Accepted" ? "selected" : "" ?>>Charge Accepted</option>
													<option value="In Process" <?= @$_GET['status'] == "In Process" ? "selected" : "" ?>>In Process</option>
													<option value="Withdrawn" <?= @$_GET['status'] == "Withdrawn" ? "selected" : "" ?>>Withdrawn</option>
													<option value="Cancelled" <?= @$_GET['status'] == "Cancelled" ? "selected" : "" ?>>Cancelled</option>


												</select>
											</div>
										</div>
									</div>



									<div class="col col-auto mb-4">
										</br>
										</br>
										<div class="input-group">
											<input type="submit" class="btn btn-primary" value="Search" align="center" style="width:70px;" onclick="javascript:document.adminForm.submit();">
										</div>
									</div>


								</div>
							</div>
							<div class="e-table">
								<div class="table-responsive table-lg">
									<table class="table card-table table-vcenter text-nowrap border" id="example1">
										<thead>
											<tr>
												<th>Request ID</th>
												<th>Charge to be Handover</th>
												<th>Charge taking Officer HRMS</th>
												<th>Charge taking Officer Name</th>
												<th>Charge taking Officer Designation</th>
												<th>Created Date</th>
												<th>Status</th>
												<th>Action</th>
											</tr>

										</thead>
										<tbody>
											<?php
											if ($totalRecords > 0) {
												for ($i = 0; $i < $totalRecords; $i++) {
													$srno++;
													$row = &$rows[$i];

													$sqluser = "select * from tbl_users where user_id='" . $database->filter($row['created_by']) . "' AND user_status = 1";
													$user = $database->get_results($sqluser);

													$sqlauthority = "select * from tbl_users where user_id='" . $database->filter($row['officer_user_id']) . "' AND user_status = 1";
													$authority = $database->get_results($sqlauthority);

													$officerfer = "select * from tbl_users where user_id='" . $database->filter($row['higher_authority_id']) . "' AND user_status = 1";
													$officerferdetail = $database->get_results($officerfer);

													$designation_sql1 = "SELECT * FROM tbl_designation where designation_id = '" . $database->filter($authority[0]['current_designation']) . "'";
													$designation1 = $database->get_results($designation_sql1);


													$designation_sql = "SELECT * FROM tbl_designation where designation_name = '" . $database->filter($row['officer_designation']) . "'";
													$designation = $database->get_results($designation_sql);

													$designation_sql11 = "SELECT * FROM tbl_designation where designation_id = '" . $database->filter($row['des_id']) . "'";
													$designation11 = $database->get_results($designation_sql11);
													$created_designation =  $designation11[0]['designation_name'];

													$offfice_sql = $database->get_results("SELECT * FROM tbl_establishment_office WHERE e_office_id = '" . $database->filter($row['emp_office']) . "'");

													$sqllogs = "select * from tbl_cto_cholog where request_id = '" . $database->filter($row['id']) . "' ORDER BY id DESC";

													$logs = $database->get_results($sqllogs);
													$log_html = '<table><tbody>';
													$action_by = "";
													$log_total = count($logs);
													$log_count = 1;
													foreach ($logs as $log) {

														$log_html .= '<tr><td><b>' . date("d/m/Y H:i", strtotime($log['updated_at'])) . ':</b><br>' . $log['action'];
														if (isset($log['file']) && $log['file'] != "") {

															$logdata = explode(",", $log['file']);
															$logcount = (count($logdata));
															$log_count = 1;
															for ($j = 0; $j < $logcount; $j++) {
																$log_html .= "<a  href='" . URL . "images/cto_cho/" . $logdata[$j] . "' ><b>" . $log_count . ". View File</b></a>";
																$log_count++;
															}
														}
														$log_html .= '</td></tr>';
														if ($log_count == 1) {
															$action_by = $log['updated_by'];
														}
														$log_count++;
													}
													$log_html .= '</tbody></table>';





													if ($row['upload_court_case'] != "") {
														$upload_court_case = "<span><a style='color: blue;' href='" . URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] . "' >View File</a></span>";
													} else {
														$upload_court_case = '';
													}


													if (isset($row['created_by_group']) && $row['created_by_group'] == 7) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=6' ><b style='color:blue;'>View</b></a>";
													} else if (isset($row['created_by_group']) && $row['created_by_group'] == 67) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=7' ><b style='color:blue;'>View</b></a>";
													} else if (isset($row['created_by_group']) && $row['created_by_group'] == 60) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=8' ><b style='color:blue;'>View</b></a>";
													} else if (isset($row['created_by_group']) && $row['created_by_group'] == 79) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=9' ><b style='color:blue;'>View</b></a>";
													} else if (isset($row['created_by_group']) && $row['created_by_group'] == 8) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=10' ><b style='color:blue;'>View</b></a>";
													} else if (isset($row['created_by_group']) && $row['created_by_group'] == 58) {
														$xen = "<a target='_blank' href='" . URL . "/admin/index.php?c=" . $component . "&task=edit&Cid=" . $menuid['component_headingid'] . "&id=" . $row['id'] . "&type=11' ><b style='color:blue;'>View</b></a>";
													} else {
														$xen = 'N/A';
													}


													/* pendency start*/
													if (($row['status_text'] == 'In Process') || ($row['status_text'] == 'Pending')) {
														if ($row['stage'] == 0) {
															$current_login = 'Request Pending With Employee-' . $user[0]['name'] . '(' . $user[0]['user_code'] . ') ' . ' [ Ph:- ' . $user[0]['telephone1'] . ' ]';
														} elseif ($row['stage'] == 1 && $row['sendback'] == 'CTO') {
															$current_login = 'Request Pending With CTO-' . $authority[0]['name'] . '(' . $authority[0]['user_code'] . ') ' . ' [ Ph:- ' . $authority[0]['telephone1'] . ' ]';
														} else if ($row['stage'] == 5 && $row['sendback'] == 'CTO') {
															// $current_login = 'Request Pending With DDO-' . $user_name2 . '(' . $user_code2 . ')';
															$current_login = 'Request Pending With CTO-' . $authority[0]['name'] . '(' . $authority[0]['user_code'] . ') ' . ' [ Ph:- ' . $authority[0]['telephone1'] . ' ]';
														} elseif ($row['stage'] == 4 && $row['sendback'] == 'HO') {
															$current_login = 'Request Pending With HO-' . $officerferdetail[0]['name'] . '(' . $officerferdetail[0]['user_code'] . ') ' . ' [ Ph:- ' . $officerferdetail[0]['telephone1'] . ' ]';
														} else {
															$current_login = '---';
														}
													} else {
														$current_login = '---';
													}
													/*pendency at end*/


													if ($row['profile_check'] == 1) {
														$profile_check = "<span class='glyphicon glyphicon-ok' style='color:blue;'></span><span><b> Profile Verified by Employee.</b></span>";
													} else {
														$profile_check = " ";
													}
											?>
													<tr>
														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1">
																	<?php $admin_groupid = explode(",", $_SESSION['admin_groupid']);
																	if (in_array(24, $admin_groupid)) { ?>
																		<a class="btn btn-danger btn-icon btn-sm" data-toggle="tooltip" data-original-title="Delete" onClick="getValue<?php echo $i ?>();"><i class="feather feather-trash-2"></i></a>

																		<script>
																			function getValue<?php echo $i ?>() {
																				let message = prompt("Please enter your reason to be deleted");
																				if (message != "" && message != null) {
																					window.location.href = "?c=<?php echo $component ?>&task=remove&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&msg=" + message;

																				}

																			}
																		</script>

																		<?php  //}
																		// if ($permission['rights_edit'] == 1) { 
																		?>
																		<!-- <a href="?c=<?php echo $component ?>&task=edit&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>"><?php echo "CHO-" . $row['id']; ?>
																		</a> -->
																	<?php } else { ?>
																		<a href="javascript:void(0);" class="detailopener" data-log="<?= $log_html ?>" data-usercode="<?= $authority[0]['user_code'] ?>" data-username="<?= $authority[0]['name'] ?>" data-designation="<?= $designation1[0]['designation_name'] ?>" data-charge_handover="<?= $row['charge_handover'] ?>" data-id="<?php echo "CHO-" . $row['id']; ?>" data-officer_hrms_code="<?= $row['officer_hrms_code'] ?>" data-officer_name="<?= $row['officer_name'] ?>" data-officer_designation="<?= $designation[0]['designation_name'] ?>" data-pending_revenue_own_office="<?= $row['pending_revenue_own_office'] ?>" data-pending_revenue_subordinate_office="<?= $row['pending_revenue_subordinate_office'] ?>" data-pending_uncommand_cases="<?= $row['pending_uncommand_cases'] ?>" data-pending_site_inspection="<?= $row['pending_site_inspection'] ?>"
																			data-done_site_inspection="<?= $row['done_site_inspection'] ?>" data-vetted_reply_submitted="<?= $row['vetted_reply_submitted'] ?>" data-vetted_reply_pending="<?= $row['vetted_reply_pending'] ?>" data-tp_register="<?= $row['tp_register'] ?>" data-outlet_notebook="<?= $row['outlet_notebook'] ?>" data-upload_court_case="<?= $upload_court_case ?>"
																			data-user_code="<?= $user[0]['user_code']; ?>"
																			data-name="<?= $row['emp_name'] ?>"
																			data-ms_created_at="<?php echo date('d-m-Y', strtotime($row['created_at'])); ?>"
																			data-xen="<?= $xen; ?>" data-profile_check="<?= $profile_check; ?>"
																			data-emp_office="<?= $offfice_sql[0]['e_office_name']; ?>"
																			data-created_designation="<?php echo $created_designation; ?>"
																			data-current_login="<?= $current_login ?>"
																			data-status="<?= $row['status_text'] ?>"><?php echo "CHO-" . $row['id']; ?>
																		</a>
																	<?php } ?>
																</div>
															</div>
														</td>
														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1"><?php echo $row['charge_handover']; ?></div>
															</div>
														</td>

														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1"><?php echo $row['officer_hrms_code']; ?></div>
															</div>
														</td>

														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1"><?php if (!empty($user)) {
																						echo $row['officer_name'];
																					} ?></div>
															</div>
														</td>




														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1"><?php echo $designation[0]['designation_name']; ?></div>
															</div>
														</td>


														<td class="align-middle">
															<div class="d-flex">
																<div class="mt-1"><?php echo date("d-m-Y", strtotime($row['created_at'])); ?></div>
															</div>
														</td>

														<td class="align-middle">

															<?php
															switch ($row['status_text']) {
																case "Charge Accepted":
																	$class = "success";
																	break;
																case "Sent Back":
																case "Cancelled":
																	$class = "danger";
																	break;
																case "Withdrawn":
																	$class = "warning";
																	break;
																default:
																	$class = "light";
																	break;
															}
															?>
															<span class="badge badge-<?= $class ?>"><?= $row['status_text'] ?: "Pending" ?></span>
														</td>

														<?php
														$admin_groupid = explode(",", $_SESSION['admin_groupid']);
														?>

														<td class="align-middle">
															<?php
															if (in_array(2, $admin_groupid)) {

																if ($row['created_by'] == $_SESSION['admin_user_id']) {
																	if ($row['stage'] == 0 && $row['status'] != 12 && $row['status'] != 5) {
															?>
																		<div class="d-flex">
																			<a
																				href="?c=<?php echo $component; ?>&task=edit&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=1">
																				<span class="badge badge-primary"
																					style="margin-right:10px;">Forward To CTO</span>
																			</a>
																		</div>
																		<br />
																		<div class="d-flex">
																			<a
																				href="?c=<?php echo $component; ?>&task=edit&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=2">
																				<span class="badge badge-primary"
																					style="margin-right:10px;">Forward To Higher Authority</span>
																			</a>
																		</div>
																		<br />

																		<div class="d-flex">
																			<a
																				href="?c=<?php echo $component; ?>&task=edit&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&stage=<?= $row['stage'] ?>">
																				<span class="badge badge-primary"
																					style="margin-right:10px;">Edit Form</span>
																			</a>
																		</div>

																		<br />
																		<div class="d-flex">
																			<a class="btn11356" href="index.php?c=<?php echo $component ?>&task=withdrawn&Cid=<?php echo $menuid['component_headingid']; ?>&request_id=<?php echo $row['id']; ?>&stage=<?php echo $row['stage']; ?>"><span class="badge badge-warning"
																					style="margin-right:10px;">Withdraw Application</span></a>
																		</div>
																	<?php
																	} elseif ($row['status'] != 2 && $row['status'] != 12 && $row['status'] != 5) { ?>





																	<?php }
																}
																if ($row['officer_user_id'] == $_SESSION['admin_user_id']) {
																	if ($row['stage'] == 1 && $row['sendback'] == 'CTO' && $row['status'] != 12 && $row['status'] != 5) {
																	?>
																		<div class="d-flex">
																			<a class="ev_remarkmodalopener" href="javascript:void(0);"
																				data-href="<?= URL ?>/admin/index.php?c=<?php echo $component ?>&task=updatestatus&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=3"><span
																					class="badge badge-primary"
																					style="float: left;width: 100%;">Accept Charge</span>
																			</a>
																		</div>
																		<br />
																		<div class="d-flex">
																			<a class="ev_remarkmodalopener" href="javascript:void(0);"
																				data-href="<?= URL ?>/admin/index.php?c=<?php echo $component ?>&task=updatestatus&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=4&stage=<?= $row['stage'] ?>"><span
																					class="badge badge-primary"
																					style="float: left;width: 100%;">Sendback To CHO</span>
																			</a>
																		</div>
																	<?php
																	} else if ($row['stage'] == 5 && $row['sendback'] == 'CTO' && $row['status'] != 12 && $row['status'] != 5) {
																	?>
																		<div class="d-flex">
																			<a class="ev_remarkmodalopener" href="javascript:void(0);"
																				data-href="<?= URL ?>/admin/index.php?c=<?php echo $component ?>&task=updatestatus&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=3"><span
																					class="badge badge-primary"
																					style="float: left;width: 100%;">Accept Charge</span>
																			</a>
																		</div>
																		<br />
																		<div class="d-flex">
																			<a class="ev_remarkmodalopener" href="javascript:void(0);"
																				data-href="<?= URL ?>/admin/index.php?c=<?php echo $component ?>&task=updatestatus&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=13&stage=<?= $row['stage'] ?>"><span
																					class="badge badge-primary"
																					style="float: left;width: 100%;">Sendback To Higher Authority</span>
																			</a>
																		<?php
																	}
																}
																if ($row['higher_authority_id'] == $_SESSION['admin_user_id']) {
																	if ($row['stage'] == 4 && $row['sendback'] == 'HO' && $row['status'] != 12 && $row['status'] != 5) {
																		?>
																			<div class="d-flex">
																				<a
																					href="?c=<?php echo $component; ?>&task=edit&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=1&stage=<?= $row['stage'] ?>&back=<?= $row['sendback'] ?>">
																					<span class="badge badge-primary"
																						style="margin-right:10px;">Assign CTO</span>
																				</a>
																			</div>
																			<br>
																			<div class="d-flex">
																				<a class="ev_remarkmodalopener" href="javascript:void(0);"
																					data-href="<?= URL ?>/admin/index.php?c=<?php echo $component ?>&task=updatestatus&Cid=<?php echo $menuid['component_headingid']; ?>&id=<?php echo $row['id']; ?>&type=5&stage=<?= $row['stage'] ?>"><span
																						class="badge badge-primary"
																						style="float: left;width: 100%;">Sendback To CHO</span>
																				</a>
																			</div>
																<?php
																	}
																}
															}
																?>
														</td>
													</tr>
												<?php
												}
											} else {
												?>

												<td class="align-middle">No record found</td>

											<?php
											}

											?>
										</tbody>
									</table>
									<?php
									$pagingObject->displayLinks_Front();
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="Cid" value="<?php echo $_GET['Cid'] ?>" />
			<input type="hidden" name="c" value="<?php echo $component ?>" />
			<input type="hidden" name="hidCheckedBoxes" value="0" />
	</form>
	<div class="modal fade show" id="detailsmodal" style="display: none; background:rgba(0,0,0,0.5)" aria-modal="true" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Department of Water Resources, Punjab - Request Details</h5>
					<a href="javascript:void(0);" class="btn btn-primary modalcloser" data-bs-dismiss="modal"><i class="fa fa-close"></i></a>
				</div>
				<div class="modal-body">
					<div class="modal-body" style="overflow: scroll;height: 500px;">
						<div class="table-responsive">
							<div class="row font-weight-semibold" style="color:blue" id="pending">
								<div class="col-md-12">
									<div class="current_login"></div>
								</div>
							</div><br>
							<table class="table mb-0">
								<tbody>
									<tr>
										<td class="font-weight-semibold">Request Id</td>
										<td>:</td>
										<td class="id"></td>
									</tr>
									<tr>
										<td class="font-weight-semibold">Name of Division</td>
										<td>:</td>
										<td class="emp_office"></td>
									</tr>
									<tr>
										<th colspan="3">
											<h3>Charge Handling officer Details</h3>
										</th>
									</tr>
									<tr>
										<td class="font-weight-semibold">HRMS Code</td>
										<td>:</td>
										<td class="user_code"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Name</td>
										<td>:</td>
										<td class="ms_username"></td>
									</tr>
									<tr>
										<td class="font-weight-semibold">Charge to be Handover</td>
										<td>:</td>
										<td class="charge_handover"></td>
									</tr>
									<tr>
										<td class="font-weight-semibold">Designation</td>
										<td>:</td>
										<td class="created_designation"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Officer Application</td>
										<td>:</td>
										<td class="xen"></td>
									</tr>



									<tr style="margin-top:20px;">
										<td></td>
									</tr>
									<tr>
										<th colspan="3" style="margin-top:40px;"><b style="margin-top:15px; font-size:20px;">Charge Taking Officer Detail</b></th>
									</tr>


									<tr>
										<td class="font-weight-semibold">HRMS Code</td>
										<td>:</td>
										<td class="officer_hrms_code"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Name</td>
										<td>:</td>
										<td class="officer_name"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Designation</td>
										<td>:</td>
										<td class="officer_designation"></td>
									</tr>



									<!-- <tr style="margin-top:20px;">
										<td></td>
									</tr>
									<tr>
										<th colspan="3" style="margin-top:40px;"><b style="margin-top:15px; font-size:20px;">Pending Revenue/ Warabandi/ Tawan/ Breach Cases</b></th>
									</tr>


									<tr>
										<td class="font-weight-semibold">Own Office</td>
										<td>:</td>
										<td class="pending_revenue_own_office"></td>
									</tr>



									<tr>
										<td class="font-weight-semibold">Subordinate Office</td>
										<td>:</td>
										<td class="pending_revenue_subordinate_office"></td>
									</tr>

									<tr style="margin-top:20px;">
										<td></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Pending Command to Uncommand Cases</td>
										<td>:</td>
										<td class="pending_uncommand_cases"></td>
									</tr>



									<tr style="margin-top:20px;">
										<td></td>
									</tr>
									<tr>
										<th colspan="3" style="margin-top:40px;"><b style="margin-top:15px; font-size:20px;">Site Inspection of Revenue/ Warabandi/ Tawan/ Breach Cases</b></th>
									</tr>


									<tr>
										<td class="font-weight-semibold">Pending</td>
										<td>:</td>
										<td class="pending_site_inspection"></td>
									</tr>



									<tr>
										<td class="font-weight-semibold">Done But Report is Pending</td>
										<td>:</td>
										<td class="done_site_inspection"></td>
									</tr>


									<tr style="margin-top:20px;">
										<td></td>
									</tr>
									<tr>
										<th colspan="3" style="margin-top:40px;"><b style="margin-top:15px; font-size:20px;">Detail of Court Cases</b></th>
									</tr>


									<tr>
										<td class="font-weight-semibold">Uploaded Document</td>
										<td>:</td>
										<td class="upload_court_case"></td>
									</tr>



									<tr>
										<td class="font-weight-semibold">Vetted Reply Submitted</td>
										<td>:</td>
										<td class="vetted_reply_submitted"></td>
									</tr>



									<tr>
										<td class="font-weight-semibold">Vetted Reply Pending</td>
										<td>:</td>
										<td class="vetted_reply_pending"></td>
									</tr>


									<tr style="margin-top:20px;">
										<td></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">T & P Register/Stock Register</td>
										<td>:</td>
										<td class="tp_register"></td>
									</tr>
									<tr style="margin-top:20px;">
										<td></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Outlet Notebook</td>
										<td>:</td>
										<td class="outlet_notebook"></td>
									</tr>



									<tr style="margin-top:20px;">
										<td></td>
									</tr> -->
									<tr>
										<th colspan="3" style="margin-top:40px;"><b style="margin-top:15px; font-size:20px;">Higher Authority/ Officer</b></th>
									</tr>


									<tr>
										<td class="font-weight-semibold">HRMS Code</td>
										<td>:</td>
										<td class="usercode"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Name</td>
										<td>:</td>
										<td class="username"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Designation</td>
										<td>:</td>
										<td class="designation"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Created On</td>
										<td>:</td>
										<td class="ms_created_at"></td>
									</tr>

									<tr>
										<td class="font-weight-semibold">Status</td>
										<td>:</td>
										<td class="status"></td>
									</tr>
									<tr>
										<td colspan="3" class="profile_check"></td>
									</tr>

								</tbody>
							</table>

							<?php //$admin_groupid = explode(",",$_SESSION['admin_groupid']);
							// if(!in_array(55,$admin_groupid)) { 
							?>

							<div class="ev_logs">
								<div class="container">
									<div class="row" style="background: #f1f1f1;">
										<div class="col-lg-12">
											<div class="card">
												<div class="card-body" style="padding: 18px 5px  0 5px;background: #f1f1f1;">
													<h4 style="margin-bottom: 10px;">Logs</h4>
													<div class="ev_log_data">
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<?php //}
							?>

						</div>
					</div>
				</div>
				<div class="modal-footer"> <a href="javascript:void(0);" class="btn btn-primary modalprint" data-bs-dismiss="modal">Print</a><a href="javascript:void(0);" class="btn btn-primary modalcloser" data-bs-dismiss="modal">Close</a>
				</div>
			</div>
		</div>
	</div>


<?php } ?>



<?php function showwithdrawnForm(&$rows)
{

	$row = array();
	global $component, $database;
	$row = &$rows[0];
	$sqlmenuid = "select * from tbl_components where component_option='" . $database->filter($_GET['c']) . "'";
	$getmenuid = $database->get_results($sqlmenuid);
	$menuid = $getmenuid[0];
	//print_r($row['user_id']);
	//echo $_GET['ola_id']; exit;
?>

	<div class="main-content">
		<div class="container">
			<div class="page-header d-xl-flex d-block">
				<div class="page-leftheader">

					<h4 class="page-title">Withdraw Request : Add</h4>

				</div>
			</div>
			<style>
				.forrevalidate {
					display: none;
				}
			</style>
			<div class="row">
				<div class="col-xl-12 col-md-12 col-lg-12">
					<div class="card">
						<div class="card-body">
							<?php

							$task = "savewithdrawn";
							?>
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<form name="user-form" id="user-form" action="?c=<?php echo $component ?>&task=<?php echo $task; ?>" method="post" class="form-horizontal" enctype="multipart/form-data">
										<div class="row">

											<div class="col-md-12">
												<div class="form-group">
													<label class="form-label">Are you sure, You want to withdraw the request.(If you withdraw the request, your request will be cancelled. )</label>
													<div class="custom-controls-stacked d-md-flex">
														<label class="custom-control custom-radio mr-4">
															<input type="radio" class="custom-control-input" name="withdraw_request" value="Yes" <?= @$row['withdraw_request'] == "Yes" ? 'checked="checked"' : '' ?> required>
															<span class="custom-control-label">Yes</span>
														</label>
														<label class="custom-control custom-radio">
															<input type="radio" onclick="window.location='?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>'" class="custom-control-input" name="withdraw_request" value="No" <?= @$row['withdraw_request'] == "No" ? 'checked="checked"' : '' ?>>
															<span class="custom-control-label" required>No</span>
														</label>
													</div>
												</div>
											</div>


											<div class="col-md-12">
												<br />
												<div class="form-group">
													<label class="form-label">Remark</label>
													<textarea placeholder="Message" name="remarkmessage" style="width: 100%;min-height: 150px;background: #eee;border: none;padding: 12px;resize: none;" required></textarea>
												</div>
											</div>



										</div>
										<div class="col-md-12">
											<div class="card-footer text-right">
												<button type="submit" class="btn btn-lg btn-primary">Submit</button>
												<button type="button" class="btn btn-lg btn-danger" onclick="window.location='?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>'">Cancel</button>
											</div>
										</div>
										<input type="hidden" name="CId" value="<?php echo $_GET['Cid'] ?>" />
										<input type="hidden" name="request_id" value="<?php echo $_GET['request_id'] ?>" />
										<input type="hidden" name="updated_at" value="<?php echo $_GET['updated_at'] ?>" />
										<input type="hidden" name="parentgroupId" value="<?php echo $_SESSION['admin_groupid'] ?>" />
										<input type="hidden" name="parentuserId" value="<?php echo $_SESSION['admin_user_id'] ?>" />
									</form>
								</div>
							</div>
							<div class="col-md-2"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>



<?php
function createFormForPagesHtml(&$rows)
{
	$row = array();
	global $component, $database;
	$row = &$rows[0];
	$sqlmenuid = "select * from tbl_components where component_option='" . $database->filter($_GET['c']) . "'";
	$getmenuid = $database->get_results($sqlmenuid);
	$menuid = $getmenuid[0];
	$admin_groupid = explode(",", $_SESSION['admin_groupid']);

	$user = $database->get_results("SELECT groupid FROM tbl_users WHERE user_id = '" . $database->filter($_SESSION['admin_user_id']) . "'");
	$groupids = $user[0]['groupid'];
	$group_ids = explode(',', $user[0]['groupid']);  // Assuming groupid stores multiple IDs as a comma-separated string

	$allowed_groups = [7, 67, 58, 60, 8, 79];
	$show_submit_button = array_intersect($allowed_groups, $group_ids);
	// Fetch group names
	$groups = $database->get_results("SELECT * FROM `tbl_groups` WHERE find_in_set (group_id, '" . $database->filter(implode(",", $show_submit_button)) . "') AND group_id NOT IN (2, 11)");

	if (in_array(7, $admin_groupid)) {
		$groupid = 7;
		$group11 = "Executive Engineer";
	} elseif (in_array(67, $admin_groupid)) {
		$groupid = 67;
		$group11 = "Sub Divisional Officer";
	} elseif (in_array(58, $admin_groupid)) {
		$groupid = 58;
		$group11 = "Ziledar";
	} elseif (in_array(60, $admin_groupid)) {
		$groupid = 60;
		$group11 = "Junior Engineer";
	} elseif (in_array(8, $admin_groupid)) {
		$groupid = 8;
		$group11 = "Superintending Engineer";
	} elseif (in_array(79, $admin_groupid)) {
		$groupid = 79;
		$group11 = "Deputy Collector";
	}
?>
	<div class="main-content">
		<div class="container">
			<div class="page-header d-xl-flex d-block">
				<div class="page-leftheader">
					<h4 class="page-title">CHO CTO Applications : <?php if (count($row) > 0) echo 'Edit';
																	else echo 'Add'; ?></h4>
				</div>
			</div>
			<style>
				.forrevalidate {
					display: none;
				}
			</style>
			<div class="row">
				<div class="col-xl-12 col-md-12 col-lg-12">
					<div class="card">
						<div class="card-body">
							<?php
							if (count($row) > 0)
								$task = "saveedit";
							else
								$task = "save";
							?>
							<div class="row">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<form name="user-form" id="user-form" action="?c=<?php echo $component ?>&task=<?php echo $task; ?>" method="post" class="form-horizontal" enctype="multipart/form-data">

										<div class="row">
											<?php if (isset($_GET['type']) && $_GET['type'] == 1) { ?>
												<div class="container card bg-light text-dark">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Select Search Method</h3>
															<select name="search_method" id="search_method" class="form-control" required>
																<option value="">-- Select --</option>
																<option value="hrms" <?php if ($row['search_method'] == 'hrms') echo 'selected'; ?>>Search by HRMS Code</option>
																<option value="office" <?php if ($row['search_method'] == 'office') echo 'selected'; ?>>Search by Office</option>
															</select>
															<br />
														</div>
													</div>

												</div>
												<div class="container card bg-light text-dark search-section" id="hrms_section" style="display: none;">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Charge Taking Officer Detail</h3>
														</div>
														<div class="col-md-12">
															<label class="form-label">HRMS Code</label>
															<input type="text" value="<?php echo $row['officer_hrms_code'] ?>" id="officer_hrms_code" name="officer_hrms_code" class="form-control"><br />
														</div>
														<div class="col-md-12 charge_taking11"></div>
														<br />
													</div>
												</div>
												<div class="container card bg-light text-dark search-section" id="office_section" style="display: none;">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Search by Office</h3>
														</div>
														<div class="col-md-6">
															<label class="form-label">Select Office</label>
															<select name="office_id" id="office_id" class="form-control">
																<option value="">Select Office</option>
																<?php
																$sql = "SELECT * FROM tbl_establishment_office WHERE e_office_status = 1";
																$results = $database->get_results($sql);
																foreach ($results as $result) { ?>
																	<option value="<?php echo $result['e_office_id']; ?>"
																		<?= $row['office_id'] == $result['e_office_id'] ? "selected" : "" ?>>
																		<?php echo $result['e_office_name']; ?>
																	</option>
																<?php } ?>
															</select>
														</div>
														<div class="col-md-6">
															<label class="form-label">Select Designation</label>
															<select name="designation_id" id="designation_id" class="form-control">
																<option value="">Select Designation</option>
																<?php
																$sql = "SELECT * FROM tbl_designation WHERE designation_status = 1";
																$results = $database->get_results($sql);
																foreach ($results as $result) { ?>
																	<option value="<?php echo $result['designation_id']; ?>"
																		<?= $row['designation_id'] == $result['designation_id'] ? "selected" : "" ?>>
																		<?php echo $result['designation_name']; ?>
																	</option>
																<?php } ?>
															</select>
														</div>
														<div class="col-md-12 charge_taking11_office"></div>
														<br />
													</div>
												</div>
											<?php } else if (isset($_GET['type']) && ($_GET['type'] == 2 || $_GET['type'] == 12)) {
											?>
												<div class="container card bg-light text-dark">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Select Search Method</h3>
															<select name="search_method1" id="search_method" class="form-control" required>
																<option value="">-- Select --</option>
																<option value="hrms" <?php if ($row['search_method1'] == 'hrms') echo 'selected'; ?>>Search by HRMS Code</option>
																<option value="office" <?php if ($row['search_method1'] == 'office') echo 'selected'; ?>>Search by Office</option>
															</select>
															<br />
														</div>
													</div>
												</div>

												<div class="container card bg-light text-dark search-section" id="hrms_section" style="display: none;">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Higher Officer Detail</h3>
														</div>
														<div class="col-md-12">
															<label class="form-label">HRMS Code</label>
															<input type="text" value="<?php echo $row['higher_authority_hrms'] ?>" id="officer_hrms_code" name="higher_authority_hrms" class="form-control"><br />
														</div>
														<div class="col-md-12 charge_taking11"></div>
														<br />
													</div>
												</div>

												<div class="container card bg-light text-dark search-section" id="office_section" style="display: none;">
													<div class="row">
														<div class="col-md-12">
															<br />
															<h3>Search by Office</h3>
														</div>
														<div class="col-md-6">
															<label class="form-label">Select Office</label>
															<select name="office_id" id="office_id" class="form-control">
																<option value="">Select Office</option>
																<?php
																$sql = "SELECT * FROM tbl_establishment_office WHERE e_office_status = 1";
																$results = $database->get_results($sql);
																foreach ($results as $result) { ?>
																	<option value="<?php echo $result['e_office_id']; ?>"
																		<?= $row['office_id'] == $result['e_office_id'] ? "selected" : "" ?>>
																		<?php echo $result['e_office_name']; ?>
																	</option>
																<?php } ?>
															</select>
														</div>
														<div class="col-md-6">
															<label class="form-label">Select Designation</label>
															<select name="designation_id" id="designation_id" class="form-control">
																<option value="">Select Designation</option>
																<?php
																$sql = "SELECT * FROM tbl_designation WHERE designation_status = 1";
																$results = $database->get_results($sql);
																foreach ($results as $result) { ?>
																	<option value="<?php echo $result['designation_id']; ?>"
																		<?= $row['designation_id'] == $result['designation_id'] ? "selected" : "" ?>>
																		<?php echo $result['designation_name']; ?>
																	</option>
																<?php } ?>
															</select>
														</div>
														<div class="col-md-12 charge_taking11_office"></div>
														<br />
													</div>
												</div>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 6) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Executive Engineer</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Uploded Files</th>

														</tr>
													</thead>
													<tbody>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of Jurisdiction</label></td>
															<td style='border: 1px solid black;'><?= isset($row['jurisdiction_detail']) ? $row['jurisdiction_detail'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document</label></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_court_case']) && $row['upload_court_case'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] ?>">View File</a>
																<?php } ?>
															</td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Court Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['court_cases']) ? $row['court_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Arbitration Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['arbitration_cases']) ? $row['arbitration_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Recovery Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_recovery_cases']) ? $row['pending_recovery_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Disciplinary Action Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_disciplinary_cases']) ? $row['pending_disciplinary_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Pension Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_pension_cases']) ? $row['pending_pension_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Compassionate Ground Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_compassionate_cases']) ? $row['pending_compassionate_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Issues with District Administration</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_district_issues']) ? $row['pending_district_issues'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Land Encroachment Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['land_encroachment_cases']) ? $row['land_encroachment_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending RTI Applications</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_rti_applications']) ? $row['pending_rti_applications'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of Contact Details</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['contact_details']) ? $row['contact_details'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_contact_list']) && $row['upload_contact_list'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_contact_list'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Cash Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cash_books']) ? $row['cash_books'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Cheque Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cheque_books']) ? $row['cheque_books'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">G.R. Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['gr_books']) ? $row['gr_books'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Available Funds Under Different Heads</label></td>
															<td style='border: 1px solid black;'><?= isset($row['available_funds']) ? $row['available_funds'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Liability</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['pending_liability']) ? $row['pending_liability'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['pending_liability_doc']) && $row['pending_liability_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['pending_liability_doc'] ?>">View File</a>
																<?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Works Proposed to be Executed in Near Future</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['works_proposed']) ? $row['works_proposed'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['works_proposed_doc']) && $row['works_proposed_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['works_proposed_doc'] ?>">View File</a>
																<?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Estimate, Without Calling, Approval of Rates Pending in Higher Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['estimate_pending']) ? $row['estimate_pending'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Completed Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['completed_works']) ? $row['completed_works'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">In Progress Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['in_progress_works']) ? $row['in_progress_works'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Tenders Floated</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_floated']) ? $row['tenders_floated'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Tenders Already Opened and In Process</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_opened']) ? $row['tenders_opened'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Tenders Pending to be Open</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_pending']) ? $row['tenders_pending'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Critical/Vulnerable Sites</label></td>
															<td style='border: 1px solid black;'><?= isset($row['critical_sites']) ? $row['critical_sites'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Govt. References</label></td>
															<td style='border: 1px solid black;'><?= isset($row['govt_references']) ? $row['govt_references'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Revenue/ Warabandi/ Tawan/ Breach Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_own_office']) ? $row['pending_revenue_own_office'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Revenue Court Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_subordinate_office']) ? $row['pending_revenue_subordinate_office'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Command to Uncommand Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_uncommand_cases']) ? $row['pending_uncommand_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
													</tbody>
												</table>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 7) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Sub Divisional Officer</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Uploaded Documents</th>

														</tr>
													</thead>
													<tbody>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of Jurisdiction</label></td>
															<td style='border: 1px solid black;'><?= isset($row['jurisdiction_detail']) ? $row['jurisdiction_detail'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Court Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['court_cases']) ? $row['court_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Arbitration Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['arbitration_cases']) ? $row['arbitration_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Recovery Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_recovery_cases']) ? $row['pending_recovery_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of Absent Employees</label></td>
															<td style='border: 1px solid black;'><?= isset($row['absent_emp']) ? $row['absent_emp'] : '' ?></td>
															<td style='border: 1px solid black;'><?php if (isset($row['upload_court_case']) && $row['upload_court_case'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<!-- <tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document</label></td>
															<td style='border: 1px solid black;'>
																
															</td>
														</tr> -->
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Issues with District Administration</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_authority']) ? $row['pending_authority'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Land Encroachment Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_disciplinary_cases']) ? $row['pending_disciplinary_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Compassionate Ground Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_compassionate_cases']) ? $row['pending_compassionate_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending RTI Applications</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_rti_applications']) ? $row['pending_rti_applications'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of Contact Details</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['contact_details']) ? $row['contact_details'] : '' ?>

															</td>
															<td style='border: 1px solid black;'><?php if (isset($row['upload_contact_list']) && $row['upload_contact_list'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_contact_list'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of FIR’s/Complaints/Cases</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['fir_complaints']) ? $row['fir_complaints'] : '' ?>

															</td>
															<td style='border: 1px solid black;'><?php if (isset($row['fir_complaints_doc']) && $row['fir_complaints_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['fir_complaints_doc'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Property Register</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['property']) ? $row['property'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['property_doc']) && $row['property_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['property_doc'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document (Accounts)</label></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['account_doc']) && $row['account_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['account_doc'] ?>">View File</a>
																<?php } ?>
															</td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Cash Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cash_books']) ? $row['cash_books'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Cheque Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cheque_books']) ? $row['cheque_books'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">G.R. Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['gr_books']) ? $row['gr_books'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Liability</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['pending_liability']) ? $row['pending_liability'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['pending_liability_doc']) && $row['pending_liability_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['pending_liability_doc'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Final Bills to be Submitted</label></td>
															<td style='border: 1px solid black;'><?= isset($row['available_funds']) ? $row['available_funds'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Works Proposed</label></td>
															<td style='border: 1px solid black;'><?= isset($row['works_proposed']) ? $row['works_proposed'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Estimate Pending in Higher Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['estimate_pending']) ? $row['estimate_pending'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Details of Ongoing Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['govt_references']) ? $row['govt_references'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document (Works)</label></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['works_proposed_doc']) && $row['works_proposed_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['works_proposed_doc'] ?>">View File</a>
																<?php } ?>
															</td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Completed Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['completed_works']) ? $row['completed_works'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">In Progress Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['in_progress_works']) ? $row['in_progress_works'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Critical/Vulnerable Sites</label></td>
															<td style='border: 1px solid black;'><?= isset($row['critical_sites']) ? $row['critical_sites'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Revised Estimates/Projects</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_opened']) ? $row['tenders_opened'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document (Tenders)</label></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['detail_tender_doc']) && $row['detail_tender_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['detail_tender_doc'] ?>">View File</a>
																<?php } ?>
															</td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Upload Document (Revenue)</label></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['revenue_doc']) && $row['revenue_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['revenue_doc'] ?>">View File</a>
																<?php } ?>
															</td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Revenue/Tawan Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_own_office']) ? $row['pending_revenue_own_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Revenue Court Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_subordinate_office']) ? $row['pending_revenue_subordinate_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Command to Uncommand Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_uncommand_cases']) ? $row['pending_uncommand_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
													</tbody>
												</table>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 8) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Juniour Engineer</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Uploded Files</th>

														</tr>
													</thead>
													<tbody>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Benchmarks</label></td>
															<td style='border: 1px solid black;'><?= isset($row['jurisdiction_detail']) ? $row['jurisdiction_detail'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_court_case']) && $row['upload_court_case'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] ?>">View File</a>
																<?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Clusters</label></td>
															<td style='border: 1px solid black;'><?= isset($row['court_cases']) ? $row['court_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Co-ordinates of Benchmarks</label></td>
															<td style='border: 1px solid black;'><?= isset($row['arbitration_cases']) ? $row['arbitration_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">GTS Stones</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_recovery_cases']) ? $row['pending_recovery_cases'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Major Buildings, Bridges, Falls & Tail</label></td>
															<td style='border: 1px solid black;'><?= isset($row['absent_emp']) ? $row['absent_emp'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Co-ordinates of location of Machinery stationed in field</label></td>
															<td style='border: 1px solid black;'><?= isset($row['vetted_reply_submitted']) ? $row['vetted_reply_submitted'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Works proposed to be executed in near future</label></td>
															<td style='border: 1px solid black;'><?= isset($row['works_proposed']) ? $row['works_proposed'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['detail_tender_doc']) && $row['detail_tender_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['detail_tender_doc'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Details of Ongoing Works</label></td>
															<td style='border: 1px solid black;'><?= isset($row['govt_references']) ? $row['govt_references'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['works_proposed_doc']) && $row['works_proposed_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['works_proposed_doc'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Completed</label></td>
															<td style='border: 1px solid black;'><?= isset($row['completed_works']) ? $row['completed_works'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">In Progress</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['in_progress_works']) ? $row['in_progress_works'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Measurement Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_liability']) ? $row['pending_liability'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">T & P Register/Stock Register, MAS Register, Inspection/Instruction Register etc.</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tp_register']) ? $row['tp_register'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Outlet Register</label></td>
															<td style='border: 1px solid black;'><?= isset($row['outlet_notebook']) ? $row['outlet_notebook'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Court Cases assigned along with status/action requireds</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_floated']) ? $row['tenders_floated'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Advance payment made to be recovered in next bill(s)</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['estimate_pending']) ? $row['estimate_pending'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['account_doc']) && $row['account_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['account_doc'] ?>">View File</a>
																<?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Bills or Final bills</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['pending_authority']) ? $row['pending_authority'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Critical/Vulnerable Sites</label></td>
															<td style='border: 1px solid black;'><?= isset($row['critical_sites']) ? $row['critical_sites'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of Contact detail of field staff/stakeholders/Progressive farmers</label></td>
															<td style='border: 1px solid black;'><?= isset($row['contact_details']) ? $row['contact_details'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_contact_list']) && $row['upload_contact_list'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_contact_list'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of pending Revenue Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cash_books']) ? $row['cash_books'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['revenue_doc']) && $row['revenue_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['revenue_doc'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of FIR’s/Complaint /Cases registered against illegal activities, encroachment, etc</label></td>
															<td style='border: 1px solid black;'><?= isset($row['fir_complaints']) ? $row['fir_complaints'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['fir_complaints_doc']) && $row['fir_complaints_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['fir_complaints_doc'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending NOC Cases with him or her</label></td>
															<td style='border: 1px solid black;'><?= isset($row['property']) ? $row['property'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['property_doc']) && $row['property_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['property_doc'] ?>">View File</a> <?php } ?>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Revised estimates/Projects to be submitted</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_opened']) ? $row['tenders_opened'] : '' ?></td>
															<td style='border: 1px solid black;'>
															</td>
														</tr>
													</tbody>
												</table>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 9) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Deputy Collector</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Documents</th>

														</tr>
													</thead>
													<tbody>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of Jurisdiction</label></td>
															<td style='border: 1px solid black;'><?= isset($row['jurisdiction_detail']) ? $row['jurisdiction_detail'] : '' ?></td>
															<td style='border: 1px solid black;'></td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Disciplinary Action/Enquiry Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['court_cases']) ? $row['court_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">T & P Register/Stock Register</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tp_register']) ? $row['tp_register'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>


														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Pending Warabandi Cases</h5>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Own Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_own_office']) ? $row['pending_revenue_own_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Subordinate Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_subordinate_office']) ? $row['pending_revenue_subordinate_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Pending Command to Uncommand Cases</h5>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Own Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_uncommand_cases']) ? $row['pending_uncommand_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Subordinate Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_recovery_cases']) ? $row['pending_recovery_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Pending Warabandi Court Cases</h5>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Warabandi Court Cases</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['contact_details']) ? $row['contact_details'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_contact_list']) && $row['upload_contact_list'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_contact_list'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>

														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Pending Judgments to be Written or Announced</h5>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Tenders Floated</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tenders_floated']) ? $row['tenders_floated'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['detail_tender_doc']) && $row['detail_tender_doc'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['detail_tender_doc'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
													</tbody>
												</table>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 10) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Superintending Engineer</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Documents</th>

														</tr>
													</thead>
													<tbody>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Detail of Jurisdiction</label></td>
															<td style='border: 1px solid black;'><?= isset($row['jurisdiction_detail']) ? $row['jurisdiction_detail'] : '' ?></td>
															<td style='border: 1px solid black;'></td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Court Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['court_cases']) ? $row['court_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Arbitration Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['arbitration_cases']) ? $row['arbitration_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>


														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Disciplinary Action Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_disciplinary_cases']) ? $row['pending_disciplinary_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Pension Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_pension_cases']) ? $row['pending_pension_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Compassionate Ground Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_compassionate_cases']) ? $row['pending_compassionate_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Issues with District Administration or any other authority</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_district_issues']) ? $row['pending_district_issues'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">List of Contact Details of Office and field staff, district administration, other officers/ stakeholders/ Progressive farmers</label></td>
															<td style='border: 1px solid black;'>
																<?= isset($row['contact_details']) ? $row['contact_details'] : '' ?>

															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_contact_list']) && $row['upload_contact_list'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_contact_list'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending RTI Applications</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_rti_applications']) ? $row['pending_rti_applications'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_court_case']) && $row['upload_court_case'] != "") { ?>
																	<a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] ?>">View File</a>
																<?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Cash Books</label></td>
															<td style='border: 1px solid black;'><?= isset($row['cash_books']) ? $row['cash_books'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Works Proposed to be Executed in Near Future</label></td>
															<td style='border: 1px solid black;'><?= isset($row['works_proposed']) ? $row['works_proposed'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Estimate, Without Calling, Approval of Rates Pending in Higher Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['estimate_pending']) ? $row['estimate_pending'] : '' ?></td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['works_proposed_doc']) && $row['works_proposed_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['works_proposed_doc'] ?>">View File</a> <?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Completed</label></td>
															<td style='border: 1px solid black;'><?= isset($row['completed_works']) ? $row['completed_works'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">In Progress</label></td>
															<td style='border: 1px solid black;'><?= isset($row['in_progress_works']) ? $row['in_progress_works'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Critical/Vulnerable Sites</label></td>
															<td style='border: 1px solid black;'><?= isset($row['critical_sites']) ? $row['critical_sites'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Issue to be taken up with Govt. and district administration</label></td>
															<td style='border: 1px solid black;'><?= isset($row['govt_references']) ? $row['govt_references'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Revenue/ Appeal cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_own_office']) ? $row['pending_revenue_own_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending judgments to be written or announced</label></td>
															<td style='border: 1px solid black;'><?= isset($row['property']) ? $row['property'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Command to Uncommand Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_uncommand_cases']) ? $row['pending_uncommand_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
													</tbody>
												</table>
											<?php
											} else if (isset($_GET['type']) && $_GET['type'] == 11) {
											?>
												<div class="col-md-12">
													<div class="d-flex justify-content-between align-items-center">
														<h3>Handover Charge of Ziledar</h3>
														<div class="card-footer text-right" style="border: none; padding: 0;">
															<button type="button" class="btn btn-sm btn-danger" style="height: 30px; line-height: 1;" onclick="closeCurrentTab()">Close</button>
														</div>
													</div>
												</div>
												<table class="table table-bordered" style="margin-top: 20px">
													<thead>
														<tr style="padding: 5px; background-color: #f0f0f0; border: 1px solid black;">
															<th style="text-align: left; padding: 0; border: 1px solid black;">Details</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Values</th>
															<th style="text-align: left; padding: 0; border: 1px solid black;">Documents</th>

														</tr>
													</thead>
													<tbody>
														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Pending Revenue/ Warabandi/ Tawan/ Breach Cases</h5>
															</td>
														</tr>

														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Own Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_own_office']) ? $row['pending_revenue_own_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Subordinate Office</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_revenue_subordinate_office']) ? $row['pending_revenue_subordinate_office'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending Command to Uncommand Cases</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_uncommand_cases']) ? $row['pending_uncommand_cases'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Site Inspection of Revenue/ Warabandi/ Tawan/ Breach Cases</h5>
															</td>
														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Pending</label></td>
															<td style='border: 1px solid black;'><?= isset($row['pending_site_inspection']) ? $row['pending_site_inspection'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Done But Report is Pending</label></td>
															<td style='border: 1px solid black;'><?= isset($row['done_site_inspection']) ? $row['done_site_inspection'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

														<tr>
															<td colspan="2" style='border: 1px solid black; background-color: #f0f0f0;'>
																<h5>Detail of Court Cases</h5>
															</td>
															<td style='border: 1px solid black;'>
																<?php if (isset($row['upload_court_case']) && $row['upload_court_case'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $row['upload_court_case'] ?>">View File</a> <?php } ?>
															</td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Vetted Reply Submitted</label></td>
															<td style='border: 1px solid black;'><?= isset($row['vetted_reply_submitted']) ? $row['vetted_reply_submitted'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Vetted Reply Pending</label></td>
															<td style='border: 1px solid black;'><?= isset($row['vetted_reply_pending']) ? $row['vetted_reply_pending'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">T & P Register/Stock Register</label></td>
															<td style='border: 1px solid black;'><?= isset($row['tp_register']) ? $row['tp_register'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>
														<tr>
															<td style='border: 1px solid black;'><label class="form-label">Outlet Noteboo</label></td>
															<td style='border: 1px solid black;'><?= isset($row['outlet_notebook']) ? $row['outlet_notebook'] : '' ?></td>
															<td style='border: 1px solid black;'></td>

														</tr>

													</tbody>
												</table>
											<?php
											} else { ?>
												<div>
													<h4 style='color:blue;'>Please check your WRD profile Data before creating new application.</h4>
													<a href="index.php?c=account&Cid=17" target='_blank' style='color:blue;'>Check link here</a>
													<br>

												</div>
												<div class="col-md-6"><br />
													<div class="form-group">
														<label class="form-label">Choose Charge to be Handover</label>
														<select name="group_id" id="group_id" class="form-control" required>
															<option value="">-- Select Role --</option>
															<?php
															foreach ($groups as $group) { ?>
																<option value="<?php echo $group['group_id']; ?>" <?php echo ($group['group_id'] == $row['group_id']) ? 'selected' : ''; ?>><?php echo $group['group_name']; ?></option>
															<?php } ?>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="form-group">
														<label class="form-label"><input type="checkbox" value="1" name="profile_check" required> I have verified my WRD profile details.</label>
													</div>
												</div>

												<?php if (!empty($show_submit_button)) { ?>

													<?php if (in_array(58, $group_ids)) { ?>
														<div class="row" id="ziledar" style="display: none;">
														</div>
													<?php } ?>

													<?php if (in_array(7, $group_ids)) { ?>

														<div class="row" id="xen" style="display: none;">
														</div>
													<?php } ?>

													<?php if (in_array(67, $group_ids)) { ?>
														<div class="row" id="sdo" style="display: none;">
														</div>
													<?php } ?>

													<?php if (in_array(60, $group_ids)) { ?>
														<div class="row" id="je" style="display: none;">
														</div>
													<?php } ?>

													<?php if (in_array(8, $group_ids)) { ?>

														<div class="row" id="se" style="display: none;">
														</div>

													<?php } ?>

													<?php if (in_array(79, $group_ids)) { ?>

														<div class="row" id="dc" style="display: none;">
														</div>
													<?php } ?>
												<?php
												} ?>
											<?php } ?>

											<?php
											if (isset($_REQUEST['type']) && ($_REQUEST['type'] == 6 || $_REQUEST['type'] == 7 || $_REQUEST['type'] == 8 || $_REQUEST['type'] == 9 || $_REQUEST['type'] == 10 || $_REQUEST['type'] == 11)) {
											?>
												<div class="col-md-12">
													<div class="card-footer text-right">


														<!-- <button type="submit"
													class="btn btn-lg btn-primary ev_leavesubmit">Submit</button> -->
														<!-- <button type="button" class="btn btn-lg btn-danger"
													onclick="window.location='?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>'">Close</button> -->
														<button type="button" class="btn btn-lg btn-danger"
															onclick="closeCurrentTab()">Close</button>

													</div>
												</div>
											<?php
											} else if (isset($_REQUEST['type']) && ($_REQUEST['type'] == 1 || $_REQUEST['type'] == 2 || $_REQUEST['type'] == 12)) {
											?>
												<div class="col-md-12">
													<div class="card-footer text-right">
														<button type="submit" class="btn btn-lg btn-primary">Submit</button>
														<button type="button" class="btn btn-lg btn-danger"
															onclick="window.location='?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>'">
															Cancel
														</button>
													</div>
												</div>
											<?php } else if (empty($show_submit_button)) {
												echo "<h4 style='color:red'> Relevant Role is Not Assigned, Please Check Your Profile </h4>";
											}
											?>
											<input type="hidden" name="CId" value="<?php echo $_GET['Cid'] ?>" />
											<input type="hidden" name="userId" value="<?php echo $row['id'] ?>" />
											<input type="hidden" name="parentgroupId" value="<?php echo $_SESSION['admin_groupid'] ?>" />
											<input type="hidden" name="parentuserId" value="<?php echo $_SESSION['admin_user_id'] ?>" />
											<input type="hidden" name="rowdata" id="rowsata" value='<?php echo htmlspecialchars(json_encode($row ?? []), ENT_QUOTES, 'UTF-8'); ?>' />

											<input type="hidden" name="type" value="<?php echo $_REQUEST['type'] ?>" />
											<input type="hidden" name="stage" value="<?php echo $_REQUEST['stage'] ?>" />

											<?php
											// }
											?>
									</form>
								</div>
							</div>
							<div class="col-md-2"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>



<script>
	function Fieldforuser(val) {
		alert(val);
	}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js"> </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"> </script>
<script>
	$("document").ready(function() {

		var selected = $('#search_method').val();

		$('.search-section').hide();
		$('#officer_hrms_code, #office_id, #designation_id').removeAttr('required');

		if (selected === 'hrms') {
			$('#hrms_section').show();
			$('#officer_hrms_code').attr('required', 'required');
		} else if (selected === 'office') {
			$('#office_section').show();
			$('#office_id, #designation_id').attr('required', 'required');
		}

		$('#search_method').change(function() {
			var selected = $(this).val();
			$('.search-section').hide();
			$('#officer_hrms_code, #office_id, #designation_id').removeAttr('required');

			if (selected === 'hrms') {
				$('#hrms_section').show();
				$('#officer_hrms_code').attr('required', 'required');
			} else if (selected === 'office') {
				$('#office_section').show();
				$('#office_id, #designation_id').attr('required', 'required');
			}
		});

		var rowsata = $("#rowsata").val();
		var parsedRowData = JSON.parse(rowsata);
		console.log("Parsed rowData:", parsedRowData);

		$("[name='charge_handover']").on("change", function() {
			if ($(this).val() == "Ziledar") {
				$(".ziledar_form1").show();
				$(".ziledar_form1").prop('required', true);
			} else {
				$(".ziledar_form1").hide();
				$(".ziledar_form1").prop('required', false);
			}
		});

		function showSelectedForm(group_id) {
			$('#ziledar, #xen, #sdo, #je, #se, #dc').hide().removeAttr('required');

			let formMap = {
				58: '#ziledar',
				7: '#xen',
				67: '#sdo',
				60: '#je',
				8: '#se',
				79: '#dc'
			};

			if (formMap[group_id]) {
				$(formMap[group_id]).show().attr('required', 'required');

				$.ajax({
					url: "<?php echo URL ?>admin/components/cho_cto/forms/" + formMap[group_id].substring(1) + "_form.php",
					type: 'POST',
					data: {
						type: group_id,
						rowData: JSON.stringify(parsedRowData)
					},
					success: function(response) {
						$(formMap[group_id]).html(response);
					}
				});
			}
		}

		let selectedGroupId = $('#group_id').val();
		if (selectedGroupId) {
			showSelectedForm(selectedGroupId);
		}

		$('#group_id').on('change', function() {
			showSelectedForm($(this).val());
		});

		var officer_hrms_code1 = $("#officer_hrms_code").val();
		$.ajax({
			url: "<?php echo URL ?>admin/components/cho_cto/ajax/get_officer_hrms.php",
			data: {
				hrms: officer_hrms_code1
			},
			success: function(gethrms) {

				$(".charge_taking11").html(gethrms);
			}
		});

		$("#officer_hrms_code").on("input", function() {

			var officer_hrms_code1 = $("#officer_hrms_code").val();
			$.ajax({
				url: "<?php echo URL ?>admin/components/cho_cto/ajax/get_officer_hrms.php",
				data: {
					hrms: officer_hrms_code1
				},
				success: function(gethrms) {

					$(".charge_taking11").html(gethrms);
				}
			});

		});

		var office_id = $("#office_id").val();
		var designation_id = $("#designation_id").val();

		$.ajax({
			url: "<?php echo URL ?>admin/components/cho_cto/ajax/get_user.php",
			data: {
				office: office_id,
				desg: designation_id
			},
			success: function(gethrms) {

				$(".charge_taking11_office").html(gethrms);
			}
		});

		$('#office_id, #designation_id').on("change", function() {
			var office_id = $("#office_id").val();
			var designation_id = $("#designation_id").val();

			$.ajax({
				url: "<?php echo URL ?>admin/components/cho_cto/ajax/get_user.php",
				data: {
					office: office_id,
					desg: designation_id
				},
				success: function(gethrms) {

					$(".charge_taking11_office").html(gethrms);
				}
			});
		})

		$("#user-form").validate({
			rules: {
				email: {
					required: true,
					email: true,
					remote: "<?php echo URL ?>admin/ajax/canalapp.php"
				},
				username: {
					required: true,
					remote: "<?php echo URL ?>admin/ajax/canalapp.php"
				},
				canal_water: {
					required: true,
					number: true,
				},
				adhar_num: {
					required: true,
					remote: "<?php echo URL ?>admin/ajax/canalapp.php"
				},
				mob_number: {
					required: true,
					remote: "<?php echo URL ?>admin/ajax/canalapp.php"
				}
			},
			messages: {
				email: {
					remote: "Email already in use.."
				},
				canal_water: {
					message: "Please enter Value.."
				},
				username: {
					remote: "Username already in use.."
				},
				mob_number: {
					remote: "Mobile No. already in use.."
				},
				adhar_num: {
					remote: "Aadhar Number already in use.."
				}
			}
		});


	});

	function closeCurrentTab() {
		window.open('', '_self');
		window.close();

	}

	document.addEventListener('DOMContentLoaded', function() {
		const detailOpeners = document.querySelectorAll('.detailopener');

		detailOpeners.forEach(function(opener) {
			opener.addEventListener('click', function() {

				charge_handover = $(this).attr("data-charge_handover");
				$("#detailsmodal .charge_handover").html(charge_handover);

				officer_hrms_code = $(this).attr("data-officer_hrms_code");
				$("#detailsmodal .officer_hrms_code").html(officer_hrms_code);

				officer_name = $(this).attr("data-officer_name");
				$("#detailsmodal .officer_name").html(officer_name);

				officer_designation = $(this).attr("data-officer_designation");
				$("#detailsmodal .officer_designation").html(officer_designation);

				pending_revenue_own_office = $(this).attr("data-pending_revenue_own_office");
				$("#detailsmodal .pending_revenue_own_office").html(pending_revenue_own_office);

				pending_revenue_subordinate_office = $(this).attr("data-pending_revenue_subordinate_office");
				$("#detailsmodal .pending_revenue_subordinate_office").html(pending_revenue_subordinate_office);

				pending_uncommand_cases = $(this).attr("data-pending_uncommand_cases");
				$("#detailsmodal .pending_uncommand_cases").html(pending_uncommand_cases);

				pending_site_inspection = $(this).attr("data-pending_site_inspection");
				$("#detailsmodal .pending_site_inspection").html(pending_site_inspection);

				done_site_inspection = $(this).attr("data-done_site_inspection");
				$("#detailsmodal .done_site_inspection").html(done_site_inspection);

				vetted_reply_submitted = $(this).attr("data-vetted_reply_submitted");
				$("#detailsmodal .vetted_reply_submitted").html(vetted_reply_submitted);

				vetted_reply_pending = $(this).attr("data-vetted_reply_pending");
				$("#detailsmodal .vetted_reply_pending").html(vetted_reply_pending);

				tp_register = $(this).attr("data-tp_register");
				$("#detailsmodal .tp_register").html(tp_register);

				outlet_notebook = $(this).attr("data-outlet_notebook");
				$("#detailsmodal .outlet_notebook").html(outlet_notebook);

				upload_court_case = $(this).attr("data-upload_court_case");
				$("#detailsmodal .upload_court_case").html(upload_court_case);

				const user_code = this.getAttribute('data-user_code');
				$(".user_code").text(user_code);

				const ms_username = this.getAttribute('data-name');
				$(".ms_username").text(ms_username);

				const ms_created_at = this.getAttribute('data-ms_created_at');
				$(".ms_created_at").text(ms_created_at);

				const xen = this.getAttribute('data-xen');
				$(".xen").html(xen);

				const emp_office = this.getAttribute('data-emp_office');
				$(".emp_office").text(emp_office);

				const designation = this.getAttribute('data-created_designation');
				$(".created_designation").text(designation);

				const status = this.getAttribute('data-status');
				(".status").text(status);
			});
		});
	});
</script>
<script>
	$("document").ready(function() {
		$('[name="groupid"]').on("change", function() {
			$('[name="establishment_office[]"]').attr("multiple", false);
			if ($('[name="groupid"]').val() == 3) {
				$('name="establishment_office[]"').attr("multiple", true);
			}
		});
	});
</script>

<style>
	.form-control.error {
		border-color: red;
	}

	.error {
		color: red;
	}

	.please_specify {
		display: none;
	}
</style>
<style>
	.ziledar_form1 {
		display: none;
	}
</style>