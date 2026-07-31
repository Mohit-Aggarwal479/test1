<?php
function showList()
{
	global $database, $page, $pagingObject;
	$sql = "SELECT cto.* FROM tbl_cto_cho cto, tbl_users u where cto.created_by = u.user_id and cto.view_status = 1 and u.user_status = 1";
	$admin_groupid = explode(",", $_SESSION['admin_groupid']);
	$where = "";

	if (in_array(2, $admin_groupid)) {
		$where .= " AND (cto.created_by = '" . $database->filter($_SESSION['admin_user_id']) . "' OR cto.officer_user_id = '" . $database->filter($_SESSION['admin_user_id']) . "' OR cto.higher_authority_id = '" . $database->filter($_SESSION['admin_user_id']) . "')";
	} else {
		if (in_array(1, $admin_groupid)) {
			$where .= " ";
		}
	}


	$hallcurrTime = date("Y-m-d");
	$hallcurrTime_7 = date('Y-m-d', strtotime('-7 days', strtotime($hallcurrTime)));

	if (isset($_GET['status_pending']) && $_GET['status_pending'] == 1) {
		$where .= " AND ((cto.status = '0' OR cto.status = '1') AND ((date(ha.updated_at) < '" . $database->filter($hallcurrTime_7) . "' AND ha.updated_at !='0000-00-00 00:00:00') or (date(ha.created_at) < '" . $database->filter($hallcurrTime_7) . "' AND ha.updated_at='0000-00-00 00:00:00')))";
	}

	if (isset($_GET['reqt_id']) && $_GET['reqt_id'] != "") {
		$where .= " AND cto.id = '" . trim($database->filter($_GET['reqt_id']), "CHO-") . "'";
	}
	if (isset($_GET['datafrom']) && $_GET['datafrom'] != "" && isset($_GET['datato']) && $_GET['datato'] != "") {
		$where .= " AND cto.created_at BETWEEN TIMESTAMP('" . $database->filter($_GET['datafrom']) . "') AND TIMESTAMP('" . date("Y-m-d", strtotime($database->filter($_GET['datato']) . "+ 1 day")) . "')";
	} else {
		if (isset($_GET['datafrom']) && $_GET['datafrom'] != "") {
			$where .= " AND cto.created_at LIKE '%" . $database->filter($_GET['datafrom']) . "%'";
		}
		if (isset($_GET['datato']) && $_GET['datato'] != "") {
			$where .= " AND cto.created_at LIKE '%" . date("Y-m-d", strtotime($database->filter($_GET['datato']) . "+ 1 day")) . "%'";
		}
	}




	if (isset($_GET['status_action']) && $_GET['status_action'] == 1) {
		if (in_array(2, $admin_groupid)) {
			$where .= " AND (cto.stage = '1' OR cto.stage = '5' OR cto.stage = '2') and cto.status != 12";
		}
	}


	if (isset($_GET['charge_handover']) && $_GET['charge_handover'] != "") {
		$where .= " AND cto.charge_handover = '" . $database->filter($_GET['charge_handover']) . "'";
	}

	if (isset($_GET['officer_name']) && $_GET['officer_name'] != "") {
		$where .= " AND cto.officer_name LIKE '%" . $database->filter($_GET['officer_name']) . "%'";
	}

	if (isset($_GET['cto_hrms']) && $_GET['cto_hrms'] != "") {
		$where .= " AND cto.officer_hrms_code = '" . $database->filter($_GET['cto_hrms']) . "'";
	}

	if (isset($_GET['cho_hrms']) && $_GET['cho_hrms'] != "") {
		$where .= " AND u.user_code = '" . $database->filter($_GET['cho_hrms']) . "'";
	}



	// if(isset($_GET['status_dashboard']) && $_GET['status_dashboard'] == 1) {

	// $where .= " AND (status != '2') AND (status != '3')";

	// }

	if (isset($_GET['status_dashboard']) && $_GET['status_dashboard'] == 1) {

		$where .= " AND (status = '0' OR status = '1')";
	}
	if (isset($_GET['dashboard']) && $_GET['dashboard'] == 1) {

		$where .= " AND (status = '0' OR status = '1')";
	}

	// if(isset($_GET['status_approved']) && $_GET['status_approved'] == 1) {

	// $where .= " AND (status = 1)";

	// }

	if (isset($_GET['status']) && $_GET['status'] != "") {
		$where .= " AND cto.status_text = '" . $_GET['status'] . "'";
	}
	if ($where != "") {
		$sql = $sql . $where;
	}

	$sql .= " group by id order by id DESC";
	$_SESSION['cho_cto_sql'] = $sql;
	$pagingObject->setMaxRecords(PAGELIMIT);
	$sql = $pagingObject->setQuery($sql);
	$results = $database->get_results($sql);
	showRecordsListing($results);
}

