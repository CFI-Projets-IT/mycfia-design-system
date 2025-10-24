<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Composant DataTable réutilisable pour affichage de listes structurées.
 *
 * Utilisé dans le chat IA pour afficher factures, commandes, stocks sous forme de tableau.
 * Support des thèmes light, dark-blue, dark-red avec design Bootstrap 5.
 *
 * Fonctionnalités :
 * - Headers dynamiques
 * - Lignes de données avec support colonnes cliquables
 * - Ligne Total optionnelle (style différencié)
 * - Icônes PDF (placeholder pour future implémentation)
 * - Responsive avec scroll horizontal mobile
 * - Liens cliquables pour actions (ex: détails facture)
 */
#[AsTwigComponent]
final class DataTable
{
    /**
     * En-têtes du tableau.
     *
     * @var array<int, string>
     *
     * @example ['ID', 'NOM', 'BON DE COMMANDE', 'MOIS FACTURATION']
     */
    public array $headers = [];

    /**
     * Lignes de données du tableau.
     *
     * @var array<int, array<string, mixed>>
     *
     * @example [
     *     ['id' => '1135', 'nom' => 'NATIONAL\TDR', 'bon' => '', 'mois' => 'février 2023'],
     *     ['id' => '1135', 'nom' => 'NATIONAL\TDR', 'bon' => '', 'mois' => 'mars 2023'],
     * ]
     */
    public array $rows = [];

    /**
     * Ligne de total optionnelle affichée en bas du tableau.
     *
     * @var array<string, mixed>|null
     *
     * @example ['label' => 'Total', 'montant_ht' => '25285,91 €', 'montant_ttc' => '30343,09 €']
     */
    public ?array $totalRow = null;

    /**
     * Configuration des colonnes cliquables avec prompts.
     *
     * @var array<string, string>
     *
     * @example ['id' => 'Voir détails facture {id}']
     */
    public array $linkColumns = [];

    /**
     * Activer les lignes alternées (striped).
     */
    public bool $striped = true;

    /**
     * Activer l'effet hover sur les lignes.
     */
    public bool $hover = true;

    /**
     * Activer le mode responsive avec scroll horizontal.
     */
    public bool $responsive = true;

    /**
     * Afficher l'icône PDF (placeholder, pas encore fonctionnel).
     */
    public bool $showPdfIcon = false;

    /**
     * Colonne où afficher l'icône PDF.
     *
     * @example 'id'
     */
    public string $pdfColumn = 'id';
}
