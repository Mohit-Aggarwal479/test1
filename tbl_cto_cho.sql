-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 10.44.242.70
-- Generation Time: Jul 31, 2026 at 07:29 AM
-- Server version: 11.7.2-MariaDB-ubu2204
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wrdp_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cto_cho`
--

CREATE TABLE `tbl_cto_cho` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `charge_handover` varchar(500) NOT NULL,
  `officer_user_id` varchar(50) NOT NULL,
  `officer_hrms_code` varchar(500) NOT NULL,
  `officer_name` varchar(500) NOT NULL,
  `officer_designation` varchar(500) NOT NULL,
  `pending_revenue_own_office` varchar(500) NOT NULL,
  `pending_revenue_subordinate_office` varchar(500) NOT NULL,
  `pending_uncommand_cases` varchar(500) NOT NULL,
  `pending_site_inspection` varchar(500) NOT NULL,
  `done_site_inspection` varchar(500) NOT NULL,
  `vetted_reply_submitted` varchar(500) NOT NULL,
  `vetted_reply_pending` varchar(500) NOT NULL,
  `tp_register` varchar(500) NOT NULL,
  `outlet_notebook` varchar(500) NOT NULL,
  `upload_court_case` text NOT NULL,
  `higher_authority` varchar(500) NOT NULL,
  `higher_authority_hrms` varchar(500) NOT NULL,
  `higher_authority_id` varchar(500) NOT NULL,
  `jurisdiction_detail` varchar(500) NOT NULL,
  `court_cases` varchar(500) NOT NULL,
  `arbitration_cases` varchar(500) NOT NULL,
  `pending_recovery_cases` varchar(500) NOT NULL,
  `pending_disciplinary_cases` varchar(500) NOT NULL,
  `pending_pension_cases` int(11) NOT NULL,
  `pending_compassionate_cases` int(11) NOT NULL,
  `pending_district_issues` int(11) NOT NULL,
  `land_encroachment_cases` int(11) NOT NULL,
  `pending_rti_applications` int(11) NOT NULL,
  `contact_details` int(11) NOT NULL,
  `upload_contact_list` varchar(200) NOT NULL,
  `cash_books` varchar(500) NOT NULL,
  `cheque_books` varchar(500) NOT NULL,
  `gr_books` varchar(500) NOT NULL,
  `available_funds` varchar(500) NOT NULL,
  `pending_liability` varchar(500) NOT NULL,
  `pending_liability_doc` varchar(500) NOT NULL,
  `works_proposed` varchar(500) NOT NULL,
  `stage` int(11) NOT NULL,
  `reject_stage` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `status_text` varchar(500) NOT NULL,
  `created_by` varchar(500) NOT NULL,
  `created_by_group` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_by` varchar(500) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `view_status` int(11) NOT NULL DEFAULT 1,
  `deleted_reason` text NOT NULL,
  `approved_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `approved_by` varchar(500) NOT NULL,
  `works_proposed_doc` varchar(200) NOT NULL,
  `estimate_pending` varchar(500) NOT NULL,
  `completed_works` int(11) NOT NULL,
  `in_progress_works` int(11) NOT NULL,
  `detail_tender_doc` varchar(100) NOT NULL,
  `tenders_floated` int(11) NOT NULL,
  `tenders_opened` varchar(500) NOT NULL,
  `tenders_pending` int(11) NOT NULL,
  `critical_sites` varchar(500) NOT NULL,
  `govt_references` varchar(500) NOT NULL,
  `pending_authority` varchar(500) NOT NULL,
  `fir_complaints_doc` varchar(200) NOT NULL,
  `fir_complaints` varchar(500) NOT NULL,
  `property_doc` varchar(200) NOT NULL,
  `property` varchar(500) NOT NULL,
  `account_doc` varchar(200) NOT NULL,
  `revenue_doc` varchar(200) NOT NULL,
  `absent_emp` varchar(500) NOT NULL,
  `office_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `sendback` varchar(100) NOT NULL,
  `emp_office` int(11) NOT NULL,
  `search_method` varchar(100) NOT NULL,
  `search_method1` varchar(100) NOT NULL,
  `emp_name` text NOT NULL,
  `des_id` text NOT NULL,
  `profile_check` int(11) NOT NULL,
  `pending_rtwb_case_doc` text NOT NULL,
  `pending_cuc_case_doc` text NOT NULL,
  `site_rtwb_case_doc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_cto_cho`
--
ALTER TABLE `tbl_cto_cho`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_cto_cho`
--
ALTER TABLE `tbl_cto_cho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