function saveFormValues()
{
	global $database, $component;
	$cid = $_POST['CId'];

	$sql11 = "SELECT * FROM tbl_users WHERE user_code = '" . $database->filter($_POST['officer_hrms_code']) . "' AND user_status = 1";
	$results11 = $database->get_results($sql11);

	$sql115 = "SELECT * FROM tbl_designation where designation_id = '" . $database->filter($results11[0]['current_designation']) . "'";
	$results115 = $database->get_results($sql115);
	$target = PATH . "images/cto_cho/";


	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	}


	$admin_groupid = explode(",", $_SESSION['admin_groupid']);
	if ($_POST['group_id'] == 7) {
		$group11 = "Executive Engineer";
	} elseif ($_POST['group_id'] == 67) {
		$group11 = "Sub Divisional Officer";
	} elseif ($_POST['group_id'] == 58) {
		$group11 = "Ziledar";
	} elseif ($_POST['group_id'] == 60) {
		$group11 = "Junior Engineer";
	} elseif ($_POST['group_id'] == 8) {
		$group11 = "Superintending Engineer";
	} elseif ($_POST['group_id'] == 79) {
		$group11 = "Deputy Collector";
	}

	if ($_FILES['upload_court_case']['name'] != "") {
		$ext = substr($_FILES['upload_court_case']['name'], strpos($_FILES['upload_court_case']['name'], "."));
		copy($_FILES['upload_court_case']['tmp_name'], $target . "CourtCase-" . "-" . time() . $ext);
		$imageName1 = "CourtCase-" . "-" . time() . $ext;
	}
	$CourtCase = !empty($imageName1) ? $imageName1 : null;

	if ($_FILES['upload_contact_list']['name'] != "") {
		$ext = substr($_FILES['upload_contact_list']['name'], strpos($_FILES['upload_contact_list']['name'], "."));
		copy($_FILES['upload_contact_list']['tmp_name'], $target . "Contactlist-" . "-" . time() . $ext);
		$imageName2 = "Contactlist-" . "-" . time() . $ext;
	}
	$Contactlist = !empty($imageName2) ? $imageName2 : null;

	if ($_FILES['pending_liability_doc']['name'] != "") {
		$ext = substr($_FILES['pending_liability_doc']['name'], strpos($_FILES['pending_liability_doc']['name'], "."));
		copy($_FILES['pending_liability_doc']['tmp_name'], $target . "libality-" . "-" . time() . $ext);
		$imageName3 = "libality-" . "-" . time() . $ext;
	}
	$libality = !empty($imageName3) ? $imageName3 : null;

	if ($_FILES['works_proposed_doc']['name'] != "") {
		$ext = substr($_FILES['works_proposed_doc']['name'], strpos($_FILES['works_proposed_doc']['name'], "."));
		copy($_FILES['works_proposed_doc']['tmp_name'], $target . "workpupose-" . "-" . time() . $ext);
		$imageName4 = "workpupose-" . "-" . time() . $ext;
	}
	$workpupose = !empty($imageName4) ? $imageName4 : null;

	if ($_FILES['detail_tender_doc']['name'] != "") {
		$ext = substr($_FILES['detail_tender_doc']['name'], strpos($_FILES['detail_tender_doc']['name'], "."));
		copy($_FILES['detail_tender_doc']['tmp_name'], $target . "detailtender-" . "-" . time() . $ext);
		$imageName5 = "detailtender-" . "-" . time() . $ext;
	}
	$detailtender = !empty($imageName5) ? $imageName5 : null;

	if ($_FILES['fir_complaints_doc']['name'] != "") {
		$ext = substr($_FILES['fir_complaints_doc']['name'], strpos($_FILES['fir_complaints_doc']['name'], "."));
		copy($_FILES['fir_complaints_doc']['tmp_name'], $target . "fir-" . "-" . time() . $ext);
		$imageName6 = "fir-" . "-" . time() . $ext;
	}
	$fir_complaints_doc = !empty($imageName6) ? $imageName6 : null;

	if ($_FILES['property_doc']['name'] != "") {
		$ext = substr($_FILES['property_doc']['name'], strpos($_FILES['property_doc']['name'], "."));
		copy($_FILES['property_doc']['tmp_name'], $target . "property-" . "-" . time() . $ext);
		$imageName7 = "property-" . "-" . time() . $ext;
	}
	$property_doc = !empty($imageName7) ? $imageName7 : null;

	if ($_FILES['account_doc']['name'] != "") {
		$ext = substr($_FILES['account_doc']['name'], strpos($_FILES['account_doc']['name'], "."));
		copy($_FILES['account_doc']['tmp_name'], $target . "account-" . "-" . time() . $ext);
		$imageName8 = "account-" . "-" . time() . $ext;
	}
	$account_doc = !empty($imageName8) ? $imageName8 : null;

	if ($_FILES['revenue_doc']['name'] != "") {
		$ext = substr($_FILES['revenue_doc']['name'], strpos($_FILES['revenue_doc']['name'], "."));
		copy($_FILES['revenue_doc']['tmp_name'], $target . "revenue-" . "-" . time() . $ext);
		$imageName9 = "revenue-" . "-" . time() . $ext;
	}
	$revenue_doc = !empty($imageName9) ? $imageName9 : null;


	if ($_FILES['pending_cuc_case_doc']['name'] != "") {
		$ext = substr($_FILES['pending_cuc_case_doc']['name'], strpos($_FILES['pending_cuc_case_doc']['name'], "."));
		copy($_FILES['pending_cuc_case_doc']['tmp_name'], $target . "pcuccd-" . "-" . time() . $ext);
		$imageName10 = "pcuccd-" . "-" . time() . $ext;
	}
	$pending_cuc_case_doc = !empty($imageName10) ? $imageName10 : null;

	if ($_FILES['pending_rtwb_case_doc']['name'] != "") {
		$ext = substr($_FILES['pending_rtwb_case_doc']['name'], strpos($_FILES['pending_rtwb_case_doc']['name'], "."));
		copy($_FILES['pending_rtwb_case_doc']['tmp_name'], $target . "prtwbcd-" . "-" . time() . $ext);
		$imageName11 = "prtwbcd-" . "-" . time() . $ext;
	}
	$pending_rtwb_case_doc = !empty($imageName11) ? $imageName11 : null;

	if ($_FILES['site_rtwb_case_doc']['name'] != "") {
		$ext = substr($_FILES['site_rtwb_case_doc']['name'], strpos($_FILES['site_rtwb_case_doc']['name'], "."));
		copy($_FILES['site_rtwb_case_doc']['tmp_name'], $target . "srtwbcd-" . "-" . time() . $ext);
		$imageName12 = "srtwbcd-" . "-" . time() . $ext;
	}
	$site_rtwb_case_doc = !empty($imageName12) ? $imageName12 : null;

	$user = $database->get_results("SELECT name,user_id, establishment_office, current_designation, gender, user_dob, telephone1  from tbl_users where user_id = '" . $database->filter($_SESSION['admin_user_id']) . "'");
	$names = array(
		'pending_cuc_case_doc' => $pending_cuc_case_doc,
		'pending_rtwb_case_doc' => $pending_rtwb_case_doc,
		'site_rtwb_case_doc' => $site_rtwb_case_doc,

		'group_id' => $_POST['group_id'],
		'charge_handover' => $group11,
		'officer_user_id' => $results11[0]['user_id'],
		'officer_hrms_code' => $_POST['officer_hrms_code'],
		'officer_name' => $results11[0]['name'],
		'officer_designation' => $results115[0]['designation_id'],
		'pending_revenue_own_office' => $_POST['pending_revenue_own_office'],
		'pending_revenue_subordinate_office' => $_POST['pending_revenue_subordinate_office'],
		'pending_uncommand_cases' => $_POST['pending_uncommand_cases'],
		'pending_site_inspection' => $_POST['pending_site_inspection'],
		'done_site_inspection' => $_POST['done_site_inspection'],
		'vetted_reply_submitted' => $_POST['vetted_reply_submitted'],
		'vetted_reply_pending' => $_POST['vetted_reply_pending'],
		'tp_register' => $_POST['tp_register'],
		'profile_check' => $_POST['profile_check'],
		'outlet_notebook' => $_POST['outlet_notebook'],
		'jurisdiction_detail' => $_POST['jurisdiction_detail'],
		'court_cases' => $_POST['court_cases'],
		'arbitration_cases' => $_POST['arbitration_cases'],
		'absent_emp' => $_POST['absent_emp'],
		'pending_recovery_cases' => $_POST['pending_recovery_cases'],
		'pending_disciplinary_cases' => $_POST['pending_disciplinary_cases'],
		'pending_pension_cases' => $_POST['pending_pension_cases'],
		'pending_compassionate_cases' => $_POST['pending_compassionate_cases'],
		'pending_district_issues' => $_POST['pending_district_issues'],
		'land_encroachment_cases' => $_POST['land_encroachment_cases'],
		'pending_rti_applications' => $_POST['pending_rti_applications'],
		'contact_details' => $_POST['contact_details'],
		'cash_books' => $_POST['cash_books'],
		'cheque_books' => $_POST['cheque_books'],
		'gr_books' => $_POST['gr_books'],
		'available_funds' => $_POST['available_funds'],
		'pending_liability' => $_POST['pending_liability'],
		'works_proposed' => $_POST['works_proposed'],
		'estimate_pending' => $_POST['estimate_pending'],
		'completed_works' => $_POST['completed_works'],
		'in_progress_works' => $_POST['in_progress_works'],
		'tenders_floated' => $_POST['tenders_floated'],
		'tenders_opened' => $_POST['tenders_opened'],
		'tenders_pending' => $_POST['tenders_pending'],
		'critical_sites' => $_POST['critical_sites'],
		'govt_references' => $_POST['govt_references'],
		'pending_authority' => $_POST['pending_authority'],
		'fir_complaints_doc' => $fir_complaints_doc,
		'fir_complaints' => $_POST['fir_complaints'],
		'property_doc' => $property_doc,
		'property' => $_POST['property'],
		'created_by_group' => $_POST['group_id'],
		'upload_court_case' => $CourtCase,
		'upload_contact_list' => $Contactlist,
		'pending_liability_doc' => $libality,
		'works_proposed_doc' => $workpupose,
		'detail_tender_doc' => $detailtender,
		'account_doc' => $account_doc,
		'revenue_doc' => $revenue_doc,
		'emp_office' => $_SESSION['admin_establishment_office'],
		'created_at' => date("Y-m-d H:i:s", time()),
		'created_by' => $_SESSION['admin_user_id'],
		'stage' => 0,
		'status' => 0,
		'status_text' => 'Pending',
		'des_id' => $user[0]['current_designation'],
		'emp_name' => $user[0]['name'],
	);

	$add_query = $database->insert('tbl_cto_cho', $names);
	$lastInsertedId1 = $database->lastid();

	$names1['request_id'] = $lastInsertedId1;
	$names1['action'] = $_SESSION['admin_name'] . " (" . $group11 . ") Created the request.";
	$names1['updated_by'] = $_SESSION['admin_user_id'];
	$names1['ipaddress'] = $ipaddress;
	$names1['updated_at'] = date("Y-m-d H:i:s", time());
	$add_query1 = $database->insert('tbl_cto_cholog', $names1);


	if ($add_query) {
		activityLogs($_SESSION['admin_name'] . " (" . $group11 . ") Created the request.", 1, 'web', $lastInsertedId1, 'CHO');


		print "<script>window.location='index.php?c=" . $component . "&Cid=" . $cid . "'</script>";
	}
}






