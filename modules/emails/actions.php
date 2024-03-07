<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Modules\Emails\Template;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        $id_module = post('module');
        $id_account = post('smtp');
        $name = post('name');
        $subject = post('subject');

        $template = Template::build($id_module, $id_account);
        $id_record = $template->id;
        $template->name = $name;
        $template->subject = $subject;
        $template->save();

        flash()->info(tr('Aggiunto nuovo template per le email!'));

        break;

    case 'update':
        $template->name = post('name');
        $template->id_account = post('smtp');
        $template->icon = post('icon');
        $template->tipo_reply_to = post('tipo_reply_to');
        $template->reply_to = post('reply_to');
        $template->cc = post('cc');
        $template->bcc = post('bcc');
        $template->read_notify = post('read_notify');
        $template->note_aggiuntive = post('note_aggiuntive');
        $template->subject = post('subject');
        $template->body = post('body');
        $template->save();

        $dbo->sync('em_print_template', ['id_template' => $id_record], ['id_print' => (array) post('prints')]);
        $dbo->sync('em_mansioni_template', ['id_template' => $id_record], ['idmansione' => (array) post('idmansioni')]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete':
        $dbo->query('UPDATE `em_templates` SET `deleted_at` = NOW() WHERE `id`='.prepare($id_record));

        flash()->info(tr('Template delle email eliminato!'));

        break;

    case 'copy':
        $database->beginTransaction();
        $database->query('CREATE TEMPORARY TABLE `tmp` SELECT * FROM `em_templates` WHERE `id`= '.prepare($id_record));
        $database->query('CREATE TEMPORARY TABLE `tmp_lang` SELECT * FROM `em_templates_lang` WHERE `id_record`= '.prepare($id_record));
        $database->query('ALTER TABLE `tmp` DROP `id`');
        $database->query('ALTER TABLE `tmp_lang` DROP `id_record`');
        $database->query('INSERT INTO `em_templates` SELECT NULL,tmp. * FROM tmp');
        $id_record = $database->lastInsertedID();
        $database->query('INSERT INTO `em_templates_lang` SELECT NULL, id_lang, '.$id_record.',name, subject, body FROM tmp_lang');
        $database->query('DROP TEMPORARY TABLE tmp');
        $database->query('DROP TEMPORARY TABLE tmp_lang');
        $database->query('UPDATE `em_templates_lang` SET `name` = CONCAT (`name`, " (copia)") WHERE id_record = '.prepare($id_record));
        $database->query('UPDATE `em_templates` SET `predefined` = 0 WHERE `id` = '.prepare($id_record));
        flash()->info(tr('Template duplicato correttamente!'));
        break;
}
