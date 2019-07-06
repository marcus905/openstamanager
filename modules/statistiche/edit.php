<?php

use Modules\Statistiche\Stats;

echo '
<script src="'.ROOTDIR.'/assets/js/chartjs/Chart.min.js"></script>';

$start = $_SESSION['period_start'];
$end = $_SESSION['period_end'];

echo '
<h3 class="text-center">
    <span class="label label-primary">'.tr('Periodo dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($start),
        '_END_' => Translator::dateToLocale($end),
    ]).'</span>
</h3>
<hr>

<script>
$(document).ready(function() {
    start = moment("'.$start.'");
    end = moment("'.$end.'");

    months = [];
    while(start.isSameOrBefore(end, "month")){
        string = start.format("MMMM YYYY");

        months.push(string.charAt(0).toUpperCase() + string.slice(1));

        start.add(1, "months");
    }
});
</script>';

$fatturato = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS totale, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');
$acquisti = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS totale, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='uscita' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

$fatturato = Stats::monthly($fatturato, $start, $end);
$acquisti = Stats::monthly($acquisti, $start, $end);

// Fatturato
echo '
<div class="card card-outline card-success">
    <div class="card-header with-border">
        <h3 class="card-title">'.tr('Vendite e acquisti').'</h3>

        <div class="card-tools float-right">
            <button type="button" class="btn btn-card-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="card-body" id="fatturato" height="100"></canvas>
</div>';

// Script per il grafico del fatturato
echo '
<script>
$(document).ready(function() {
    new Chart(document.getElementById("fatturato").getContext("2d"), {
        type: "bar",
        data: {
            labels: months,
            datasets: [
                {
                    label: "'.tr('Fatturato (iva esclusa)').'",
                    backgroundColor: "#63E360",
                    data: [
                        '.implode(',', array_column($fatturato, 'totale')).'
                    ]
                },
                {
                    label: "'.tr('Acquisti (iva esclusa)').'",
                    backgroundColor: "#EE4B4B",
                    data: [
                        '.implode(',', array_column($acquisti, 'totale')).'
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: "bottom",
            },
			scales: {
				yAxes: [{
					ticks: {
						// Include a dollar sign in the ticks
						callback: function(value, index, values) {
							return \'€ \' + value;
						}
					}
				}]
			},
        }
    });
});
</script>';

// Clienti top
$clienti = $dbo->fetchArray('SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale, (SELECT COUNT(*) FROM co_documenti WHERE co_documenti.idanagrafica =an_anagrafiche.idanagrafica AND co_documenti.data BETWEEN '.prepare($start).' AND '.prepare($end).") AS qta, an_anagrafiche.idanagrafica, an_anagrafiche.ragione_sociale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY an_anagrafiche.idanagrafica ORDER BY SUM(subtotale - sconto) DESC LIMIT 20');

$totale = $dbo->fetchArray("SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end));

echo '
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header with-border">
                <h3 class="card-title">'.tr('I 20 clienti TOP').'</h3><span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span>

                <div class="card-tools float-right">
                    <button type="button" class="btn btn-card-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';
if (!empty($clienti)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th class="col-md-6" >'.tr('Ragione sociale').'</th>
                        <th class="text-center">'.tr('Num. fatture').'</th>
                        <th class="text-right">'.tr('Totale').'</th>
                        <th class="text-right">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul fatturato').'">&nbsp;<i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                    </tr>';
    foreach ($clienti as $cliente) {
        echo '
                    <tr>
                        <td>'.Modules::link('Anagrafiche', $cliente['idanagrafica'], $cliente['ragione_sociale']).'</td>
                        <td class="text-center">'.intval($cliente['qta']).'</td>
                        <td class="text-right">'.moneyFormat($cliente['totale']).'</td>
                        <td class="text-right">'.Translator::numberToLocale($cliente['totale'] * 100 / $totale[0]['totale']).' %</td>
                    </tr>';
    }
    echo '
                </table>';
} else {
    echo '
                <p>'.tr('Nessuna vendita').'...</p>';
}
echo '

            </div>
        </div>
    </div>';

// Articoli più venduti
$articoli = $dbo->fetchArray("SELECT SUM(co_righe_documenti.qta) AS qta, SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale, mg_articoli.id, mg_articoli.codice, mg_articoli.descrizione, mg_articoli.um FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY co_righe_documenti.idarticolo ORDER BY SUM(co_righe_documenti.qta) DESC LIMIT 20');

$totale = $dbo->fetchArray("SELECT SUM(co_righe_documenti.qta) AS totale_qta, SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end));

echo '
    <div class="col-md-6">
        <div class="card card-outline card-danger">
            <div class="card-header with-border">
                <h3 class="card-title">'.tr('I 20 articoli più venduti').'</h3><span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span>

                <div class="card-tools float-right">
                    <button type="button" class="btn btn-card-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">';
if (!empty($articoli)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th>'.tr('Codice').'</th>
                        <th class="col-md-6" >'.tr('Descrizione').'</th>
                        <th class="text-right">'.tr('Q.tà').'</th>
                        <th class="text-right">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul numero di articoli venduti').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                        <th class="text-right">'.tr('Totale').'</th>
                    </tr>';
    foreach ($articoli as $articolo) {
        echo '
                    <tr>
                        <td>'.Modules::link('Articoli', $articolo['id'], $articolo['codice']).'</td>
                        <td>'.$articolo['descrizione'].'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta']).' '.$articolo['um'].'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta'] * 100 / $totale[0]['totale_qta']).' %</td>
                        <td class="text-right">'.moneyFormat($articolo['totale']).'</td>
                    </tr>';
    }
    echo '
                </table>';
} else {
    echo '
                <p>'.tr('Nessun articolo è stato venduto').'...</p>';
}
echo '

            </div>
        </div>
    </div>
</div>';

// Interventi per tipologia
$tipi = $dbo->fetchArray('SELECT * FROM `in_tipiintervento`');

$dataset = '';
foreach ($tipi as $tipo) {
    $interventi = $dbo->fetchArray('SELECT COUNT(*) AS totale, YEAR(in_interventi.data_richiesta) AS year, MONTH(in_interventi.data_richiesta) AS month FROM in_interventi WHERE in_interventi.id_tipo_intervento = '.prepare($tipo['id_tipo_intervento']).' AND in_interventi.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(in_interventi.data_richiesta), MONTH(in_interventi.data_richiesta) ORDER BY YEAR(in_interventi.data_richiesta) ASC, MONTH(in_interventi.data_richiesta) ASC');

    $interventi = Stats::monthly($interventi, $start, $end);

    //Random color
    $background = '#'.dechex(rand(256, 16777215));

    $dataset .= '{
        label: "'.$tipo['descrizione'].'",
        backgroundColor: "'.$background.'",
        data: [
            '.implode(',', array_column($interventi, 'totale')).'
        ]
    },';
}

echo '
<div class="card card-outline card-info">
    <div class="card-header with-border">
        <h3 class="card-title">'.tr('Interventi per tipologia').'</h3>

        <div class="card-tools float-right">
            <button type="button" class="btn btn-card-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="card-body" id="interventi" height="100"></canvas>
</div>';

// Script per il grafico del fatturato
echo '
<script>
$(document).ready(function() {
    new Chart(document.getElementById("interventi").getContext("2d"), {
        type: "bar",
        data: {
            labels: months,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: "bottom",
            },
        }
    });
});
</script>';