function savewithdrawnval()
{
	global $database, $component;
	$cid = $_POST['CId'];
	$pid = $_POST['request_id'];

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	}
	$update = array(
		'updated_at' => date("Y-m-d H:i:s", time()),
		'updated_by' => $_SESSION['admin_user_id'],
		//'withdraw_request_remark'           => $_POST['remarkmessage'],
		'status' => 12,
		'status_text' => 'Withdrawn'
	);


	$where = array(
		'id' => $pid
	);

	$database->update('tbl_cto_cho', $update, $where, 1);
	$lastInsertedId = $pid;
	$query = $database->get_results("SELECT * FROM tbl_cto_cho WHERE id = '" . $database->filter($pid) . "'");

	if ($query[0]['group_id'] == 7) {
		$group11 = "Executive Engineer";
	} elseif ($query[0]['group_id'] == 67) {
		$group11 = "Sub Divisional Officer";
	} elseif ($query[0]['group_id'] == 58) {
		$group11 = "Ziledar";
	} elseif ($query[0]['group_id'] == 60) {
		$group11 = "Junior Engineer";
	} elseif ($query[0]['group_id'] == 8) {
		$group11 = "Superintending Engineer";
	} elseif ($query[0]['group_id'] == 79) {
		$group11 = "Deputy Collector";
	}


	$admin_groupid = explode(",", $_SESSION['admin_groupid']);


	if (isset($_POST['remarkmessage']) && $_POST['remarkmessage'] != "") {
		$action1 = $_SESSION['admin_name'] . "((" . $group11 . ") withdrawn the request.<br/>Remark:'" . $_POST['remarkmessage'] . "'";
	} else {
		$action1 = $_SESSION['admin_name'] . "((" . $group11 . ") withdrawn the request.";
	}
	$text1 = $_SESSION['admin_name'] . "((" . $group11 . ") withdrawn the request.";






	$names = array(
		'request_id' => $pid,
		'action' => $action1,
		'updated_by' => $_SESSION['admin_user_id'],
		'updated_at' => date("Y-m-d H:i:s", time()),
		'ipaddress' => $ipaddress
	);


	$add_query = $database->insert('tbl_cto_cholog', $names);




	activityLogs($_SESSION['admin_name'] . $text1, 1, 'web', $lastInsertedId, 'TEMP');


	//exit;
	print "<script>window.location='index.php?c=" . $component . "&Cid=" . $cid . "&request_id=" . $pid . "'</script>";
}

