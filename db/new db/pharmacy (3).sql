-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 03:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_heads`
--

CREATE TABLE `account_heads` (
  `id` int(11) NOT NULL,
  `account_code` varchar(100) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `account_type` enum('Asset','Liability','Income','Expense','Equity','VAT') NOT NULL,
  `account_subtype` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_heads`
--

INSERT INTO `account_heads` (`id`, `account_code`, `account_name`, `account_type`, `account_subtype`, `parent_id`, `opening_balance`, `status`, `description`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '1232', 'account reciaveable', 'Asset', 'Current Asset', NULL, 1000.00, 'Inactive', 'kj', 4, '2026-10-20 03:20:46', '2026-10-23 13:14:28', '2026-10-23 13:14:28'),
(3, '1112', 'Damaged goods Loss', 'Expense', 'Current Asset', NULL, 5000.00, 'Active', 'fgfg', 4, '2026-10-20 03:22:12', '2026-10-23 13:33:29', NULL),
(8, '1233', 'Medicine Stock', 'Asset', '', NULL, 5000.00, 'Inactive', '', 4, '2026-10-20 03:24:37', '2026-10-23 13:14:26', '2026-10-23 13:14:26'),
(9, 'AUTO-SALES-INCOME', 'Sales Income', 'Income', 'Invoice voucher', NULL, 0.00, 'Inactive', 'Automatically created for invoice-linked vouchers.', 4, '2026-10-20 05:06:06', '2026-10-23 13:14:17', '2026-10-23 13:14:17'),
(10, 'AUTO-PURCHASE-EXPENSE', 'Purchase Expense', 'Expense', 'Invoice voucher', NULL, 0.00, 'Inactive', 'Automatically created for invoice-linked vouchers.', 4, '2026-10-20 05:09:08', '2026-10-23 13:14:20', '2026-10-23 13:14:20'),
(11, '132', 'Expense', 'Expense', 'Current Liability', 9, 50000.00, 'Inactive', 'df', 4, '2026-10-20 11:17:07', '2026-10-21 01:21:04', '2026-10-21 01:21:04'),
(12, '1122', 'Account payable', 'Liability', 'Current Liability', NULL, 10000.00, 'Inactive', '', 4, '2026-10-20 12:37:49', '2026-10-20 13:41:22', '2026-10-20 13:41:22'),
(13, '1234', 'Expired Medicine Loss', 'Liability', '', 12, 5000.00, 'Inactive', '', 4, '2026-10-20 13:34:48', '2026-10-20 13:41:19', '2026-10-20 13:41:19'),
(14, '101', 'Cash in Hand', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:27:40', '2026-10-23 13:32:32', NULL),
(15, '102', 'Expired Medicine Loss', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:28:57', '2026-10-23 13:33:14', NULL),
(16, '103', 'purchase(COGS)', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:29:47', '2026-10-23 13:34:52', NULL),
(17, '104', 'Sales', 'Income', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:31:09', '2026-10-23 13:35:31', NULL),
(18, '105', 'Advance to Supplier', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:34:42', '2026-10-23 12:34:42', NULL),
(19, '106', 'Advance Rent', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:35:06', '2026-10-23 12:35:06', NULL),
(20, '107', 'Cash at Bank', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:35:45', '2026-10-23 12:35:45', NULL),
(21, '108', 'Supplier Payable', 'Liability', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:36:42', '2026-10-23 12:36:42', NULL),
(22, '109', 'Bank Loan', 'Liability', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:37:10', '2026-10-23 12:37:10', NULL),
(23, '110', 'Customer Advance', 'Liability', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:37:49', '2026-10-23 12:37:49', NULL),
(24, '111', 'Drawings', 'Equity', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:38:33', '2026-10-23 12:38:33', NULL),
(25, '112', 'Commission Income', 'Income', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:39:14', '2026-10-23 12:39:14', NULL),
(26, '113', 'Salary & Wages', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:41:36', '2026-10-23 12:41:36', NULL),
(27, '114', 'Shop Rent', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 12:42:02', '2026-10-23 12:42:02', NULL),
(28, '115', 'account reciaveable', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 13:16:22', '2026-10-23 13:16:22', NULL),
(29, '116', 'Medicine Stock', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 13:16:36', '2026-10-23 13:16:36', NULL),
(30, '117', 'Bad Debt Expense', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 13:16:55', '2026-10-23 13:35:41', NULL),
(31, '118', 'Shortage of goods', 'Expense', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 13:17:13', '2026-10-23 13:35:10', NULL),
(32, '119', 'bkash/nagad/rocket', 'Asset', '', NULL, 0.00, 'Active', '', 4, '2026-10-23 13:28:01', '2026-10-23 13:28:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(36, 'Tablet', 'Solid oral dosage form, e.g. Paracetamol, Napa', NULL, NULL, NULL),
(38, 'Capsule', 'Oral medicine enclosed in a gelatin shell', NULL, NULL, NULL),
(39, 'Syrup', 'Liquid medicine, especially for children or those who can’t swallow tablets', NULL, NULL, NULL),
(40, 'Injection', 'Medicine given into a vein, muscle, or under the skin', NULL, NULL, NULL),
(41, '	Saline / IV Fluid', 'Intravenous fluids for dehydration or hospital use', NULL, NULL, NULL),
(42, '	Ointment / Cream', 'Topical medicine for itching, infections, and burns', NULL, NULL, NULL),
(43, 'Drops', 'Eye, ear, or nasal drops', NULL, NULL, NULL),
(44, 'Inhaler', 'Inhaled medicine for respiratory patients (asthma, COPD)', NULL, NULL, NULL),
(45, '	Suppository', 'Medicine administered rectally or vaginally', NULL, NULL, NULL),
(46, 'Powder / Sachet', 'Powder mixed with water, e.g. ORS', NULL, NULL, NULL),
(47, 'Antibiotic', 'Medicine for bacterial infections, e.g. Amoxicillin, Azithromycin', NULL, NULL, NULL),
(48, 'Antacid / Gastric', '	Medicine for stomach acid, gastritis, and ulcers', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_code` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `nid_number` varchar(30) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_vouchers`
--

CREATE TABLE `journal_vouchers` (
  `id` int(11) NOT NULL,
  `voucher_no` varchar(100) NOT NULL,
  `narration` text DEFAULT NULL,
  `voucher_date` date NOT NULL,
  `source_type` varchar(100) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_vouchers`
--

INSERT INTO `journal_vouchers` (`id`, `voucher_no`, `narration`, `voucher_date`, `source_type`, `source_id`, `dr`, `cr`, `created_by`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'JO-20261020-AFA0F7FC', 'fsdf', '2026-10-20', '', NULL, 500.00, 500.00, 4, 'posted', '2026-10-20 03:40:50', '2026-10-23 12:59:22', '2026-10-23 12:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `journal_voucher_details`
--

CREATE TABLE `journal_voucher_details` (
  `id` int(11) NOT NULL,
  `journal_voucher_id` int(11) NOT NULL,
  `account_head_id` int(11) NOT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `journal_voucher_details`
--

INSERT INTO `journal_voucher_details` (`id`, `journal_voucher_id`, `account_head_id`, `dr`, `cr`, `remarks`, `created_by`) VALUES
(1, 1, 1, 500.00, 0.00, 'd', 4),
(2, 1, 8, 0.00, 500.00, 'd', 4);

-- --------------------------------------------------------

--
-- Stand-in structure for view `ledger`
-- (See below for the actual view)
--
CREATE TABLE `ledger` (
`voucher_type` varchar(7)
,`voucher_id` int(11)
,`voucher_no` varchar(100)
,`voucher_date` date
,`detail_id` int(11)
,`account_head_id` int(11)
,`dr` decimal(14,2)
,`cr` decimal(14,2)
,`remarks` varchar(255)
,`created_by` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `category_id`, `supplier_id`, `medicine_name`, `batch_no`, `purchase_price`, `selling_price`, `stock_qty`, `expiry_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 36, 32, 'Amlodipine 5 mg', 'a1', 35.00, 40.00, 134, '2029-05-06', '2026-10-17 16:11:47', '2026-10-22 06:49:31', NULL),
(5, 36, 31, 'Metformin 500 mg', 'a2', 1.10, 1.50, 195, '2029-08-06', '2026-10-17 16:13:31', '2026-10-22 06:49:31', NULL),
(6, 36, 31, 'Ciprocin 500 mg', 'a3', 8.00, 9.00, 292, '2028-04-09', '2026-10-17 16:14:34', '2026-10-21 11:03:30', NULL),
(7, 43, 31, 'Ciprocin Eye/Ear Drops', 'g1', 95.00, 110.00, 99, '2028-04-08', '2026-10-17 17:00:08', '2026-10-22 06:49:31', NULL),
(8, 43, 31, 'Tobrex Eye Drops 5 ml', 'l11', 130.00, 150.00, 33, '2029-03-05', '2026-10-17 17:01:27', '2026-10-21 11:03:30', NULL),
(9, 43, 32, 'Otrivin Nasal Drops 10 ml', 'dl', 85.00, 95.00, 38, '2028-04-06', '2026-10-17 17:04:57', '2026-10-21 11:02:23', NULL),
(10, 39, 32, 'Cef-3 Suspension 30 ml', 'l5', 120.00, 130.00, 20, '2029-06-08', '2026-10-17 17:06:44', '2026-10-17 17:06:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_vouchers`
--

CREATE TABLE `payment_vouchers` (
  `id` int(11) NOT NULL,
  `voucher_no` varchar(100) NOT NULL,
  `pay_to` varchar(150) NOT NULL,
  `narration` text DEFAULT NULL,
  `voucher_date` date NOT NULL,
  `source_type` varchar(100) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_vouchers`
--

INSERT INTO `payment_vouchers` (`id`, `voucher_no`, `pay_to`, `narration`, `voucher_date`, `source_type`, `source_id`, `dr`, `cr`, `created_by`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PA-20261020-CDB1A544', 'ds', '', '2026-10-20', '', NULL, 500.00, 500.00, 4, 'posted', '2026-10-20 04:11:27', '2026-10-23 12:33:26', '2026-10-23 12:33:26'),
(2, 'PA-20261020-D4340224', 'Belal ibn rabah', 'Purchase invoice 0002', '0226-04-10', 'purchase_invoice', 9, 6450.00, 6450.00, 4, 'posted', '2026-10-20 05:09:09', '2026-10-23 12:59:32', '2026-10-23 12:59:32'),
(3, 'PA-20261020-87F707D0', 'Shams Tabrizi', 'Purchase invoice 0001', '2026-04-04', 'purchase_invoice', 8, 430.00, 430.00, 4, 'posted', '2026-10-20 10:59:55', '2026-10-23 12:59:30', '2026-10-23 12:59:30'),
(4, 'PA-20261022-4D086319', 'Shams Tabrizi', 'Purchase invoice 0003', '2026-04-05', 'purchase_invoice', 10, 11.00, 11.00, 4, 'posted', '2026-10-22 03:53:24', '2026-10-23 12:59:28', '2026-10-23 12:59:28'),
(5, 'PA-20261023-C69F5B66', 'Shams Tabrizi', 'Purchase invoice 0003', '2026-04-05', 'purchase_invoice', 10, 11.00, 11.00, 4, 'posted', '2026-10-23 13:19:37', '2026-10-23 13:19:37', NULL),
(6, 'PA-20261023-DB685F67', 'Belal ibn rabah', 'Purchase invoice 0002', '0226-04-10', 'purchase_invoice', 9, 6450.00, 6450.00, 4, 'posted', '2026-10-23 13:20:22', '2026-10-23 13:20:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_voucher_details`
--

CREATE TABLE `payment_voucher_details` (
  `id` int(11) NOT NULL,
  `payment_voucher_id` int(11) NOT NULL,
  `account_head_id` int(11) NOT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `payment_voucher_details`
--

INSERT INTO `payment_voucher_details` (`id`, `payment_voucher_id`, `account_head_id`, `dr`, `cr`, `remarks`, `created_by`) VALUES
(1, 1, 1, 500.00, 0.00, '', 4),
(2, 1, 8, 0.00, 500.00, '', 4),
(3, 2, 10, 6450.00, 0.00, 'Purchase expense for invoice 0002', 4),
(4, 2, 8, 0.00, 6450.00, 'Payment for invoice 0002', 4),
(5, 3, 10, 430.00, 0.00, 'Purchase expense for invoice 0001', 4),
(6, 3, 8, 0.00, 430.00, 'Payment for invoice 0001', 4),
(7, 4, 10, 11.00, 0.00, 'Purchase expense for invoice 0003', 4),
(8, 4, 8, 0.00, 11.00, 'Payment for invoice 0003', 4),
(9, 5, 31, 11.00, 0.00, 'Purchase expense for invoice 0003', 4),
(10, 5, 3, 0.00, 11.00, 'Payment for invoice 0003', 4),
(11, 6, 31, 6450.00, 0.00, 'Purchase expense for invoice 0002', 4),
(12, 6, 3, 0.00, 6450.00, 'Payment for invoice 0002', 4);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `salary_month` date NOT NULL COMMENT 'মাসের প্রথম দিন দিন: যেমন 2026-10-01',
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overtime` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `advance_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Pending','Paid') NOT NULL DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purchase_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `invoice_no`, `supplier_id`, `user_id`, `purchase_date`, `total_amount`, `created_at`) VALUES
(8, '0001', 31, 4, '2026-04-04', 430.00, '2026-10-18 11:30:36'),
(9, '0002', 1, 4, '0226-04-10', 6450.00, '2026-10-20 04:04:54'),
(10, '0003', 31, 4, '2026-04-05', 11.00, '2026-10-21 01:49:22');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `medicine_id`, `quantity`, `unit_price`, `line_total`) VALUES
(13, 8, 4, 10, 35.00, 350.00),
(14, 8, 6, 10, 8.00, 80.00),
(15, 9, 7, 50, 95.00, 4750.00),
(16, 9, 9, 20, 85.00, 1700.00),
(17, 10, 5, 10, 1.10, 11.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `purchase_id`, `return_no`, `return_date`, `total`, `reason`, `created_at`) VALUES
(1, 8, 'PR-20261018-E66FA339', '2026-10-18 00:00:00', 70.00, 'ghfd', '2026-10-18 15:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_return_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_item_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_return_items`
--

INSERT INTO `purchase_return_items` (`id`, `purchase_return_id`, `purchase_item_id`, `medicine_id`, `quantity`, `unit_cost`, `line_total`) VALUES
(1, 1, 13, 4, 2.000, 35.00, 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `receive_vouchers`
--

CREATE TABLE `receive_vouchers` (
  `id` int(11) NOT NULL,
  `voucher_no` varchar(100) NOT NULL,
  `received_from` varchar(150) NOT NULL,
  `narration` text DEFAULT NULL,
  `voucher_date` date NOT NULL,
  `source_type` varchar(100) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receive_vouchers`
--

INSERT INTO `receive_vouchers` (`id`, `voucher_no`, `received_from`, `narration`, `voucher_date`, `source_type`, `source_id`, `dr`, `cr`, `created_by`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'RE-20261020-878A96D1', 'ms', '', '2026-10-20', '', NULL, 1000.00, 1000.00, 4, 'posted', '2026-10-20 03:41:41', '2026-10-22 10:47:24', '2026-10-22 10:47:24'),
(2, 'RE-20261020-0BB9B8F6', 'Abdullah', 'Sale invoice 0006', '2026-04-05', 'sales_invoice', 16, 245.00, 245.00, 4, 'posted', '2026-10-20 05:06:06', '2026-10-22 10:47:17', '2026-10-22 10:47:17'),
(3, 'RE-20261020-358E4A96', 'jamil', '', '2026-10-20', '', NULL, 5000.00, 5000.00, 4, 'posted', '2026-10-20 11:13:29', '2026-10-23 12:32:51', '2026-10-23 12:32:51'),
(4, 'RE-20261021-DBFCBCA5', 'Nahiyan', 'Sale invoice 0007', '2026-05-06', 'sales_invoice', 18, 89.00, 89.00, 4, 'posted', '2026-10-21 03:12:41', '2026-10-22 10:47:20', '2026-10-22 10:47:20'),
(5, 'RE-20261023-2A8823CA', 'Nahiyan', 'Sale invoice 0009', '2026-05-10', 'sales_invoice', 20, 862.50, 862.50, 4, 'posted', '2026-10-23 13:30:07', '2026-10-23 13:30:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receive_voucher_details`
--

CREATE TABLE `receive_voucher_details` (
  `id` int(11) NOT NULL,
  `receive_voucher_id` int(11) NOT NULL,
  `account_head_id` int(11) NOT NULL,
  `dr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `receive_voucher_details`
--

INSERT INTO `receive_voucher_details` (`id`, `receive_voucher_id`, `account_head_id`, `dr`, `cr`, `remarks`, `created_by`) VALUES
(1, 1, 1, 1000.00, 0.00, '', 4),
(2, 1, 8, 0.00, 1000.00, '', 4),
(3, 2, 8, 245.00, 0.00, 'Cash received for invoice 0006', 4),
(4, 2, 9, 0.00, 245.00, 'Sales income for invoice 0006', 4),
(5, 3, 1, 5000.00, 0.00, '', 4),
(6, 3, 9, 0.00, 5000.00, '', 4),
(7, 4, 8, 89.00, 0.00, 'Cash received for invoice 0007', 4),
(8, 4, 9, 0.00, 89.00, 'Sales income for invoice 0007', 4),
(11, 5, 14, 862.50, 0.00, 'Cash received for invoice 0009', 4),
(12, 5, 17, 0.00, 862.50, 'Sales income for invoice 0009', 4);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_no`, `customer_name`, `user_id`, `sale_date`, `total_amount`, `created_at`) VALUES
(12, '0002', 'jubair', 4, '2026-04-02', 159.00, '2026-10-18 12:41:36'),
(13, '0003', 'Nahiyan', 4, '2026-10-08', 49.00, '2026-10-18 13:03:03'),
(14, '0004', 'mahmud', 4, '2025-04-04', 150.00, '2026-10-18 13:04:00'),
(15, '0005', 'mahmud', 4, '2025-04-04', 1.50, '2026-10-18 13:07:19'),
(16, '0006', 'Abdullah', 6, '2026-04-05', 245.00, '2026-10-19 12:08:02'),
(18, '0007', 'Nahiyan', 4, '2026-05-06', 89.00, '2026-10-21 01:49:46'),
(19, '0008', 'mahmud', 4, '2026-08-05', 1795.00, '2026-10-21 11:02:23'),
(20, '0009', 'Nahiyan', 4, '2026-05-10', 862.50, '2026-10-21 11:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_returns`
--

INSERT INTO `sales_returns` (`id`, `sale_id`, `return_no`, `return_date`, `total`, `reason`, `created_at`) VALUES
(1, 15, 'SR-20261019-99086C5C', '2026-10-19 00:00:00', 1.50, 'fdg', '2026-10-19 10:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_return_id` bigint(20) UNSIGNED NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_return_items`
--

INSERT INTO `sales_return_items` (`id`, `sales_return_id`, `sale_item_id`, `medicine_id`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 23, 5, 1.000, 1.50, 1.50);

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `medicine_id`, `quantity`, `unit_price`, `line_total`) VALUES
(17, 12, 6, 1, 9.00, 9.00),
(18, 12, 8, 1, 150.00, 150.00),
(19, 13, 4, 1, 40.00, 40.00),
(20, 13, 6, 1, 9.00, 9.00),
(21, 14, 4, 1, 40.00, 40.00),
(22, 14, 7, 1, 110.00, 110.00),
(23, 15, 5, 1, 1.50, 1.50),
(24, 16, 8, 1, 150.00, 150.00),
(25, 16, 9, 1, 95.00, 95.00),
(27, 18, 4, 2, 40.00, 80.00),
(28, 18, 6, 1, 9.00, 9.00),
(29, 19, 9, 10, 95.00, 950.00),
(30, 19, 6, 5, 9.00, 45.00),
(31, 19, 4, 20, 40.00, 800.00),
(32, 20, 6, 10, 9.00, 90.00),
(33, 20, 8, 5, 150.00, 750.00),
(34, 20, 5, 15, 1.50, 22.50);

-- --------------------------------------------------------

--
-- Table structure for table `shop_expenses`
--

CREATE TABLE `shop_expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_expenses`
--

INSERT INTO `shop_expenses` (`id`, `expense_date`, `expense_name`, `amount`, `remarks`, `created_by`, `created_at`, `deleted_at`) VALUES
(2, '2026-10-20', 'rent', 5000.00, 'fdf', 4, '2026-10-20 04:09:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `phone`, `email`, `address`, `description`, `deleted_at`) VALUES
(1, 'Belal ibn rabah', '455741', 'Belal@gmail.com', 'abhi-siniya(ethiopia)', 'Mr of kumodini pharma', NULL),
(31, 'Shams Tabrizi', '789654', 'Tabriz@yahoo.com', '2no gate cu,hathazari,chattogram', 'Mr of Esminex pharmacitucals Ltd.', NULL),
(32, 'Hanzalah ibn Abu amir', '856574', 'Hanzala@gmail.com', 'Chowduryhat,Hathazari,Chattogram', 'Mr of suqure permacitucals ltd.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `mobile`, `email`, `password`, `status`, `remember_token`, `reset_token`, `reset_expires`, `image`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Kamal Uddin', NULL, 'kamal@yahoo.com', '738f69d66dd207d6184e396e0c7e6bd582165627', 1, '14201791604761119689', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Arafur Rahman Irfan', '12365488', 'araf@yahoo.com', '7c222fb2927d828af22f592134e8932480637c0d', 1, '73951792845716408690', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Irfan', '654789', 'irfan@yahoo.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1, '23281791844011658892', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Jabiullah', '987654', 'jabi@yahoo.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1, '46021792407169530761', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `ledger`
--
DROP TABLE IF EXISTS `ledger`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ledger`  AS SELECT 'journal' AS `voucher_type`, `j`.`id` AS `voucher_id`, `j`.`voucher_no` AS `voucher_no`, `j`.`voucher_date` AS `voucher_date`, `d`.`id` AS `detail_id`, `d`.`account_head_id` AS `account_head_id`, `d`.`dr` AS `dr`, `d`.`cr` AS `cr`, `d`.`remarks` AS `remarks`, `d`.`created_by` AS `created_by` FROM (`journal_vouchers` `j` join `journal_voucher_details` `d` on(`d`.`journal_voucher_id` = `j`.`id`)) WHERE `j`.`status` = 'posted' AND `j`.`deleted_at` is nullunion allselect 'receive' AS `voucher_type`,`r`.`id` AS `voucher_id`,`r`.`voucher_no` AS `voucher_no`,`r`.`voucher_date` AS `voucher_date`,`d`.`id` AS `detail_id`,`d`.`account_head_id` AS `account_head_id`,`d`.`dr` AS `dr`,`d`.`cr` AS `cr`,`d`.`remarks` AS `remarks`,`d`.`created_by` AS `created_by` from (`receive_vouchers` `r` join `receive_voucher_details` `d` on(`d`.`receive_voucher_id` = `r`.`id`)) where `r`.`status` = 'posted' and `r`.`deleted_at` is null union all select 'payment' AS `voucher_type`,`p`.`id` AS `voucher_id`,`p`.`voucher_no` AS `voucher_no`,`p`.`voucher_date` AS `voucher_date`,`d`.`id` AS `detail_id`,`d`.`account_head_id` AS `account_head_id`,`d`.`dr` AS `dr`,`d`.`cr` AS `cr`,`d`.`remarks` AS `remarks`,`d`.`created_by` AS `created_by` from (`payment_vouchers` `p` join `payment_voucher_details` `d` on(`d`.`payment_voucher_id` = `p`.`id`)) where `p`.`status` = 'posted' and `p`.`deleted_at` is null  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_heads`
--
ALTER TABLE `account_heads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_account_heads_code` (`account_code`),
  ADD KEY `idx_account_heads_type` (`account_type`),
  ADD KEY `idx_account_heads_parent` (`parent_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `nid_number` (`nid_number`);

--
-- Indexes for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_journal_vouchers_no` (`voucher_no`),
  ADD KEY `idx_journal_vouchers_date` (`voucher_date`),
  ADD KEY `idx_journal_vouchers_source` (`source_type`,`source_id`),
  ADD KEY `idx_journal_vouchers_status` (`status`);

--
-- Indexes for table `journal_voucher_details`
--
ALTER TABLE `journal_voucher_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jvd_voucher` (`journal_voucher_id`),
  ADD KEY `idx_jvd_account` (`account_head_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_medicines_category` (`category_id`),
  ADD KEY `fk_medicines_supplier` (`supplier_id`);

--
-- Indexes for table `payment_vouchers`
--
ALTER TABLE `payment_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_vouchers_no` (`voucher_no`),
  ADD KEY `idx_payment_vouchers_date` (`voucher_date`),
  ADD KEY `idx_payment_vouchers_source` (`source_type`,`source_id`),
  ADD KEY `idx_payment_vouchers_status` (`status`);

--
-- Indexes for table `payment_voucher_details`
--
ALTER TABLE `payment_voucher_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pvd_voucher` (`payment_voucher_id`),
  ADD KEY `idx_pvd_account` (`account_head_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_salary_month` (`employee_id`,`salary_month`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchases_invoice_no` (`invoice_no`),
  ADD KEY `idx_purchases_supplier_id` (`supplier_id`),
  ADD KEY `idx_purchases_user_id` (`user_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_items_purchase_id` (`purchase_id`),
  ADD KEY `idx_purchase_items_medicine_id` (`medicine_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `fk_purchase_returns_purchase` (`purchase_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_purchase_return_items_return` (`purchase_return_id`),
  ADD KEY `fk_purchase_return_items_purchase_item` (`purchase_item_id`),
  ADD KEY `fk_purchase_return_items_medicine` (`medicine_id`);

--
-- Indexes for table `receive_vouchers`
--
ALTER TABLE `receive_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_receive_vouchers_no` (`voucher_no`),
  ADD KEY `idx_receive_vouchers_date` (`voucher_date`),
  ADD KEY `idx_receive_vouchers_source` (`source_type`,`source_id`),
  ADD KEY `idx_receive_vouchers_status` (`status`);

--
-- Indexes for table `receive_voucher_details`
--
ALTER TABLE `receive_voucher_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rvd_voucher` (`receive_voucher_id`),
  ADD KEY `idx_rvd_account` (`account_head_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sales_invoice_no` (`invoice_no`),
  ADD KEY `idx_sales_user_id` (`user_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `fk_sales_returns_sale` (`sale_id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_return_items_return` (`sales_return_id`),
  ADD KEY `fk_sales_return_items_sale_item` (`sale_item_id`),
  ADD KEY `fk_sales_return_items_medicine` (`medicine_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_items_sale_id` (`sale_id`),
  ADD KEY `idx_sale_items_medicine_id` (`medicine_id`);

--
-- Indexes for table `shop_expenses`
--
ALTER TABLE `shop_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_expenses_date` (`expense_date`),
  ADD KEY `idx_shop_expenses_deleted_at` (`deleted_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_heads`
--
ALTER TABLE `account_heads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `journal_voucher_details`
--
ALTER TABLE `journal_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_vouchers`
--
ALTER TABLE `payment_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_voucher_details`
--
ALTER TABLE `payment_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receive_vouchers`
--
ALTER TABLE `receive_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `receive_voucher_details`
--
ALTER TABLE `receive_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `shop_expenses`
--
ALTER TABLE `shop_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_heads`
--
ALTER TABLE `account_heads`
  ADD CONSTRAINT `fk_account_heads_parent` FOREIGN KEY (`parent_id`) REFERENCES `account_heads` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `journal_voucher_details`
--
ALTER TABLE `journal_voucher_details`
  ADD CONSTRAINT `fk_jvd_account` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jvd_voucher` FOREIGN KEY (`journal_voucher_id`) REFERENCES `journal_vouchers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicines_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_medicines_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `payment_voucher_details`
--
ALTER TABLE `payment_voucher_details`
  ADD CONSTRAINT `fk_pvd_account` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pvd_voucher` FOREIGN KEY (`payment_voucher_id`) REFERENCES `payment_vouchers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_purchases_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `fk_purchase_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `fk_purchase_items_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `fk_purchase_returns_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `fk_purchase_return_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_return_items_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_return_items_return` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `receive_voucher_details`
--
ALTER TABLE `receive_voucher_details`
  ADD CONSTRAINT `fk_rvd_account` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rvd_voucher` FOREIGN KEY (`receive_voucher_id`) REFERENCES `receive_vouchers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `fk_sales_returns_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `fk_sales_return_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_return_items_return` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_return_items_sale_item` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
