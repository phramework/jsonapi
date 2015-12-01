--
-- Table structure for table `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator-user-id` int(10) unsigned NOT NULL,
  `title` varchar(128) NOT NULL,
  `created` bigint(20) unsigned NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator-user-id` (`creator-user-id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`id`, `creator-user-id`, `title`, `created`, `status`) VALUES
(1, 1, 'post', 1447934963, 1),
(2, 2, 'repost', 1447936960, 1);

--
-- Triggers `article`
--
DROP TRIGGER IF EXISTS `article_BI`;
DELIMITER //
CREATE TRIGGER `article_BI` BEFORE INSERT ON `article`
 FOR EACH ROW BEGIN
	SET NEW.created := UNIX_TIMESTAMP(UTC_TIMESTAMP());
END
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `article-tag`
--

CREATE TABLE IF NOT EXISTS `article-tag` (
  `article-id` int(10) unsigned NOT NULL,
  `tag-id` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`article-id`,`tag-id`),
  KEY `tag-id` (`tag-id`),
  KEY `article-id` (`article-id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `article-tag`
--

INSERT INTO `article-tag` (`article-id`, `tag-id`, `status`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(2, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(32) NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `tag`
--

INSERT INTO `tag` (`id`, `tag`, `status`) VALUES
(2, 'blog', 1),
(3, 'release', 1),
(4, 'closed', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `status` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `status`) VALUES
(1, 'nohponex', 1),
(2, 'xenofon', 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`creator-user-id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `article-tag`
--
ALTER TABLE `article-tag`
  ADD CONSTRAINT `article-tag_ibfk_1` FOREIGN KEY (`article-id`) REFERENCES `article` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
