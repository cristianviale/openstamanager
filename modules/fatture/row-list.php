<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Interventi\Intervento;

include_once __DIR__.'/init.php';

echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed table-bordered">
        <thead>
            <tr>
                <th width="35" class="text-center" >'.tr('#').'</th>
                <th>'.tr('Descrizione').'</th>
                <th class="text-center" width="150">'.tr('Q.tà').'</th>
                <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="150">'.tr('Iva unitaria').'</th>
                <th class="text-center" width="150">'.tr('Importo').'</th>
                <th width="120"></th>
            </tr>
        </thead>
        <tbody class="sortable">';

// Righe documento
$righe = $fattura->getRighe();
$num = 0;
foreach ($righe as $riga) {
    ++$num;

    $extra = '';
    $mancanti = 0;
    $delete = 'delete_riga';

    // Individuazione dei seriali
    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        $serials = $riga->serials;
        $mancanti = abs($riga->qta) - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }

    $extra_riga = '';
    if (!$riga->isDescrizione()) {
        // Informazioni su CIG, CUP, ...
        if ($riga->hasOriginalComponent()) {
            $documento_originale = $riga->getOriginalComponent()->getDocument();

            $num_item = $documento_originale['num_item'];
            $codice_cig = $documento_originale['codice_cig'];
            $codice_cup = $documento_originale['codice_cup'];
            $id_documento_fe = $documento_originale['id_documento_fe'];
        }

        $descrizione_conto = $dbo->fetchOne('SELECT descrizione FROM co_pianodeiconti3 WHERE id = '.prepare($riga->id_conto))['descrizione'];

        $extra_riga = replace('_DESCRIZIONE_CONTO__ID_DOCUMENTO__NUMERO_RIGA__CODICE_CIG__CODICE_CUP__RITENUTA_ACCONTO__RITENUTA_CONTRIBUTI__RIVALSA_', [
            '_RIVALSA_' => $riga->rivalsa_inps ? '<br>Rivalsa: '.moneyFormat(abs($riga->rivalsa_inps)) : null,
            '_RITENUTA_ACCONTO_' => $riga->ritenuta_acconto ? '<br>Ritenuta acconto: '.moneyFormat(abs($riga->ritenuta_acconto)) : null,
            '_RITENUTA_CONTRIBUTI_' => $riga->ritenuta_contributi ? '<br>Ritenuta contributi: '.moneyFormat(abs($riga->ritenuta_contributi)) : null,
            '_DESCRIZIONE_CONTO_' => $descrizione_conto ?: '<span class="label label-danger" ><i class="fa fa-exclamation-triangle"></i>
            '.tr('Conto mancante').'</span>',
            '_ID_DOCUMENTO_' => $id_documento_fe ? ' - DOC: '.$id_documento_fe : null,
            '_NUMERO_RIGA_' => $num_item ? ', NRI: '.$num_item : null,
            '_CODICE_CIG_' => $codice_cig ? ', CIG: '.$codice_cig : null,
            '_CODICE_CUP_' => $codice_cup ? ', CUP: '.$codice_cup : null,
        ]);
    }

    echo '
        <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'" '.$extra.'>
            <td class="text-center">
                '.$num.'
            </td>

            <td>';

    // Informazioni aggiuntive sulla destra
    echo '
                <small class="pull-right text-right text-muted">
                    '.$extra_riga;

    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                    <br>'.reference($riga->getOriginalComponent()->getDocument(), tr('Origine'));
    }
    // Fix per righe da altre componenti degli Interventi
    elseif (!empty($riga->idintervento)) {
        echo '
                    <br>'.reference(Intervento::find($riga->idintervento), tr('Origine'));
    }

    echo '
                </small>';

    if ($riga->isArticolo()) {
        echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
    } else {
        echo nl2br($riga->descrizione);
    }

    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        if (!empty($mancanti)) {
            echo '
                <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                    '_NUM_' => $mancanti,
                ]).'</small></b>';
        }
        if (!empty($serials)) {
            echo '
                <br>'.tr('SN').': '.implode(', ', $serials);
        }
    }

    echo '
            </td>';

    if ($riga->isDescrizione()) {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
            <td class="text-center">
                '.numberFormat($fattura->isNota() ? -$riga->qta : $riga->qta, 'qta').' '.$riga->um.'
            </td>';

        // Prezzi unitari
        echo '
            <td class="text-right">
                '.moneyFormat($riga->prezzo_unitario_corrente);

        if ($dir == 'entrata' && $riga->costo_unitario != 0) {
            echo '
                <br><small class="text-muted">
                    '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
                </small>';
        }

        if (abs($riga->sconto_unitario) > 0) {
            $text = discountInfo($riga);

            echo '
                <br><small class="label label-danger">'.$text.'</small>';
        }

        echo '
            </td>';

        // Iva
        echo '
            <td class="text-right">
                '.moneyFormat($riga->iva_unitaria).'
                <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
            </td>';

        // Importo
        echo '
            <td class="text-right">
                '.moneyFormat($fattura->isNota() ? -$riga->importo : $riga->importo).'
            </td>';
    }

    // Possibilità di rimuovere una riga solo se la fattura non è pagata
    echo '
            <td class="text-center">';

    if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa' && $riga->id != $fattura->rigaBollo->id) {
        echo '
                <div class="input-group-btn">';

        if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
            echo '
                    <a class="btn btn-primary btn-xs" title="'.tr('Modifica seriali della riga').'" onclick="modificaSeriali(this)">
                        <i class="fa fa-barcode"></i>
                    </a>';
        }

        echo '
                    <a class="btn btn-xs btn-info" title="'.tr('Aggiungi informazioni FE per questa riga').'" onclick="apriInformazioniFE(this)">
                        <i class="fa fa-file-code-o"></i>
                    </a>

                    <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </a>

                    <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
                        <i class="fa fa-trash"></i>
                    </a>

                    <a class="btn btn-xs btn-default handle" title="'.tr('Modifica ordine delle righe').'">
                        <i class="fa fa-sort"></i>
                    </a>
                </div>';
    }

    echo '
            </td>
        </tr>';
}

