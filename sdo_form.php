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
				<label class="form-label">Pending Recovery Cases</label>
				<input type="number" name="pending_recovery_cases" id="pending_recovery_cases" value="<?php echo isset($rowData['pending_recovery_cases']) ? $rowData['pending_recovery_cases'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">List of Absent Employees</label>
				<input type="number" name="absent_emp" id="absent_emp" class="form-control" value="<?php echo isset($rowData['absent_emp']) ? $rowData['absent_emp'] : ''; ?>" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>
				<input type="file" name="upload_court_case" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['upload_court_case']) && $rowData['upload_court_case'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['upload_court_case'] ?>">View File</a> <?php } ?>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending issues with district administration or any other authority</label>
				<input type="text" name="pending_authority" id="pending_authority" value="<?php echo isset($rowData['pending_authority']) ? $rowData['pending_authority'] : ''; ?>" class="form-control" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Land Encroachment Cases</label>
				<input type="number" name="pending_disciplinary_cases" id="pending_disciplinary_cases" value="<?php echo isset($rowData['pending_disciplinary_cases']) ? $rowData['pending_disciplinary_cases'] : ''; ?>" class="form-control" required>
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
				<label class="form-label">Pending RTI Applications</label>
				<input type="number" name="pending_rti_applications" id="pending_rti_applications" value="<?php echo isset($rowData['pending_rti_applications']) ? $rowData['pending_rti_applications'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-group">
				<label class="form-label">List of contact details of Office Staff, district administration, other officers/ stakeholders/ Progressive farmers</label>
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
				<label class="form-label">Detail of FIR’s/Complaint /Cases registered against illegal activities, encroachment, etc</label>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<input type="text" name="fir_complaints" id="fir_complaints" value="<?php echo isset($rowData['fir_complaints']) ? $rowData['fir_complaints'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<input type="file" name="fir_complaints_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['fir_complaints_doc']) && $rowData['fir_complaints_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['fir_complaints_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Property Register</label>

				<input type="text" name="property" id="fir_complaints" value="<?php echo isset($rowData['property']) ? $rowData['property'] : ''; ?>" class="form-control" required>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="property_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['property_doc']) && $rowData['property_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['property_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-12">
			<br />
			<h4> Accounts </h4>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="account_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['account_doc']) && $rowData['account_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['account_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Cash Books</label>
				<input type="text" name="cash_books" id="cash_books" value="<?php echo isset($rowData['cash_books']) ? $rowData['cash_books'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Cheque Books</label>
				<input type="text" name="cheque_books" id="cheque_books" value="<?php echo isset($rowData['cheque_books']) ? $rowData['cheque_books'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">G.R. Books</label>
				<input type="text" name="gr_books" id="gr_books" value="<?php echo isset($rowData['gr_books']) ? $rowData['gr_books'] : ''; ?>" class="form-control">
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Liability</label>
				<input type="text" name="pending_liability" id="pending_liability" value="<?php echo isset($rowData['pending_liability']) ? $rowData['pending_liability'] : ''; ?>" class="form-control">
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Final bills to be submitted</label>
				<input type="text" name="available_funds" id="available_funds" value="<?php echo isset($rowData['available_funds']) ? $rowData['available_funds'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="pending_liability_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['pending_liability_doc']) && $rowData['pending_liability_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['pending_liability_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-12">
			<br />
			<h4> Works </h4>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending works proposed to be executed in near future</label>
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
				<label class="form-label">Details of Ongoing Works</label>
				<input type="text" name="govt_references" id="govt_references" value="<?php echo isset($rowData['govt_references']) ? $rowData['govt_references'] : ''; ?>" class="form-control">
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="works_proposed_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['works_proposed_doc']) && $rowData['works_proposed_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['works_proposed_doc'] ?>">View File</a> <?php } ?>
			</div>
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
				<input type="text" name="critical_sites" id="critical_sites" value="<?php echo isset($rowData['critical_sites']) ? $rowData['critical_sites'] : ''; ?>" class="form-control">
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Revised estimates/Projects to be submitted</label>
				<input type="text" name="tenders_opened" id="tenders_opened" value="<?php echo isset($rowData['tenders_opened']) ? $rowData['tenders_opened'] : ''; ?>" class="form-control">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="detail_tender_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['detail_tender_doc']) && $rowData['detail_tender_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['detail_tender_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>

		<div class="col-md-12">
			<br />
			<h4> Revenue </h4>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Upload Document</label>

				<input type="file" name="revenue_doc" id="file" class="form-control" accept="image/gif, image/jpeg, image/jpg, image/png, application/pdf, .doc, .docx, .xls, .xlsx,.csv" />
				<?php if (isset($rowData['revenue_doc']) && $rowData['revenue_doc'] != "") { ?> <a href="<?= URL . "images/cto_cho/processing.php?file=" . $rowData['revenue_doc'] ?>">View File</a> <?php } ?>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Revenue/Tawan cases</label>
				<input type="number" name="pending_revenue_own_office" value="<?php echo isset($rowData['pending_revenue_own_office']) ? $rowData['pending_revenue_own_office'] : ''; ?>" id="pending_revenue_own_office" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label">Pending Revenue Court cases</label>
				<input type="number" name="pending_revenue_subordinate_office" value="<?php echo isset($rowData['pending_revenue_subordinate_office']) ? $rowData['pending_revenue_subordinate_office'] : ''; ?>" id="pending_revenue_subordinate_office" class="form-control" required>
			</div>
		</div>


		<div class="col-md-6">
			<div class="form-group">
				<label class="form-label"> Pending Command to Uncommand Cases</label>
				<input type="number" name="pending_uncommand_cases" id="pending_uncommand_cases" value="<?php echo isset($rowData['pending_uncommand_cases']) ? $rowData['pending_uncommand_cases'] : ''; ?>" class="form-control" required>
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