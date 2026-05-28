-- RecruitmentApplication module tables (idempotent for fresh installs)

CREATE TABLE IF NOT EXISTS `vtiger_recruitmentapplication` (
  `recruitmentapplicationid` int NOT NULL,
  `application_number` varchar(255) DEFAULT NULL,
  `number` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`recruitmentapplicationid`),
  CONSTRAINT `fk_recruitmentapplication_crmid` FOREIGN KEY (`recruitmentapplicationid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vtiger_recruitmentapplicationcf` (
  `recruitmentapplicationid` int NOT NULL,
  `cf_303283` varchar(255) DEFAULT NULL,
  `cf_303285` varchar(255) DEFAULT NULL,
  `cf_303287` text DEFAULT NULL,
  `cf_303289` int DEFAULT NULL,
  `cf_303291` varchar(255) DEFAULT NULL,
  `cf_303293` varchar(255) DEFAULT NULL,
  `cf_303295` varchar(255) DEFAULT NULL,
  `cf_303297` int DEFAULT NULL,
  `cf_303309` varchar(255) DEFAULT NULL,
  `cf_303311` varchar(255) DEFAULT NULL,
  `cf_303313` varchar(255) DEFAULT NULL,
  `cf_303315` varchar(255) DEFAULT NULL,
  `cf_303317` tinyint DEFAULT NULL,
  `cf_303319` varchar(255) DEFAULT NULL,
  `cf_303321` varchar(255) DEFAULT NULL,
  `cf_303323` varchar(255) DEFAULT NULL,
  `cf_303325` varchar(255) DEFAULT NULL,
  `cf_303327` text DEFAULT NULL,
  `cf_303329` tinyint DEFAULT NULL,
  `cf_303331` varchar(255) DEFAULT NULL,
  `cf_303333` varchar(255) DEFAULT NULL,
  `cf_303335` varchar(255) DEFAULT NULL,
  `cf_303337` int DEFAULT NULL,
  `cf_303339` varchar(255) DEFAULT NULL,
  `cf_303341` varchar(255) DEFAULT NULL,
  `cf_303299` bigint DEFAULT NULL,
  `cf_303305` bigint DEFAULT NULL,
  `cf_303307` bigint DEFAULT NULL,
  PRIMARY KEY (`recruitmentapplicationid`),
  KEY `vtiger_recruitmentapplicationcf_cf_303289_idx` (`cf_303289`),
  KEY `vtiger_recruitmentapplicationcf_cf_303297_idx` (`cf_303297`),
  KEY `vtiger_recruitmentapplicationcf_cf_303337_idx` (`cf_303337`),
  CONSTRAINT `fk_recruitmentapplicationcf_id` FOREIGN KEY (`recruitmentapplicationid`) REFERENCES `vtiger_recruitmentapplication` (`recruitmentapplicationid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
