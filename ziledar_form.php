<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include "../../../../private/settings.php";

$type = $_POST['type'] ?? null;
$rowData = isset($_POST['rowData']) ? json_decode($_POST['rowData'], true) : [];

if (!is_array($rowData)) {
	echo "Error: rowData is not an array.";
	exit;
}

?>

<div class="container card bg-light text-dark">
	<div class="row">

		<div class="col-md-12">
			<br />
			<h4> Pending Revenue/ Warabandi/ Tawan/ Breach Cases</h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Own Office</label>
				<input type="number" name="pending_revenue_own_office" id="pending_revenue_own_office"
					value="<?php echo isset($rowData['pending_revenue_own_office']) ? $rowData['pending_revenue_own_office'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Subordinate Office</label>
				<input type="number" name="pending_revenue_subordinate_office" id="pending_revenue_subordinate_office"
					value="<?php echo isset($rowData['pending_revenue_subordinate_office']) ? $rowData['pending_revenue_subordinate_office'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label"> Upload Doc</label>
				<input type="file" name="pending_rtwb_case_doc" id="pending_rtwb_case_doc"
					value="<?php echo isset($rowData['pending_rtwb_case_doc']) ? $rowData['pending_rtwb_case_doc'] : ''; ?>"
					class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<br />
			<div class="form-group">
				<h4> Pending Command to Uncommand Cases</h4>
				<input type="number" name="pending_uncommand_cases" id="pending_uncommand_cases"
					value="<?php echo isset($rowData['pending_uncommand_cases']) ? $rowData['pending_uncommand_cases'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<br />
			<div class="form-group">
				<h4> Upload Doc</h4>
				<input type="file" name="pending_cuc_case_doc" id="pending_cuc_case_doc"
					value="<?php echo isset($rowData['pending_cuc_case_doc']) ? $rowData['pending_cuc_case_doc'] : ''; ?>"
					class="form-control">
			</div>
		</div>


		<div class="col-md-12">
			<br />
			<h4>Site Inspection of Revenue/ Warabandi/ Tawan/ Breach Cases</h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending</label>
				<input type="number" name="pending_site_inspection" id="pending_site_inspection"
					value="<?php echo isset($rowData['pending_site_inspection']) ? $rowData['pending_site_inspection'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Done But Report is Pending</label>
				<input type="number" name="done_site_inspection" id="done_site_inspection"
					value="<?php echo isset($rowData['done_site_inspection']) ? $rowData['done_site_inspection'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label"> Upload Doc</label>
				<input type="file" name="site_rtwb_case_doc" id="site_rtwb_case_doc"
					value="<?php echo isset($rowData['site_rtwb_case_doc']) ? $rowData['site_rtwb_case_doc'] : ''; ?>"
					class="form-control">
			</div>
		</div>
		<div class="col-md-12">
			<br />
			<h4>Detail of Court Cases</h4>
		</div>


		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label">Upload Document</label>
				<input type="file" name="upload_court_case" id="file" class="form-control"
					accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['upload_court_case']) && $rowData['upload_court_case'] != "") { ?> <a
						href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['upload_court_case'] ?>">View
						File</a> <?php } ?>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Vetted Reply Submitted</label>
				<input type="number" name="vetted_reply_submitted" id="vetted_reply_submitted"
					value="<?php echo isset($rowData['vetted_reply_submitted']) ? $rowData['vetted_reply_submitted'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Vetted Reply Pending</label>
				<input type="number" name="vetted_reply_pending" id="vetted_reply_pending"
					value="<?php echo isset($rowData['vetted_reply_pending']) ? $rowData['vetted_reply_pending'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>


		<div class="col-md-12">
			<br />
			<div class="form-group">
				<h4>T & P Register/Stock Register</h4>
				<input type="text" name="tp_register" id="tp_register"
					value="<?php echo isset($rowData['tp_register']) ? $rowData['tp_register'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<div class="form-group">
				<h4>Outlet Notebook</h4>
				<input type="text" name="outlet_notebook" id="outlet_notebook"
					value="<?php echo isset($rowData['outlet_notebook']) ? $rowData['outlet_notebook'] : ''; ?>"
					class="form-control" required>
			</div>
		</div>



	</div>
	<div class="col-md-12">
		<div class="card-footer text-right">
			<button type="submit" class="btn btn-lg btn-primary">Submit</button>
			<button type="button" class="btn btn-lg btn-danger"
				onclick="window.location='?c=<?php echo $component ?>&Cid=<?php echo $menuid['component_headingid']; ?>'">
				Cancel
			</button>
		</div>
	</div>
</div>