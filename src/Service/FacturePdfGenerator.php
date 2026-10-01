<?php

namespace App\Service;

use App\Entity\Facture;
use Dompdf\Dompdf;
use Dompdf\Options;

class FacturePdfGenerator
{
    public function __construct(
        private string $companyName,
        private string $companyAddress,
        private string $companyPhone,
        private string $companyEmail,
        private string $companySiret,
        private ?string $companyTvaIntra = null,
    ) {
    }

    public function generate(Facture $facture): string
    {
        $intervention = $facture->getIntervention();
        $vehicule = $intervention->getVehicule();
        $client = $vehicule->getProprietaire();

        $esc = static fn(?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        $lignesHtml = '';
        foreach ($facture->getLignesFacture() as $ligne) {
            $designation = $ligne->getDescription() ?: 'Prestation atelier';
            $quantite = $ligne->getQuantite() ?: 1;
            $prixUnitaireHT = (float) $ligne->getPrixUnitaire();
            $tauxTva = (float) $ligne->getTauxTva();
            $montantHT = (float) $ligne->getMontantHT();

            $lignesHtml .= sprintf(
                '<tr>
                    <td class="col-desc">%s</td>
                    <td style="text-align:center;">%d</td>
                    <td style="text-align:right;">%s €</td>
                    <td style="text-align:center;">%s %%</td>
                    <td style="text-align:right;">%s €</td>
                </tr>',
                $esc($designation),
                $quantite,
                number_format($prixUnitaireHT, 2, ',', ' '),
                number_format($tauxTva, 1, ',', ' '),
                number_format($montantHT, 2, ',', ' ')
            );
        }

        $companyNameEsc = $esc($this->companyName);
        $companyAddressEsc = nl2br($esc($this->companyAddress));
        $companyPhoneEsc = $esc($this->companyPhone);
        $companyEmailEsc = $esc($this->companyEmail);
        $companySiretEsc = $esc($this->companySiret);
        $companyTvaIntraEsc = $this->companyTvaIntra ? $esc($this->companyTvaIntra) : '';

        $numeroFactureEsc = $esc($facture->getNumeroFacture());
        $dateEmissionEsc = $esc($facture->getDateEmission()?->format('d/m/Y') ?? date('d/m/Y'));
        $datePrestationEsc = $esc($facture->getDatePrestation()?->format('d/m/Y') ?? $dateEmissionEsc);
        $dateEcheanceEsc = $esc($facture->getDateEcheance()?->format('d/m/Y') ?? date('d/m/Y', strtotime('+30 days')));

        $clientNomCompletEsc = $esc(trim(($client->getPrenom() ?? '') . ' ' . ($client->getNom() ?? '')));
        $clientEmailEsc = $esc($client->getEmail());
        $clientAdresseEsc = nl2br($esc($client->getAdresse() ?? ''));

        $vehiculeMarqueEsc = $esc($vehicule->getMarque());
        $vehiculeModeleEsc = $esc($vehicule->getModele());
        $vehiculeImmatEsc = $esc($vehicule->getImmatriculation());

        $totalHTFormatted = $this->formatMontant($facture->getMontantHT());
        $totalTVAFormatted = $this->formatMontant($facture->getMontantTVA());
        $totalTTCFormatted = $this->formatMontant($facture->getMontantTTC());
        $tauxTvaGlobal = number_format((float) $facture->getTauxTva(), 1, ',', ' ');

        $tvaIntraHtml = $companyTvaIntraEsc
            ? "<div><strong>N° TVA intracommunautaire :</strong> {$companyTvaIntraEsc}</div>"
            : '';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Facture {$numeroFactureEsc}</title>
            <style>
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    font-size: 11px;
                    color: #222;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                }
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 25px;
                }
                .header-table td {
                    vertical-align: top;
                    border: none;
                    padding: 0;
                }
                .company-name {
                    font-size: 20px;
                    font-weight: bold;
                    color: #111;
                    letter-spacing: 0.5px;
                    margin-bottom: 6px;
                }
                .company-info {
                    font-size: 10.5px;
                    color: #444;
                    line-height: 1.5;
                }
                .invoice-box {
                    background: #fdfdfd;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    padding: 12px 16px;
                    text-align: right;
                }
                .invoice-title {
                    font-size: 18px;
                    font-weight: bold;
                    color: #111;
                    margin-bottom: 4px;
                }
                .invoice-number {
                    font-size: 13px;
                    font-weight: bold;
                    color: #dcae00;
                    margin-bottom: 8px;
                }
                .invoice-meta {
                    font-size: 10.5px;
                    color: #555;
                    line-height: 1.5;
                }
                .client-box {
                    width: 100%;
                    background: #f9f9f9;
                    border: 1px solid #e5e5e5;
                    border-radius: 6px;
                    padding: 12px 14px;
                    margin-bottom: 18px;
                }
                .client-title {
                    font-size: 11px;
                    font-weight: bold;
                    color: #333;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 6px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 4px;
                }
                .vehicle-badge {
                    display: inline-block;
                    background: #eef2f6;
                    border: 1px solid #d0d7de;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 10.5px;
                    margin-bottom: 20px;
                }
                table.lines-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                    margin-bottom: 20px;
                }
                table.lines-table th {
                    background: #1f2937;
                    color: #ffffff;
                    padding: 8px 10px;
                    font-size: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border: 1px solid #1f2937;
                }
                table.lines-table td {
                    padding: 8px 10px;
                    border-bottom: 1px solid #e5e5e5;
                    font-size: 10.5px;
                }
                .col-desc {
                    width: 44%;
                    font-weight: 500;
                }
                .totals-container {
                    width: 100%;
                    margin-bottom: 30px;
                }
                .totals-table {
                    width: 260px;
                    margin-left: auto;
                    border-collapse: collapse;
                }
                .totals-table td {
                    padding: 6px 10px;
                    font-size: 11px;
                }
                .totals-table .total-label {
                    text-align: left;
                    color: #555;
                }
                .totals-table .total-val {
                    text-align: right;
                    font-weight: 600;
                }
                .totals-table .row-ttc {
                    background: #f5f5f5;
                    border-top: 2px solid #222;
                    border-bottom: 2px solid #222;
                }
                .totals-table .row-ttc td {
                    padding: 8px 10px;
                    font-size: 13px;
                    font-weight: bold;
                    color: #000;
                }
                .legal-notices {
                    margin-top: 30px;
                    padding: 12px 14px;
                    background: #fbfbfb;
                    border: 1px solid #eee;
                    border-radius: 6px;
                    font-size: 8.5px;
                    color: #666;
                    line-height: 1.45;
                }
                .legal-notices strong {
                    color: #333;
                }
                .footer {
                    margin-top: 25px;
                    font-size: 9px;
                    color: #888;
                    text-align: center;
                    border-top: 1px solid #e5e5e5;
                    padding-top: 10px;
                }
            </style>
        </head>
        <body>
            <table class="header-table">
                <tr>
                    <td style="width: 55%;">
                        <div class="company-name">{$companyNameEsc}</div>
                        <div class="company-info">
                            {$companyAddressEsc}<br>
                            <strong>Tél :</strong> {$companyPhoneEsc}<br>
                            <strong>Email :</strong> {$companyEmailEsc}<br>
                            <strong>SIRET :</strong> {$companySiretEsc}<br>
                            {$tvaIntraHtml}
                        </div>
                    </td>
                    <td style="width: 45%;">
                        <div class="invoice-box">
                            <div class="invoice-title">FACTURE</div>
                            <div class="invoice-number">N° {$numeroFactureEsc}</div>
                            <div class="invoice-meta">
                                <strong>Date d'émission :</strong> {$dateEmissionEsc}<br>
                                <strong>Date de prestation :</strong> {$datePrestationEsc}<br>
                                <strong>Échéance de paiement :</strong> {$dateEcheanceEsc}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="client-box">
                <div class="client-title">Facturé à :</div>
                <strong>{$clientNomCompletEsc}</strong><br>
                {$clientEmailEsc}<br>
                {$clientAdresseEsc}
            </div>

