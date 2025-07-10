<?php
/**
 * Snippet ID: 140
 * Name: FILTRES page planning global fournisseur dispos
 * Description: 
 * @active false
 */

add_action('admin_footer', 'init_advanced_planning_table_filters');
function init_advanced_planning_table_filters() {
    // Optionnel : cibler seulement la page "gestion-plannings"
    if (!isset($_GET['page']) || $_GET['page'] !== 'gestion-plannings') return;
    ?>
    <!-- Bloc de filtres toujours visible -->
    <div id="planning-filters-container" 
         style="background:#efefef; padding:10px; border:1px solid #ccc; margin-bottom:10px; border-radius:5px;">
      <h2 style="margin:0 0 5px 0; font-size:16px;">Filtres Avancés</h2>
      <p style="margin-top:0;">Utilisez ces filtres pour affiner l'affichage du tableau ci-dessous.</p>

      <!-- Conteneur FLEX horizontal, sans wrap -->
      <div id="filters-wrap" style="
          display: flex;
          flex-wrap: nowrap;
          gap: 20px;
          white-space: nowrap;   /* Empêche le retour à la ligne */
          overflow-x: auto;      /* Barre de défilement horizontale si besoin */
          padding-bottom: 5px;">
      </div>

      <!-- Bouton de réinitialisation -->
      <button id="reset-all-filters" class="button button-secondary">
        Réinitialiser tous les filtres
      </button>
    </div>

    <script>
    jQuery(document).ready(function($){
        // ==============================
        // 1) Colonnes à filtrer
        // ==============================
        var columnsToFilter = [
            { selector: '.col-date',        label: 'Date' },
            { selector: '.col-nom',         label: 'Nom' },
            { selector: '.col-type',        label: 'Type' },
            { selector: '.col-dispo',       label: 'Dispo' },
            { selector: '.col-etat',        label: 'État' },
            { selector: '.col-action',      label: 'Action' },
            { selector: '.col-formation',   label: 'Formation' },
            { selector: '.col-lieu',        label: 'Lieu' },
            { selector: '.col-inter_intra', label: 'Inter/Intra' },
            { selector: '.col-booke',       label: 'Booké' },
            { selector: '.col-commentaire', label: 'Commentaire' }
        ];

        // Récupérer les lignes de données (en excluant éventuellement celles avec fond gris)
        var $allRows  = $('#planning-table tbody tr');
        var $dataRows = $allRows.not('[style*="background:#ccc"]');

        // Tableau d’options distinctes par colonne
        var columnValues = {};
        columnsToFilter.forEach(function(col){
            columnValues[col.selector] = [];
        });

        // Extraire les valeurs présentes par colonne
        $dataRows.each(function(){
            var $row = $(this);
            columnsToFilter.forEach(function(col){
                var cellText = $.trim($row.find(col.selector).text());

                // Si la cellule a un input text
                var $textInput = $row.find(col.selector).find('input[type="text"]');
                if ($textInput.length) {
                    cellText = $.trim($textInput.val()) || cellText;
                }

                // Si la cellule a un select
                var $select = $row.find(col.selector).find('select');
                if ($select.length) {
                    cellText = $.trim($select.find('option:selected').text()) || cellText;
                }

                if (cellText && columnValues[col.selector].indexOf(cellText) === -1) {
                    columnValues[col.selector].push(cellText);
                }
            });
        });

        // ==============================
        // 2) Construire l'UI des filtres
        // ==============================
        $('#filters-wrap').empty();
        columnsToFilter.forEach(function(col){
            var colValArray = columnValues[col.selector].sort();
            var colId       = col.selector.replace('.',''); // ex: .col-date => col-date

            // Conteneur du filtre
            var $filterGroup = $(
                '<div class="filter-group" id="filter-'+colId+'" ' +
                ' style="border:1px solid #ccc; padding:8px; border-radius:5px;'+
                '       min-width:130px; max-width:200px;">' +
                '  <h3 style="margin-top:0; font-size:14px;">'+col.label+'</h3>' +
                '</div>'
            );

            // Bouton "Cocher/Décocher tout"
            var $selectAllBtn = $('<button type="button" class="button button-small">Cocher / Décocher tout</button>')
                .css({'margin-bottom':'6px'});

            // Zone liste des cases
            var $optionsWrap = $('<div class="filter-options" style="max-height:150px; overflow:auto;"></div>');

            // Générer une checkbox par valeur
            colValArray.forEach(function(val){
                var safeVal = val.replace(/"/g, '&quot;');
                var $cb = $(
                    '<label style="display:block; margin-bottom:2px;">'+
                    '  <input type="checkbox" checked value="'+safeVal+'"> ' + val +
                    '</label>'
                );
                $optionsWrap.append($cb);
            });

            // Au clic sur "Cocher/Décocher tout"
            $selectAllBtn.on('click', function(){
                var allChecked = $optionsWrap.find('input[type="checkbox"]:checked').length 
                               === $optionsWrap.find('input[type="checkbox"]').length;
                var newState   = !allChecked; // si tout est coché => on décoche tout, et vice versa
                $optionsWrap.find('input[type="checkbox"]').prop('checked', newState);
                applyFilters();
            });

            // Au changement d’une case
            $optionsWrap.on('change', 'input[type="checkbox"]', function(){
                applyFilters();
            });

            // Assembler
            $filterGroup.append($selectAllBtn);
            $filterGroup.append($optionsWrap);
            $('#filters-wrap').append($filterGroup);
        });

        // ==============================
        // 3) Réinitialiser tous les filtres
        // ==============================
        $('#reset-all-filters').on('click', function(){
            $('#filters-wrap').find('input[type="checkbox"]').prop('checked', true);
            $dataRows.show(); // réaffiche toutes les lignes
        });

        // ==============================
        // 4) Fonction de filtrage
        // ==============================
        function applyFilters(){
            $dataRows.hide();

            $dataRows.each(function(){
                var $row = $(this);
                var rowOk = true;

                columnsToFilter.forEach(function(col){
                    if (!rowOk) return;

                    // Liste des valeurs cochées pour cette colonne
                    var colId       = col.selector.replace('.','');
                    var $checked    = $('#filter-'+colId).find('input[type="checkbox"]:checked');
                    var checkedVals = $checked.map(function(){ return $(this).val(); }).get();

                    // Si aucune case cochée => on ignore ce filtre
                    if (checkedVals.length === 0) return;

                    // Valeur de la cellule
                    var cellText = $.trim($row.find(col.selector).text());
                    var $textInput = $row.find(col.selector).find('input[type="text"]');
                    if ($textInput.length) {
                        cellText = $.trim($textInput.val()) || cellText;
                    }
                    var $select = $row.find(col.selector).find('select');
                    if ($select.length) {
                        cellText = $.trim($select.find('option:selected').text()) || cellText;
                    }

                    // Si la valeur de la cellule n’est pas dans la liste cochée, on exclut la ligne
                    if (checkedVals.indexOf(cellText) === -1) {
                        rowOk = false;
                    }
                });

                // Afficher la ligne si elle passe tous les filtres
                if (rowOk) $row.show();
            });
        }
    });
    </script>

    <style>
    #planning-filters-container {
        margin-top: 5px;
    }
    .filter-group h3 {
        margin: 0 0 5px 0;
    }
    </style>
    <?php
}
