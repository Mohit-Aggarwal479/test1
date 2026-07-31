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
				<label class="form-label">Court Cases</label>
				<input type="number" name="court_cases" id="court_cases" value="<?php echo isset($rowData['court_cases']) ? $rowData['court_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Arbitration Cases</label>
				<input type="number" name="arbitration_cases" id="arbitration_cases" value="<?php echo isset($rowData['arbitration_cases']) ? $rowData['arbitration_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Disciplinary Action Cases</label>
				<input type="number" name="pending_disciplinary_cases" id="pending_disciplinary_cases" value="<?php echo isset($rowData['pending_disciplinary_cases']) ? $rowData['pending_disciplinary_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Pension Cases</label>
				<input type="number" name="pending_pension_cases" id="pending_pension_cases" value="<?php echo isset($rowData['pending_pension_cases']) ? $rowData['pending_pension_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Compassionate Ground Cases</label>
				<input type="number" name="pending_compassionate_cases" id="pending_compassionate_cases" value="<?php echo isset($rowData['pending_compassionate_cases']) ? $rowData['pending_compassionate_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Issues with District Administration or any other authority</label>
				<input type="number" name="pending_district_issues" id="pending_district_issues" value="<?php echo isset($rowData['pending_district_issues']) ? $rowData['pending_district_issues'] : ''; ?>" class="form-control" required>
			</div>
		</div>


		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label">List of Contact Details of Office and field staff, district administration, other officers/ stakeholders/ Progressive farmers</label>
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
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending RTI Applications</label>
				<input type="number" name="pending_rti_applications" id="pending_rti_applications" value="<?php echo isset($rowData['pending_rti_applications']) ? $rowData['pending_rti_applications'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>
				<input type="file" name="upload_court_case" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['upload_court_case']) && $rowData['upload_court_case'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['upload_court_case'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-12">
			<br />
			<h4> Accounts </h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Cash Books</label>
				<input type="text" name="cash_books" id="cash_books" value="<?php echo isset($rowData['cash_books']) ? $rowData['cash_books'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<h4> Works </h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Works Proposed to be Executed in Near Future</label>
				<input type="text" name="works_proposed" id="works_proposed" value="<?php echo isset($rowData['works_proposed']) ? $rowData['works_proposed'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Estimate, Without Calling, Approval of Rates Pending in Higher Office</label>
				<input type="text" name="estimate_pending" id="estimate_pending" value="<?php echo isset($rowData['estimate_pending']) ? $rowData['estimate_pending'] : ''; ?>" class="form-control">
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="works_proposed_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['works_proposed_doc']) && $rowData['works_proposed_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['works_proposed_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-12">
			<br />
			<h4> Details of Ongoing Works </h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Completed</label>
				<input type="number" name="completed_works" id="completed_works" value="<?php echo isset($rowData['completed_works']) ? $rowData['completed_works'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">In Progress</label>
				<input type="number" name="in_progress_works" id="in_progress_works" value="<?php echo isset($rowData['in_progress_works']) ? $rowData['in_progress_works'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Critical/Vulnerable Sites</label>
				<input type="number" name="critical_sites" id="critical_sites" value="<?php echo isset($rowData['critical_sites']) ? $rowData['critical_sites'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Issue to be taken up with Govt. and district administration</label>
				<input type="text" name="govt_references" id="govt_references" value="<?php echo isset($rowData['govt_references']) ? $rowData['govt_references'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<h4> Revenue </h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Revenue/ Appeal cases</label>
				<input type="number" name="pending_revenue_own_office" id="pending_revenue_own_office" value="<?php echo isset($rowData['pending_revenue_own_office']) ? $rowData['pending_revenue_own_office'] : ''; ?>" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending judgments to be written or announced</label>
				<input type="number" name="property" id="property" value="<?php echo isset($rowData['property']) ? $rowData['property'] : ''; ?>" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label"> Pending Command to Uncommand Cases</label>
				<input type="number" name="pending_uncommand_cases" id="pending_uncommand_cases" value="<?php echo isset($rowData['pending_uncommand_cases']) ? $rowData['jurisdiction_detail'] : ''; ?>" class="form-control" required>
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