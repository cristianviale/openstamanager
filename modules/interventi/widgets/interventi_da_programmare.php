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

include_once __DIR__.'/../../../core.php';

use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;

$rs = Intervento::where('idstatointervento', '=', Stato::where('codice', '=', 'TODO')->first()->id)->get();

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50">'.tr('Codice').'</th>
        <th width="150">'.tr('Cliente').'</th>
        <th width="70"><small>'.tr('Data richiesta').'</small></th>
        <th width="20%" class="text-center">'.tr('Tecnici assegnati').'</th>
        <th width="200">'.tr('Tipo intervento').'</th>
        <th width="200">'.tr('Stato intervento').'</th>
        <th width="40"></th>
    </tr>';

    foreach ($rs as $r) {
        $rs_tecnici = $dbo->fetchArray("SELECT GROUP_CONCAT(ragione_sociale SEPARATOR ',') AS tecnici FROM an_anagrafiche INNER JOIN in_interventi_tecnici_assegnati ON in_interventi_tecnici_assegnati.id_tecnico=an_anagrafiche.idanagrafica WHERE id_intervento=".prepare($r->id).' GROUP BY id_intervento');

        echo '
            <tr id="int_'.$r->id.'">
				<td><a target="_blank" >'.Modules::link('Interventi', $r->id, $r->codice).'</a></td>
                <td><a target="_blank" >'.Modules::link('Anagrafiche', $r->idanagrafica, $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica='.prepare($r->idanagrafica))['ragione_sociale']).'<br><small>Presso: ';
        // Sede promemoria
        if ($r->idsede_destinazione == '-1') {
            echo '- Nessuna -';
        } elseif (empty($r->idsede_destinazione)) {
            echo tr('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($r->idsede_destinazione));

            echo $rsp2[0]['descrizione'];
        }
        echo '
                </small>
                </td>
                <td>'.Translator::dateToLocale($r->data_richiesta).' '.((empty($r->data_scadenza)) ? '' : '<br><small>Entro il '.Translator::dateToLocale($r->data_scadenza).'</small>').'</td>
                <td>
                    '.$rs_tecnici[0]['tecnici'].'
                </td>

                <td>'.$dbo->fetchOne("SELECT CONCAT_WS(' - ', `codice`, `title`) AS descrizione FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).') WHERE `in_tipiintervento`.`id`='.prepare($r->idtipointervento))['descrizione'].'</td>

                <td class="text-right">
                    <button type="button" class="btn btn-xs btn-default" onclick="toggleDettagli(this)">
                        <i class="fa fa-plus"></i>
                    </button>
                    
                </td>
            </tr>
            
            <tr style="display: none">
                <td colspan="7">
                    '.input([
            'type' => 'ckeditor',
            'name' => 'descrizione_'.$r->id,
            'value' => $r->richiesta,
            'disabled' => true,
        ]).'
                </td>
            </tr>';
    }
    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono attività da programmare').'.</p>';
}

echo '
<script>
    $(document).ready(function(){init();});

    function toggleDettagli(trigger) {
        const tr = $(trigger).closest("tr");
        const dettagli = tr.next();

        if (dettagli.css("display") === "none"){
            dettagli.show(500);
            $(trigger).children().removeClass("fa-plus"); 
            $(trigger).children().addClass("fa-minus");
        } else {
            dettagli.hide(500);
            $(trigger).children().removeClass("fa-minus"); 
            $(trigger).children().addClass("fa-plus");
        }
    }
</script>';