function showwithdrawn($id)
{
	global $database;
	$pid = $_GET['request_id'];
	$sql = "SELECT * FROM tbl_cto_cho where id='" . $pid . "'";
	$results = $database->get_results($sql);
	showwithdrawnForm($results);
}




function saveModificationsOperation()
{
	global $database, $component;
	$cid = $_POST['CId'];
	$userPublished = $_POST['rdoPublished'];
	$userId = $_POST['userId'];
	$type = $_REQUEST['type'];
	$cho_stage = $_REQUEST['stage'];
	$back = $_REQUEST['back'];

	$target = PATH . "images/cto_cho/";

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	}


	$admin_groupid = explode(",", $_SESSION['admin_groupid']);
	if ($_POST['group_id'] == 7) {
		$group11 = "Executive Engineer";
	} elseif ($_POST['group_id'] == 67) {
		$group11 = "Sub Divisional Officer";
	} elseif ($_POST['group_id'] == 58) {
		$group11 = "Ziledar";
	} elseif ($_POST['group_id'] == 60) {
		$group11 = "Junior Engineer";
	} elseif ($_POST['group_id'] == 8) {
		$group11 = "Superintending Engineer";
	} elseif ($_POST['group_id'] == 79) {
		$group11 = "Deputy Collector";
	}

	if ($_FILES['upload_court_case']['name'] != "") {
		$ext = substr($_FILES['upload_court_case']['name'], strpos($_FILES['upload_court_case']['name'], "."));
		copy($_FILES['upload_court_case']['tmp_name'], $target . "CourtCase-" . "-" . time() . $ext);
		$imageName1 = "CourtCase-" . "-" . time() . $ext;
	}
	$CourtCase = !empty($imageName1) ? $imageName1 : null;

	if ($_FILES['upload_contact_list']['name'] != "") {
		$ext = substr($_FILES['upload_contact_list']['name'], strpos($_FILES['upload_contact_list']['name'], "."));
		copy($_FILES['upload_contact_list']['tmp_name'], $target . "Contactlist-" . "-" . time() . $ext);
		$imageName2 = "Contactlist-" . "-" . time() . $ext;
	}
	$Contactlist = !empty($imageName2) ? $imageName2 : null;

	if ($_FILES['pending_liability_doc']['name'] != "") {
		$ext = substr($_FILES['pending_liability_doc']['name'], strpos($_FILES['pending_liability_doc']['name'], "."));
		copy($_FILES['pending_liability_doc']['tmp_name'], $target . "libality-" . "-" . time() . $ext);
		$imageName3 = "libality-" . "-" . time() . $ext;
	}
	$libality = !empty($imageName3) ? $imageName3 : null;

	if ($_FILES['works_proposed_doc']['name'] != "") {
		$ext = substr($_FILES['works_proposed_doc']['name'], strpos($_FILES['works_proposed_doc']['name'], "."));
		copy($_FILES['works_proposed_doc']['tmp_name'], $target . "workpupose-" . "-" . time() . $ext);
		$imageName4 = "workpupose-" . "-" . time() . $ext;
	}
	$workpupose = !empty($imageName4) ? $imageName4 : null;

	if ($_FILES['detail_tender_doc']['name'] != "") {
		$ext = substr($_FILES['detail_tender_doc']['name'], strpos($_FILES['detail_tender_doc']['name'], "."));
		copy($_FILES['detail_tender_doc']['tmp_name'], $target . "detailtender-" . "-" . time() . $ext);
		$imageName5 = "detailtender-" . "-" . time() . $ext;
	}
	$detailtender = !empty($imageName5) ? $imageName5 : null;

	if ($_FILES['fir_complaints_doc']['name'] != "") {
		$ext = substr($_FILES['fir_complaints_doc']['name'], strpos($_FILES['fir_complaints_doc']['name'], "."));
		copy($_FILES['fir_complaints_doc']['tmp_name'], $target . "fir-" . "-" . time() . $ext);
		$imageName6 = "fir-" . "-" . time() . $ext;
	}
	$fir_complaints_doc = !empty($imageName6) ? $imageName6 : null;

	if ($_FILES['property_doc']['name'] != "") {
		$ext = substr($_FILES['property_doc']['name'], strpos($_FILES['property_doc']['name'], "."));
		copy($_FILES['property_doc']['tmp_name'], $target . "property-" . "-" . time() . $ext);
		$imageName7 = "property-" . "-" . time() . $ext;
	}
	$property_doc = !empty($imageName7) ? $imageName7 : null;

	if ($_FILES['account_doc']['name'] != "") {
		$ext = substr($_FILES['account_doc']['name'], strpos($_FILES['account_doc']['name'], "."));
		copy($_FILES['account_doc']['tmp_name'], $target . "account-" . "-" . time() . $ext);
		$imageName8 = "account-" . "-" . time() . $ext;
	}
	$account_doc = !empty($imageName8) ? $imageName8 : null;

	if ($_FILES['revenue_doc']['name'] != "") {
		$ext = substr($_FILES['revenue_doc']['name'], strpos($_FILES['revenue_doc']['name'], "."));
		copy($_FILES['revenue_doc']['tmp_name'], $target . "revenue-" . "-" . time() . $ext);
		$imageName9 = "revenue-" . "-" . time() . $ext;
	}
	$revenue_doc = !empty($imageName9) ? $imageName9 : null;

	if ($_FILES['pending_cuc_case_doc']['name'] != "") {
		$ext = substr($_FILES['pending_cuc_case_doc']['name'], strpos($_FILES['pending_cuc_case_doc']['name'], "."));
		copy($_FILES['pending_cuc_case_doc']['tmp_name'], $target . "pcuccd-" . "-" . time() . $ext);
		$imageName10 = "pcuccd-" . "-" . time() . $ext;
	}
	$pending_cuc_case_doc = !empty($imageName10) ? $imageName10 : null;
	if ($_FILES['pending_rtwb_case_doc']['name'] != "") {
		$ext = substr($_FILES['pending_rtwb_case_doc']['name'], strpos($_FILES['pending_rtwb_case_doc']['name'], "."));
		copy($_FILES['pending_rtwb_case_doc']['tmp_name'], $target . "prtwbcd-" . "-" . time() . $ext);
		$imageName11 = "prtwbcd-" . "-" . time() . $ext;
	}
	$pending_rtwb_case_doc = !empty($imageName11) ? $imageName11 : null;

	if ($_FILES['site_rtwb_case_doc']['name'] != "") {
		$ext = substr($_FILES['site_rtwb_case_doc']['name'], strpos($_FILES['site_rtwb_case_doc']['name'], "."));
		copy($_FILES['site_rtwb_case_doc']['tmp_name'], $target . "srtwbcd-" . "-" . time() . $ext);
		$imageName12 = "srtwbcd-" . "-" . time() . $ext;
	}
	$site_rtwb_case_doc = !empty($imageName12) ? $imageName12 : null;

	$query = $database->get_results("SELECT * FROM tbl_cto_cho WHERE id = '" . $database->filter($userId) . "'");

	$sql11 = "SELECT * FROM tbl_users WHERE (user_code = '" . $database->filter($_POST['officer_hrms_code']) . "' OR user_id = '" . $database->filter($_POST['officer_user_id']) . "' OR user_code = '" . $database->filter($_POST['higher_authority_hrms']) . "') AND user_status = 1";
	$results11 = $database->get_results($sql11);

	$sql115 = "SELECT * FROM tbl_designation where designation_id = '" . $database->filter($results11[0]['current_designation']) . "'";
	$results115 = $database->get_results($sql115);


	if (isset($type) && $type == 1) {
		$update = [
			'officer_hrms_code' => !empty($_POST['officer_hrms_code']) ? $_POST['officer_hrms_code'] : $results11[0]['user_code'],
			'officer_user_id' => !empty($_POST['officer_user_id']) ? $_POST['officer_user_id'] : $results11[0]['user_id'],
			'office_id' => $_POST['office_id'],
			'officer_name' => !empty($_POST['officer_name']) ? $_POST['officer_name'] : $results11[0]['name'],
			'officer_designation' => !empty($_POST['officer_designation']) ? $_POST['officer_designation'] : $results115[0]['designation_name'],
			'designation_id' => $_POST['designation_id'],
			'updated_at' => date("Y-m-d H:i:s", time()),
			'updated_by' => $_SESSION['admin_user_id'],
			// 'stage'                 => 1,
			'status' => 1,
			'status_text' => 'In Process',
			'search_method' => $_POST['search_method'],
		];

		if (isset($cho_stage) && $cho_stage == 4) {
			$update['stage'] = 5;
			$update['reject_stage'] = 0;
			$update['sendback'] = 'CTO';
			$stage = 5;
			$sm_action = $_SESSION['admin_name'] . " (HO) has updated and forwarded the request to CTO:" . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
		} else {


			$update['stage'] = 1;
			$update['sendback'] = 'CTO';
			$stage = 1;
			$update['reject_stage'] = 0;

			$officer_hrms_code = !empty($_POST['officer_hrms_code']) ? $_POST['officer_hrms_code'] : $results11[0]['user_code'];
			if ($query[0]['sendback'] == 'CTO') {
				$sm_action = $_SESSION['admin_name'] . " (CHO) has updated and forwarded the request to CTO:" . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
			} else {
				$sm_action = $_SESSION['admin_name'] . " (CHO) has forwarded the request to CTO:" . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
			}
		}
	} else if (isset($type) && $type == 2) {
		$update = [
			'higher_authority_hrms' => !empty($_POST['higher_authority_hrms']) ? $_POST['higher_authority_hrms'] : $results11[0]['user_code'],
			'higher_authority_id' => !empty($_POST['officer_user_id']) ? $_POST['officer_user_id'] : $results11[0]['user_id'],
			'higher_authority' => !empty($_POST['officer_name']) ? $_POST['officer_name'] : $results11[0]['name'],
			'officer_designation' => !empty($_POST['officer_designation']) ? $_POST['officer_designation'] : $results115[0]['designation_name'],
			'office_id' => $_POST['office_id'],
			'designation_id' => $_POST['designation_id'],
			'updated_at' => date("Y-m-d H:i:s", time()),
			'updated_by' => $_SESSION['admin_user_id'],
			// 'stage'                 => 1,
			'status' => 1,
			'status_text' => 'In Process',
			'search_method1' => $_POST['search_method1'],

		];


		$update['stage'] = 4;
		$update['reject_stage'] = 0;
		$update['sendback'] = 'HO';
		$stage = 4;
		$officer_name = !empty($_POST['officer_name']) ? $_POST['officer_name'] : $results11[0]['name'];
		$higher_authority = !empty($_POST['higher_authority_hrms']) ? $_POST['higher_authority_hrms'] : $results11[0]['user_code'];

		$higher_authority_hrms = !empty($_POST['higher_authority_hrms']) ? $_POST['higher_authority_hrms'] : $results11[0]['user_code'];
		if ($query[0]['sendback'] == 'HO') {
			$sm_action = $_SESSION['admin_name'] . " (CHO) has updated and forwarded the request to Higher Authority: " .
				$officer_name . " (" . $higher_authority . ")";
		} else {
			$sm_action = $_SESSION['admin_name'] . " (CHO) has forwarded the request to Higher Authority: " .
				$officer_name . " (" . $higher_authority . ")";
		}
	} else {
		$update = array(
			'pending_cuc_case_doc' => $pending_cuc_case_doc,
			'pending_rtwb_case_doc' => $pending_rtwb_case_doc,
			'site_rtwb_case_doc' => $site_rtwb_case_doc,
			'group_id' => $_POST['group_id'],
			'charge_handover' => $group11,
			'officer_user_id' => $results11[0]['user_id'],
			'officer_hrms_code' => $_POST['officer_hrms_code'],
			'officer_name' => $results11[0]['name'],
			'officer_designation' => $results115[0]['designation_id'],
			'pending_revenue_own_office' => $_POST['pending_revenue_own_office'],
			'pending_revenue_subordinate_office' => $_POST['pending_revenue_subordinate_office'],
			'pending_uncommand_cases' => $_POST['pending_uncommand_cases'],
			'pending_site_inspection' => $_POST['pending_site_inspection'],
			'done_site_inspection' => $_POST['done_site_inspection'],
			'vetted_reply_submitted' => $_POST['vetted_reply_submitted'],
			'vetted_reply_pending' => $_POST['vetted_reply_pending'],
			'tp_register' => $_POST['tp_register'],
			'outlet_notebook' => $_POST['outlet_notebook'],
			'jurisdiction_detail' => $_POST['jurisdiction_detail'],
			'court_cases' => $_POST['court_cases'],
			'arbitration_cases' => $_POST['arbitration_cases'],
			'absent_emp' => $_POST['absent_emp'],
			'pending_recovery_cases' => $_POST['pending_recovery_cases'],
			'pending_disciplinary_cases' => $_POST['pending_disciplinary_cases'],
			'pending_pension_cases' => $_POST['pending_pension_cases'],
			'pending_compassionate_cases' => $_POST['pending_compassionate_cases'],
			'pending_district_issues' => $_POST['pending_district_issues'],
			'land_encroachment_cases' => $_POST['land_encroachment_cases'],
			'pending_rti_applications' => $_POST['pending_rti_applications'],
			'contact_details' => $_POST['contact_details'],
			'cash_books' => $_POST['cash_books'],
			'cheque_books' => $_POST['cheque_books'],
			'gr_books' => $_POST['gr_books'],
			'available_funds' => $_POST['available_funds'],
			'pending_liability' => $_POST['pending_liability'],
			'works_proposed' => $_POST['works_proposed'],
			'estimate_pending' => $_POST['estimate_pending'],
			'completed_works' => $_POST['completed_works'],
			'in_progress_works' => $_POST['in_progress_works'],
			'tenders_floated' => $_POST['tenders_floated'],
			'tenders_opened' => $_POST['tenders_opened'],
			'tenders_pending' => $_POST['tenders_pending'],
			'critical_sites' => $_POST['critical_sites'],
			'govt_references' => $_POST['govt_references'],
			'pending_authority' => $_POST['pending_authority'],
			'fir_complaints_doc' => $fir_complaints_doc,
			'fir_complaints' => $_POST['fir_complaints'],
			'property_doc' => $property_doc,
			'property' => $_POST['property'],
			'upload_court_case' => $CourtCase,
			'upload_contact_list' => $Contactlist,
			'pending_liability_doc' => $libality,
			'works_proposed_doc' => $workpupose,
			'detail_tender_doc' => $detailtender,
			'account_doc' => $account_doc,
			'revenue_doc' => $revenue_doc,
			'updated_at' => date("Y-m-d H:i:s", time()),
			'updated_by' => $_SESSION['admin_user_id'],
			// 'stage'                               => 2,
			'status' => 0,
			'reject_stage' => 0,
			'status_text' => 'in Process'

		);

		$update['stage'] = 0;
		$stage = 0;
		$sm_action = $_SESSION['admin_name'] . " (" . $group11 . ") Updated the request.";
	}

	$where_clause = array(
		'id' => $userId
	);

	$updated = $database->update('tbl_cto_cho', $update, $where_clause, 1);
	if ($updated) {

		$names1 = array(
			'request_id' => $userId,
			'ipaddress' => $ipaddress,
			'action' => $sm_action,
			// 'file'	=> $jsonFilenames,
			'stage' => $stage,
			'updated_by' => $_SESSION['admin_user_id'],
			'updated_at' => date("Y-m-d H:i:s", time())
		);

		$database->insert('tbl_cto_cholog', $names1);

		activityLogs($sm_action, 1, 'web', $userId, 'CHO');

		print "<script>window.location='index.php?c=" . $component . "&Cid=" . $cid . "'</script>";
	}
}

