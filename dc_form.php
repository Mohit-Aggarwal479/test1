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
			<h4> Adminstrator</h4>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Detail of Jurisdiction</label>
				<input type="text" name="jurisdiction_detail" id="jurisdiction_detail" value="<?php echo isset($rowData['jurisdiction_detail']) ? $rowData['jurisdiction_detail'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Disciplinary action/ Enquiry Cases</label>
				<input type="number" name="court_cases" id="court_cases" value="<?php echo isset($rowData['court_cases']) ? $rowData['court_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">T & P Register/Stock Register</label>
				<input type="number" name="tp_register" id="tp_register" value="<?php echo isset($rowData['tp_register']) ? $rowData['tp_register'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<h4> Revenue</h4>
		</div>
		<div class="col-md-12">
			<br />
			<h5> Pending Warabandi cases</h5>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Own Office</label>
				<input type="number" name="pending_revenue_own_office" id="pending_revenue_own_office" value="<?php echo isset($rowData['pending_revenue_own_office']) ? $rowData['pending_revenue_own_office'] : ''; ?>" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Subordinate Office</label>
				<input type="number" name="pending_revenue_subordinate_office" id="pending_revenue_subordinate_office" value="<?php echo isset($rowData['pending_revenue_subordinate_office']) ? $rowData['pending_revenue_subordinate_office'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<div class="form-group">
				<h5> Pending Command to Uncommand Cases</h5>

			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Own Office</label>
				<input type="number" name="pending_uncommand_cases" id="pending_uncommand_cases" value="<?php echo isset($rowData['pending_uncommand_cases']) ? $rowData['pending_uncommand_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Subordinate Office</label>
				<input type="number" name="pending_recovery_cases" id="pending_recovery_cases" value="<?php echo isset($rowData['pending_recovery_cases']) ? $rowData['pending_recovery_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label">Pending Warabandi Court cases</label>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<input type="number" name="contact_details" id="contact_details" value="<?php echo isset($rowData['contact_details']) ? $rowData['contact_details'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<input type="file" name="upload_contact_list" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['upload_contact_list']) && $rowData['upload_contact_list'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['upload_contact_list'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label">Pending judgments to be written or announced</label>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">

				<input type="file" name="detail_tender_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['detail_tender_doc']) && $rowData['detail_tender_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['detail_tender_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<input type="number" name="tenders_floated" id="tenders_floated" value="<?php echo isset($rowData['tenders_floated']) ? $rowData['tenders_floated'] : ''; ?>" class="form-control">
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