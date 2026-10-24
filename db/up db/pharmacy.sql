-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 08:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `P_AMT` (`start` DATE, `end` DATE) RETURNS DECIMAL(8,2) DETERMINISTIC NO SQL BEGIN
DECLARE PAMT DECIMAL(8,2) DEFAULT 0.0;
SELECT SUM(P_COST) INTO PAMT FROM PURCHASE WHERE PUR_DATE >= start AND PUR_DATE<= end;
RETURN PAMT;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `S_AMT` (`start` DATE, `end` DATE) RETURNS DECIMAL(8,2) NO SQL BEGIN
DECLARE SAMT DECIMAL(8,2) DEFAULT 0.0;
SELECT SUM(TOTAL_AMT) INTO SAMT FROM SALES WHERE S_DATE >= start AND S_DATE<= end;
RETURN SAMT;
END$$

DELIMITER ;

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
(1, 'JO-20261024-4D70D3D6', '', '2026-10-24', '', NULL, 8000.00, 8000.00, 4, 'posted', '2026-10-24 06:26:31', '2026-10-24 06:26:34', '2026-10-24 06:26:34');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_voucher_details`
--

INSERT INTO `journal_voucher_details` (`id`, `journal_voucher_id`, `account_head_id`, `dr`, `cr`, `remarks`, `created_by`) VALUES
(1, 1, 32, 8000.00, 0.00, '', 4),
(2, 1, 27, 0.00, 8000.00, '', 4);

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `voucher_type` varchar(7) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `voucher_no` varchar(100) DEFAULT NULL,
  `voucher_date` date DEFAULT NULL,
  `detail_id` int(11) DEFAULT NULL,
  `account_head_id` int(11) DEFAULT NULL,
  `dr` decimal(14,2) DEFAULT NULL,
  `cr` decimal(14,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(4, 36, 0, 'Amlodipine 5 mg', 'a1', 95.00, 105.00, 120, '2029-05-06', '2026-10-17 16:11:47', '2026-10-24 04:51:11', NULL),
(5, 36, 2, 'Metformin 500 mg', 'a2', 97.00, 105.00, 139, '2029-08-06', '2026-10-17 16:13:31', '2026-10-24 05:01:32', NULL),
(6, 36, 3, 'Ciprocin 500 mg', 'a3', 125.00, 145.00, 170, '2028-04-09', '2026-10-17 16:14:34', '2026-10-24 04:51:11', NULL),
(7, 43, 31, 'Ciprocin Eye/Ear Drops', 'g1', 95.00, 110.00, 49, '2028-04-08', '2026-10-17 17:00:08', '2026-10-24 04:58:10', NULL),
(8, 43, 31, 'Tobrex Eye Drops 5 ml', 'l11', 130.00, 150.00, 33, '2029-03-05', '2026-10-17 17:01:27', '2026-10-21 11:03:30', NULL),
(9, 43, 32, 'Otrivin Nasal Drops 10 ml', 'dl', 85.00, 95.00, 18, '2028-04-06', '2026-10-17 17:04:57', '2026-10-24 04:58:10', NULL),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `id` int(20) UNSIGNED NOT NULL,
  `sale_id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` int(20) UNSIGNED NOT NULL,
  `sales_return_id` bigint(20) UNSIGNED NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Shams Tabrizi', '+88965457812', 'Tabrizi@yahoo.com', 'chowduryhat,hathazari', 'mr of kumudini pharma', NULL),
(2, 'Hanzalah', '+6483215645', 'Hanzalah@gmail.com', 'Hider Ali Musir Bari, Fatehabed', 'Mr of esminex pharma', NULL),
(3, 'Daniyal Arham', '+88965457812', 'Daniyel@yahoo.com', 'Islamihat, hathazari', 'Mr ibn sina phrmacitucals ltd.', NULL);

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
(3, 'Muhammad', '987654', 'Muhammad@gmail.com', '7c222fb2927d828af22f592134e8932480637c0d', 1, '31741791696238804948', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Arafur Rahman', '12365488', 'araf@yahoo.com', '7c222fb2927d828af22f592134e8932480637c0d', 1, '35731792818540418144', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Irfan', '654789', 'irfan@yahoo.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1, '23281791844011658892', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Jabiullah', '987654', 'jabi@yahoo.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1, '46021792407169530761', NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_heads`
--
ALTER TABLE `account_heads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_voucher_details`
--
ALTER TABLE `journal_voucher_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_vouchers`
--
ALTER TABLE `payment_vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_voucher_details`
--
ALTER TABLE `payment_voucher_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receive_vouchers`
--
ALTER TABLE `receive_vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receive_voucher_details`
--
ALTER TABLE `receive_voucher_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `journal_vouchers`
--
ALTER TABLE `journal_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `journal_voucher_details`
--
ALTER TABLE `journal_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment_vouchers`
--
ALTER TABLE `payment_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_voucher_details`
--
ALTER TABLE `payment_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receive_voucher_details`
--
ALTER TABLE `receive_voucher_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` int(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
