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
    ) {
    }

    public function generate(Facture $facture): string
    {
        $intervention = $facture->getIntervention();
        $vehicule = $intervention->getVehicule();
        $client = $vehicule->getProprietaire();

        $lignesHtml = '';
        foreach ($facture->getLignesFacture() as $ligne) {
            $lignesHtml .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td style="text-align:center;">%d</td>
                    <td style="text-align:right;">%s €</td>
                    <td style="text-align:right;">%s €</td>
                </tr>',
                htmlspecialchars($ligne->getDescription()),
                $ligne->getQuantite(),
                number_format((float) $ligne->getPrixUnitaire(), 2, ',', ' '),
                number_format((float) $ligne->getSousTotal(), 2, ',', ' ')
            );
        }

        $html = <<<HTML
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #222; }
                .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .company-name { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
                .company-info { font-size: 11px; color: #555; line-height: 1.5; }
                .invoice-title { font-size: 18px; font-weight: bold; margin-top: 20px; }
                .client-info { margin: 20px 0; padding: 15px; background: #f7f7f7; border-radius: 4px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #222; color: white; padding: 8px; text-align: left; font-size: 11px; }
                td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 11px; }
                .total-row { font-weight: bold; font-size: 14px; }
                .footer { margin-top: 40px; font-size: 10px; color: #888; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="company-name">{$this->companyName}</div>
                    <div class="company-info">
                        {$this->companyAddress}<br>
                        Tél : {$this->companyPhone}<br>
                        {$this->companyEmail}<br>
                        SIRET : {$this->companySiret}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="invoice-title">FACTURE</div>
                    <div>N° {$facture->getNumeroFacture()}</div>
                    <div>Date : {$facture->getDateEmission()->format('d/m/Y')}</div>
                </div>
            </div>

            <div class="client-info">
                <strong>Facturé à :</strong><br>
                {$client->getPrenom()} {$client->getNom()}<br>
                {$client->getEmail()}<br>
                {$client->getAdresse()}
            </div>

            <div>
                <strong>Véhicule :</strong> {$vehicule->getMarque()} {$vehicule->getModele()} — {$vehicule->getImmatriculation()}
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align:center;">Qté</th>
                        <th style="text-align:right;">Prix unitaire</th>
                        <th style="text-align:right;">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    {$lignesHtml}
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">TOTAL TTC</td>
                        <td style="text-align:right;">{$this->formatMontant($facture->getMontantTotal())} €</td>
                    </tr>
                </tbody>
            </table>

            <div class="footer">
                {$this->companyName} — {$this->companyAddress} — SIRET {$this->companySiret}
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
