SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Table structure for table `site_account_cash_changes`
--

CREATE TABLE IF NOT EXISTS `site_account_cash_changes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account` int(10) unsigned NOT NULL,
  `delta` float NOT NULL,
  `reason` varchar(32) NOT NULL,
  `extra` varchar(1024) NOT NULL,
  `ip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_achvs`
--

CREATE TABLE IF NOT EXISTS `site_achvs` (
  `id` int(10) unsigned NOT NULL,
  `category` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_achv_categories`
--

CREATE TABLE IF NOT EXISTS `site_achv_categories` (
  `id` int(10) unsigned NOT NULL,
  `category` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_donate_goods`
--

CREATE TABLE IF NOT EXISTS `site_donate_goods` (
  `entry` int(10) unsigned NOT NULL,
  `price` float unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_log_errors`
--

CREATE TABLE IF NOT EXISTS `site_log_errors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(15) NOT NULL DEFAULT '0',
  `user` int(10) unsigned NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_log_test`
--

CREATE TABLE IF NOT EXISTS `site_log_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(15) NOT NULL DEFAULT '0',
  `user` int(10) unsigned NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_mmotop_votes`
--

CREATE TABLE IF NOT EXISTS `site_mmotop_votes` (
  `id` int(10) unsigned NOT NULL,
  `guid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint(3) unsigned NOT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_appearance`
--

CREATE TABLE IF NOT EXISTS `site_operation_appearance` (
  `guid` int(10) unsigned NOT NULL,
  `logout_time` int(10) unsigned NOT NULL,
  `type` smallint(6) NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_exchange`
--

CREATE TABLE IF NOT EXISTS `site_operation_exchange` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid_my` int(10) unsigned NOT NULL,
  `guid_its` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guid_my` (`guid_my`,`guid_its`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_history`
--

CREATE TABLE IF NOT EXISTS `site_operation_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `operation` varchar(32) NOT NULL,
  `action` varchar(32) NOT NULL,
  `account` int(10) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_history_accounts`
--

CREATE TABLE IF NOT EXISTS `site_operation_history_accounts` (
  `history_id` int(10) unsigned NOT NULL,
  `field` varchar(32) NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`history_id`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_history_characters`
--

CREATE TABLE IF NOT EXISTS `site_operation_history_characters` (
  `history_id` int(10) unsigned NOT NULL,
  `field` varchar(32) NOT NULL,
  `guid` int(10) unsigned NOT NULL,
  `name` varchar(15) NOT NULL,
  PRIMARY KEY (`history_id`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_history_custom`
--

CREATE TABLE IF NOT EXISTS `site_operation_history_custom` (
  `history_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  PRIMARY KEY (`history_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_history_plain`
--

CREATE TABLE IF NOT EXISTS `site_operation_history_plain` (
  `history_id` int(10) unsigned NOT NULL,
  `name` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  PRIMARY KEY (`history_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_mail_confirm`
--

CREATE TABLE IF NOT EXISTS `site_operation_mail_confirm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `operation` varchar(32) NOT NULL,
  `action` varchar(32) NOT NULL,
  `account` int(10) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(15) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL,
  `data` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_makeuping`
--

CREATE TABLE IF NOT EXISTS `site_operation_makeuping` (
  `guid` int(10) unsigned NOT NULL,
  `logout_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_masking`
--

CREATE TABLE IF NOT EXISTS `site_operation_masking` (
  `guid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_renaming`
--

CREATE TABLE IF NOT EXISTS `site_operation_renaming` (
  `guid` int(10) unsigned NOT NULL,
  `logout_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_operation_transfer_complete`
--

CREATE TABLE IF NOT EXISTS `site_operation_transfer_complete` (
  `guid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_referrals`
--

CREATE TABLE IF NOT EXISTS `site_referrals` (
  `account` int(10) unsigned NOT NULL,
  `to_character` int(10) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account`),
  KEY `to_character` (`to_character`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `site_referrals_info`
--

CREATE TABLE IF NOT EXISTS `site_referrals_info` (
  `account` int(10) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `points` int(5) unsigned NOT NULL DEFAULT '0',
  `money` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`account`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_userbar_titles`
--

CREATE TABLE IF NOT EXISTS `site_userbar_titles` (
  `guid` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_variables`
--

CREATE TABLE IF NOT EXISTS `site_variables` (
  `name` varchar(32) NOT NULL,
  `value` varchar(64) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_zones`
--

CREATE TABLE IF NOT EXISTS `site_zones` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
