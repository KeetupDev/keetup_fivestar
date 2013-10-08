CREATE TABLE `elgg_fivestar` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,

  `entity_guid` bigint(20) unsigned NOT NULL,

  `owner_guid` bigint(20) unsigned NULL,
  `ip` bigint(20) unsigned NOT NULL,

  `value` FLOAT(11) NOT NULL DEFAULT 0,
  
  `time_created` int(11) NOT NULL,
  `time_updated` int(11) NOT NULL,

  `enabled` enum('yes','no') NOT NULL DEFAULT 'yes',

  PRIMARY KEY (`id`),
  KEY `entity_guid` (`entity_guid`),
  KEY `owner_guid` (`owner_guid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;
