-- Aggiunta impostazione aggiornamento prezzi e fornitore in fase di import FE
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Aggiorna info di acquisto', 'Non aggiornare', 'list[Non aggiornare,Aggiorna prezzo di listino,Aggiorna prezzo di acquisto + imposta fornitore predefinito]', '1', 'Fatturazione', '16', NULL);

-- Aggiunto filtro per mostrare gli impianti ai tecnici assegnati (Disabilitato di default)
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_groups` WHERE `nome`='Tecnici'), (SELECT `id` FROM `zz_modules` WHERE `name`='Impianti'), 'Mostra impianti ai tecnici assegnati', 'my_impianti.idtecnico=|id_anagrafica|', 'WHR', 0, 1);

-- Ridotto il valid time per la cache informazioni su Services
UPDATE `zz_cache` SET `valid_time` = '1 day' WHERE `zz_cache`.`name` = 'Informazioni su Services';

-- Ridotto il valid time per la cache informazioni su spazio FE
UPDATE `zz_cache` SET `valid_time` = '1 day' WHERE `zz_cache`.`name` = 'Informazioni su spazio FE';

-- Ordinamento righe intervento
ALTER TABLE `in_righe_interventi` ADD `order` INT NOT NULL AFTER `idsede_partenza`;

-- Aggiunta widget per la sincronizzazione esterna delle liste newsletter
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Sincronizzazione disiscritti', 'custom', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Liste newsletter'), 'controller_top', 'col-md-12', NULL, '#4ccc4c', 'fa fa-envelope ', '', './modules/liste_newsletter/widgets/opt-out.php', 'popup', './modules/liste_newsletter/widgets/opt-out.php', 'Sincronizza disiscritti dal servizio esterno', '0', '1');