echo '
        </tbody>';

// Individuazione dei totali
$imponibile = $fattura->imponibile;
$sconto = $fattura->sconto;
$totale_imponibile = $fattura->totale_imponibile;
$iva = $fattura->iva;
$totale = $fattura->totale;
$netto_a_pagare = $fattura->netto;
$rivalsa_inps = $fattura->rivalsa_inps;
$ritenuta_acconto = $fattura->ritenuta_acconto;
$ritenuta_contributi = $fattura->totale_ritenuta_contributi;

// Inversione dei valori per le Note
$imponibile = $fattura->isNota() ? -$imponibile : $imponibile;
$sconto = $fattura->isNota() ? -$sconto : $sconto;
$totale_imponibile = $fattura->isNota() ? -$totale_imponibile : $totale_imponibile;
$iva = $fattura->isNota() ? -$iva : $iva;
$totale = $fattura->isNota() ? -$totale : $totale;
$netto_a_pagare = $fattura->isNota() ? -$netto_a_pagare : $netto_a_pagare;
$rivalsa_inps = $fattura->isNota() ? -$rivalsa_inps : $rivalsa_inps;
$ritenuta_acconto = $fattura->isNota() ? -$ritenuta_acconto : $ritenuta_acconto;
$ritenuta_contributi = $fattura->isNota() ? -$ritenuta_contributi : $ritenuta_contributi;

// IMPONIBILE
echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($imponibile, 2).'
            </td>
            <td></td>
        </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
        <tr>
            <td colspan="5" class="text-right">
                <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"><i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>
            <td class="text-right">
                '.moneyFormat($sconto, 2).'
            </td>
            <td></td>
        </tr>';

    // TOTALE IMPONIBILE
    echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($totale_imponibile, 2).'
            </td>
            <td></td>
        </tr>';
}

// RIVALSA INPS
if (!empty($rivalsa_inps)) {
    echo '
        <tr>
            <td colspan="5" class="text-right">';

    if ($dir == 'entrata') {
        $descrizione_rivalsa = $database->fetchOne('SELECT CONCAT_WS(\' - \', codice, descrizione) AS descrizione FROM fe_tipo_cassa WHERE codice = '.prepare(setting('Tipo Cassa Previdenziale')));
        echo '
				<span class="tip" title="'.$descrizione_rivalsa['descrizione'].'">
				    <i class="fa fa-question-circle-o"></i>
                </span> ';
    }

    echo '
                <b>'.tr('Rivalsa', [], ['upper' => true]).' :</b>
            </td>
            <td class="text-right">
                '.moneyFormat($rivalsa_inps, 2).'
            </td>
            <td></td>
        </tr>';
}

// IVA
if (!empty($iva)) {
    echo '
        <tr>
            <td colspan="5" class="text-right">';

    if ($records[0]['split_payment']) {
        echo '<b>'.tr('Iva a carico del destinatario', [], ['upper' => true]).':</b>';
    } else {
        echo '<b>'.tr('Iva', [], ['upper' => true]).':</b>';
    }
    echo '
            </td>
            <td class="text-right">
                '.moneyFormat($iva, 2).'
            </td>
            <td></td>
        </tr>';
}

// TOTALE
echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($totale, 2).'
            </td>
            <td></td>
        </tr>';

// RITENUTA D'ACCONTO
if (!empty($ritenuta_acconto)) {
    echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($ritenuta_acconto, 2).'
            </td>
            <td></td>
        </tr>';
}

// RITENUTA CONTRIBUTI
if (!empty($ritenuta_contributi)) {
    echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr('Ritenuta contributi', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($ritenuta_contributi, 2).'
            </td>
            <td></td>
        </tr>';
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
        <tr>
            <td colspan="5" class="text-right">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($netto_a_pagare, 2).'
            </td>
            <td></td>
        </tr>';
}

echo '
    </table>
</div>';

echo '
<script>
async function modificaRiga(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    // Salvataggio via AJAX
    let valid = await salvaForm(button, $("#edit-form"));

    if (valid) {
        // Chiusura tooltip
        if ($(button).hasClass("tooltipstered"))
            $(button).tooltipster("close");

        // Apertura modal
        openModal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
    }
}

function rimuoviRiga(button) {
    swal({
        title: "'.tr('Rimuovere questa riga?').'",
        html: "'.tr('Sei sicuro di volere rimuovere questa riga dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        let riga = $(button).closest("tr");
        let id = riga.data("id");
        let type = riga.data("type");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_riga",
                riga_type: type,
                riga_id: id,
            },
            success: function (response) {
                location.reload();
            },
            error: function() {
                location.reload();
            }
        });
    }).catch(swal.noop);
}

function modificaSeriali(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Aggiorna SN').'", globals.rootdir + "/modules/fatture/add_serial.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

function apriInformazioniFE(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Dati Fattura Elettronica').'", "'.$module->fileurl('fe/row-fe.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type)
}

$(document).ready(function() {
	$(".sortable").each(function() {
        $(this).sortable({
            axis: "y",
            handle: ".handle",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			update: function(event, ui) {
                let order = $(".table tr[data-id]").toArray().map(a => $(a).data("id"))

				$.post(globals.rootdir + "/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
                    order: order.join(","),
				});
			}
		});
	});
});
</script>';