function createFormForPages($id)
{
	global $database;
	$sql = "SELECT * FROM  tbl_cto_cho where id='" . $database->filter($id) . "'";
	$results = $database->get_results($sql);
	createFormForPagesHtml($results);
}

function removeSelectedItems()
{
	global $database, $component;
	$cid = $_REQUEST['Cid'];
	$msg = $_REQUEST['msg'];
	$update = array(
		'view_status' => 0,
		'deleted_reason' => $msg
	);
	$where_clause = array('id' => $_GET['id']);

	$updated = $database->update('tbl_cto_cho', $update, $where_clause, 1);
	if ($updated) {
		activityLogs($_SESSION['admin_name'] . " (" . $_SESSION['admin_user_code'] . ") deleted the request.", 1, 'web', $_GET['id'], 'CHO');

		print "<script>window.location='index.php?c=" . $component . "&Cid=" . $cid . "'</script>";
	}
}


function updateStatus()
{
	global $database, $component;
	$cid = $_POST['CId'];
	$userId = $_REQUEST['id'];
	$type = $_REQUEST['type'];
	$back = $_REQUEST['back'];
	$target = PATH . "images/cto_cho/";

	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	}

	$admin_groupid = explode(",", $_SESSION['admin_groupid']);
	$update = array(
		'updated_at' => date("Y-m-d H:i:s", time()),
		'updated_by' => $_SESSION['admin_user_id']
	);

	if ($_FILES['ev_remarkdoc']['name'] != "") {
		$ext = substr($_FILES['ev_remarkdoc']['name'], strpos($_FILES['ev_remarkdoc']['name'], "."));
		copy($_FILES['ev_remarkdoc']['tmp_name'], $target . "cho_cto-" . "-" . time() . $ext);
		$imageName = "cho_cto-" . "-" . time() . $ext;
	}
	$jsonFilenames = !empty($imageName) ? $imageName : null;

	$query = $database->get_results("SELECT * FROM tbl_cto_cho WHERE id = '" . $database->filter($userId) . "'");

	$sql11 = "SELECT * FROM tbl_users WHERE user_id = '" . $database->filter($query[0]['created_by']) . "'";
	$results11 = $database->get_results($sql11);

	if (isset($type)) {
		if ($type == 3) {
			$update['stage'] = 3; //accept the charge
			$update['status'] = 2;
			$update['reject_stage'] = 3;
			$update['status_text'] = 'Charge Accepted';
			$text = $_SESSION['admin_name'] . " (CTO) has accepted the caharge of (" . $query[0]['charge_handover'] . ")";
			$stage = '3';
		} else if ($type == 4) {
			$update['stage'] = 0; //sendback to CHO
			$update['status'] = 1;
			$update['reject_stage'] = 1;
			$update['status_text'] = 'In Process';
			$text = $_SESSION['admin_name'] . " (" . $_SESSION['admin_user_code'] . ") (CTO) has sendback the request to the CHO: " . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
			$stage = '0';
		} else if ($type == 5) {
			$update['stage'] = 0; //sendback to CHO
			$update['status'] = 1;
			$update['reject_stage'] = 1;
			$update['status_text'] = 'In Process';
			$text = $_SESSION['admin_name'] . " (" . $_SESSION['admin_user_code'] . ") (HO) has sendback the request to the CHO: " . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
			$stage = '0';
		} else if ($type == 13) {
			$update['stage'] = 4; //sendback
			$update['status'] = 1;
			$update['reject_stage'] = 0;
			$update['sendback'] = 'HO';
			$update['status_text'] = 'In Process';
			$text = $_SESSION['admin_name'] . " (CTO) has sendback the request to Higher Authority:" . $results11[0]['name'] . " (" . $results11[0]['user_code'] . ")";
			$stage = '4';
		}
	}
	$where_clause = array(
		'id' => $userId,
	);

	activityLogs($text, 1, 'web', $userId, 'CHO');

	$updated = $database->update('tbl_cto_cho', $update, $where_clause, 1);
	if (isset($_POST['ev_remarkmessage']) && $_POST['ev_remarkmessage'] != "") {
		$text = $text . " Remark: " . $_POST['ev_remarkmessage'];
	}
	$names = array(
		'request_id' => $userId,
		'action' => $text,
		'file' => $jsonFilenames,
		'stage' => $stage,
		'ipaddress' => $ipaddress,
		'updated_by' => $_SESSION['admin_user_id'],
		'updated_at' => date("Y-m-d H:i:s", time()),
		//'applicant_status' => $applicant_status1		 	
	);

	$add_query = $database->insert('tbl_cto_cholog', $names);

	if ($updated) {
		print "<script>window.location='index.php?c=" . $component . "&Cid=" . $cid . "'</script>";
	}
}
