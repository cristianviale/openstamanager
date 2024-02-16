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

include_once __DIR__.'/../../core.php';

use Modules\Pagamenti\Pagamento;

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $id_pagamento = (new Pagamento())->getByName($descrizione)->id_record;

        if (isset($descrizione)) {
            foreach (post('id') as $key => $id) {
                // Data fatturazione
                $giorno = 0;

                // Data fatturazione fine mese
                if (post('scadenza')[$key] == 2) {
                    $giorno = -1;
                }

                // Data fatturazione giorno fisso
                if (post('scadenza')[$key] == 3) {
                    $giorno = post('giorno')[$key];
                }

                // Data fatturazione fine mese (giorno fisso)
                elseif (post('scadenza')[$key] == 4) {
                    $giorno = -post('giorno')[$key] - 1;
                }

                $array = [
                    'num_giorni' => post('distanza')[$key],
                    'giorno' => $giorno,
                    'prc' => post('percentuale')[$key],
                    'idconto_vendite' => post('idconto_vendite') ?: null,
                    'idconto_acquisti' => post('idconto_acquisti') ?: null,
                    'codice_modalita_pagamento_fe' => post('codice_modalita_pagamento_fe'),
                ];

                if ($id_pagamento) {
                    flash()->error(tr('Esiste più un metodo di pagamento con questo nome!'));
                    break;
                } else {
                    if (!empty($id)) {
                        $dbo->update('co_pagamenti_lang', ['name' => $descrizione], ['id' => $id, 'id_lang' => setting('Lingua')]);
                        $dbo->update('co_pagamenti', $array, ['id' => $id_record]);
                    } else {
                        $dbo->INSERT('co_pagamenti', $array);
                        $dbo->INSERT('co_pagamenti_lang', ['name' => $descrizione, 'id_record' => $id, 'id_lang' => setting('Lingua')]);
                    }
                }
            }
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $codice_modalita_pagamento_fe = filter('codice_modalita_pagamento_fe');

        if (isset($descrizione)) {
            $id_pagamento = (new Pagamento())->getByName($descrizione)->id_record;

            if (($id_pagamento)) {
                flash()->error(tr('Esiste già un metodo di pagamento con questo nome!'));
            } else {
                $dbo->query('INSERT INTO `co_pagamenti` (`codice_modalita_pagamento_fe`, `prc` ) VALUES ('.prepare($codice_modalita_pagamento_fe).', 100 )');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `co_pagamenti_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(setting('Lingua')).')');

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'pagamento',
                ]));
            }
        }

        break;

    case 'delete':
        if (!empty($id_record)) {
            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'pagamento',
            ]));
        }

        break;

    case 'delete_rata':
        $id = filter('id');
        if (isset($id)) {
            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id`='.prepare($id));
            flash()->info(tr('Elemento eliminato con successo!'));

            if ($id_record == $id) {
                $res = $dbo->fetchArray('SELECT * FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` WHERE `co_pagamenti`.`id`!='.prepare($id).' AND `name`='.prepare($record['descrizione']));
                if (count($res) != 0) {
                    redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$res[0]['id']);
                } else {
                    // $_POST['backto'] = 'record-list';
                    redirect(base_path().'/controller.php?id_module='.$id_module);
                }
            }
        }

        break;
}
