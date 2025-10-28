/**
 * DataTable Renderer - Module pour générer les tableaux de données dans le chat
 *
 * Détecte les métadonnées `table_data` dans les réponses de l'IA
 * et génère le HTML du composant DataTable côté client.
 *
 * Structure attendue de table_data :
 * {
 *   headers: ['ID', 'NOM', 'BON DE COMMANDE', 'MOIS FACTURATION'],
 *   rows: [
 *     { id: '1135', nom: 'NATIONAL\\TDR', bon: '', mois: 'février 2023' },
 *     ...
 *   ],
 *   totalRow: { label: 'Total', montant_ht: '25285,91 €', ... },
 *   linkColumns: { id: 'Voir détails facture {id}' }
 * }
 */

/**
 * Vérifier si les métadonnées contiennent des données de tableau.
 *
 * @param {Object} metadata - Métadonnées de la réponse IA
 * @returns {boolean}
 */
export function hasTableData(metadata) {
    return metadata &&
           metadata.table_data &&
           Array.isArray(metadata.table_data.headers) &&
           Array.isArray(metadata.table_data.rows) &&
           metadata.table_data.headers.length > 0 &&
           metadata.table_data.rows.length > 0;
}

/**
 * Générer le HTML du tableau DataTable.
 *
 * @param {Object} tableData - Données du tableau depuis metadata.table_data
 * @returns {string} HTML du tableau
 */
export function renderDataTable(tableData) {
    const { headers, rows, totalRow, linkColumns } = tableData;

    // 1. Générer les en-têtes
    const theadHtml = `
        <thead>
            <tr>
                ${headers.map(header => `<th scope="col">${escapeHtml(header)}</th>`).join('')}
            </tr>
        </thead>
    `;

    // 2. Générer les lignes de données
    const tbodyHtml = `
        <tbody>
            ${rows.map(row => {
                return `
                    <tr>
                        ${headers.map((header, index) => {
                            const key = Object.keys(row)[index];
                            const value = row[key];

                            // Si cette colonne a un lien cliquable configuré
                            if (linkColumns && linkColumns[key] && value) {
                                const prompt = linkColumns[key].replace(`{${key}}`, value);
                                return `
                                    <td>
                                        <a href="#"
                                           class="detail-link text-decoration-none fw-semibold"
                                           data-action-prompt="${escapeHtml(prompt)}"
                                           data-entity-id="${escapeHtml(value)}"
                                           title="Cliquer pour voir les détails">
                                            ${escapeHtml(value)}
                                        </a>
                                    </td>
                                `;
                            }

                            return `<td>${escapeHtml(value || '')}</td>`;
                        }).join('')}
                    </tr>
                `;
            }).join('')}
        </tbody>
    `;

    // 3. Générer la ligne Total (optionnelle)
    const tfootHtml = totalRow ? `
        <tfoot>
            <tr class="table-total fw-bold">
                ${headers.map((header, index) => {
                    const key = Object.keys(totalRow)[index];
                    const value = totalRow[key];
                    return `<td>${escapeHtml(value || '')}</td>`;
                }).join('')}
            </tr>
        </tfoot>
    ` : '';

    // 4. Assembler le tableau complet
    return `
        <div class="chat-datatable table-responsive">
            <table class="table table-striped table-hover mb-0">
                ${theadHtml}
                ${tbodyHtml}
                ${tfootHtml}
            </table>
        </div>
    `;
}

/**
 * Échapper le HTML pour éviter les injections XSS.
 *
 * @param {string} text - Texte à échapper
 * @returns {string}
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}