            <div class="vehicle-badge">
                <strong>Véhicule pris en charge :</strong> {$vehiculeMarqueEsc} {$vehiculeModeleEsc} — <strong>Immatriculation :</strong> {$vehiculeImmatEsc}
            </div>

            <table class="lines-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Désignation</th>
                        <th style="text-align:center; width: 45px;">Qté</th>
                        <th style="text-align:right; width: 90px;">Prix unit. HT</th>
                        <th style="text-align:center; width: 65px;">Taux TVA</th>
                        <th style="text-align:right; width: 95px;">Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    {$lignesHtml}
                </tbody>
            </table>

            <div class="totals-container">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Total HT</td>
                        <td class="total-val">{$totalHTFormatted} €</td>
                    </tr>
                    <tr>
                        <td class="total-label">TVA ({$tauxTvaGlobal} %)</td>
                        <td class="total-val">{$totalTVAFormatted} €</td>
                    </tr>
                    <tr class="row-ttc">
                        <td class="total-label">TOTAL TTC</td>
                        <td class="total-val">{$totalTTCFormatted} €</td>
                    </tr>
                </table>
            </div>

            <div class="legal-notices">
                <!-- Mention franchise en base (si applicable dans le futur) : TVA non applicable, article 293 B du CGI -->
                <strong>Conditions de règlement :</strong> Paiement exigible à la date d'échéance : <strong>{$dateEcheanceEsc}</strong>.<br>
                <strong>Escompte :</strong> Pas d'escompte pour paiement anticipé.<br>
                <strong>Pénalités de retard :</strong> En cas de retard de paiement, une pénalité de 3 fois le taux d'intérêt légal sera appliquée, ainsi qu'une indemnité forfaitaire pour frais de recouvrement de 40 €, conformément aux articles L441-10 et D441-5 du Code de commerce.
            </div>

            <div class="footer">
                {$companyNameEsc} — {$companyAddressEsc} — SIRET : {$companySiretEsc}
            </div>
        </body>
        </html>
        HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function formatMontant(string $montant): string
    {
        return number_format((float) $montant, 2, ',', ' ');
    }
}
