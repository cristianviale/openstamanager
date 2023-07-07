-- Aggiunta tipologia reply to
ALTER TABLE `em_templates` ADD `tipo_reply_to` VARCHAR(255) NOT NULL AFTER `subject`; 
UPDATE `em_templates` SET `tipo_reply_to` = 'email_fissa' WHERE `reply_to` != '';

-- Fix help setting
UPDATE `zz_settings` SET `help` = "Modifica automaticamente la data di emissione di una fattura al momento della sua emissione, impostando data successiva a quella dell'ultima fattura emessa" WHERE `zz_settings`.`nome` = 'Data emissione fattura automatica';

-- Fix options Scadenzario
UPDATE `zz_modules` SET `options` = 'SELECT\r\n    |select| \r\nFROM \r\n    `co_scadenziario`\r\n    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`\r\n    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`\r\n    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`\r\n    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\r\n    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\r\n    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT id FROM zz_modules WHERE name = \'Scadenzario\') AND `zz_operations`.`op` = \'send-email\' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`\r\nWHERE \r\n    1=1 AND (`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN(\'Emessa\',\'Parzialmente pagato\',\'Pagato\')) \r\nHAVING\r\n    2=2\r\nORDER BY \r\n    `scadenza` ASC' WHERE `name` = 'Scadenzario';

-- Aggiunto template solleciti raggrupati per anagrafica
INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `subject`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `predefined`, `note_aggiuntive`, `deleted_at`, `id_account`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Sollecito di pagamento raggruppato per anagrafica', 'fa fa-envelope', 'Sollecito di pagamento fatture', '', '', '', '', '<html>\r\n<head>\r\n <title></title>\r\n <link href=\"https://svc.webspellchecker.net/spellcheck31/wscbundle/css/wsc.css\" rel=\"stylesheet\" type=\"text/css\" />\r\n</head>\r\n<body data-wsc-instance=\"true\">\r\n<p>Spett.le {ragione_sociale},</p>\r\n\r\n<p>da un riscontro contabile, ci risulta che le seguenti fatture a Voi intestate, siano scadute nelle seguenti date:</p>\r\n\r\n<p>{scadenze_fatture_scadute}</p>\r\n\r\n<p>La sollecitiamo pertanto di provvedere quanto prima a regolarizzare la sua situazione contabile.</p>\r\n\r\n<p>Se ha gi&agrave; provveduto al pagamento, ritenga nulla la presente.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>La ringraziamo e le porgiamo i nostri saluti.</p>\r\n</body>\r\n</html>\r\n', '0', '0', '', NULL, '1', NULL);