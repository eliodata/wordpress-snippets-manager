<?php
/**
 * Snippet ID: 159
 * Name: PHP Tableau de bord des alertes page admin 
 * Description: 
 * @active false
 */



/**
 * PHP Tableau de bord des alertes page admin 
 */
/**
 * Plugin Name: Tableau de Bord Formations
 * Description: Tableau de bord administratif pour la gestion des formations
 * Version: 1.0
 * Author: Formation Stratégique
 */


// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Ajouter le menu au tableau de bord WordPress
function fs_dashboard_admin_menu() {
    add_menu_page(
        'Tableau de Bord Formations',
        'Tableau de Bord',
        'manage_options',
        'fs-dashboard',
        'fs_render_dashboard_page',
        'dashicons-analytics',
        3
    );
}
add_action('admin_menu', 'fs_dashboard_admin_menu');

// Enregistrer les scripts et styles
function fs_dashboard_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_fs-dashboard') {
        return;
    }
    
    wp_enqueue_style('fs-dashboard-style', admin_url('admin-ajax.php?action=fs_dashboard_css'), array(), '1.0.0');
	wp_enqueue_script('fs-dashboard-script', admin_url('admin-ajax.php?action=fs_dashboard_js'), array('jquery'), '1.0.0', true);

    // Localiser les variables pour le JavaScript
    wp_localize_script('fs-dashboard-script', 'fs_dashboard', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fs_dashboard_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'fs_dashboard_enqueue_scripts');

// Fonction principale pour afficher le tableau de bord
function fs_render_dashboard_page() {
    ?>
    <div class="wrap fs-dashboard-wrap">
        <h1>Tableau de Bord des Formations</h1>
        
        <div class="fs-dashboard-summary">
            <div class="fs-stat-card">
                <div class="fs-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
                <div class="fs-stat-content">
                    <h3>Factures non réglées</h3>
                    <span class="fs-stat-value"><?php echo fs_get_unpaid_invoices_count(); ?></span>
                    <span class="fs-stat-label">Total: <?php echo fs_get_unpaid_invoices_total(); ?> €</span>
                </div>
            </div>
            
            <div class="fs-stat-card">
                <div class="fs-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                <div class="fs-stat-content">
                    <h3>Factures réglées (mois)</h3>
                    <span class="fs-stat-value"><?php echo fs_get_paid_invoices_count(); ?></span>
                    <span class="fs-stat-label">Total: <?php echo fs_get_paid_invoices_total(); ?> €</span>
                </div>
            </div>
            
            <div class="fs-stat-card">
                <div class="fs-stat-icon"><span class="dashicons dashicons-clipboard"></span></div>
                <div class="fs-stat-content">
                    <h3>Factures en attente</h3>
                    <span class="fs-stat-value"><?php echo fs_get_pending_invoices_count(); ?></span>
                    <span class="fs-stat-label">Formation terminée - Non facturée</span>
                </div>
            </div>
            
            <div class="fs-stat-card">
                <div class="fs-stat-icon"><span class="dashicons dashicons-groups"></span></div>
                <div class="fs-stat-content">
                    <h3>Formateurs à régler</h3>
                    <span class="fs-stat-value"><?php echo fs_get_trainers_to_pay_count(); ?></span>
                    <span class="fs-stat-label">Total: <?php echo fs_get_trainers_to_pay_total(); ?> €</span>
                </div>
            </div>
        </div>
        
        <div class="fs-dashboard-tabs">
            <div class="fs-tab-nav">
                <button class="fs-tab-button active" data-tab="alerts-urgent">Alertes Urgentes</button>
                <button class="fs-tab-button" data-tab="alerts-pre">Alertes Pré-Formation</button>
                <button class="fs-tab-button" data-tab="alerts-post">Alertes Post-Formation</button>
                <button class="fs-tab-button" data-tab="alerts-financial">Alertes Financières</button>
            </div>
            
            <div class="fs-tab-content active" id="alerts-urgent">
                <?php fs_render_urgent_alerts(); ?>
            </div>
            
            <div class="fs-tab-content" id="alerts-pre">
                <?php fs_render_pre_formation_alerts(); ?>
            </div>
            
            <div class="fs-tab-content" id="alerts-post">
                <?php fs_render_post_formation_alerts(); ?>
            </div>
            
            <div class="fs-tab-content" id="alerts-financial">
                <?php fs_render_financial_alerts(); ?>
            </div>
        </div>
    </div>
    <?php
}

// Fonction pour rendre les alertes urgentes
function fs_render_urgent_alerts() {
    ?>
    <div class="fs-alert-grid">
        <?php fs_render_alert_card(
            'Formations en erreur',
            fs_get_error_formations(),
            'red',
            'exclamation-triangle',
            'Formations signalées avec erreur - Facturation impossible'
        ); ?>
        
        <?php fs_render_alert_card(
            'Formations terminées - Émargements non reçus',
            fs_get_completed_formations_without_signatures(7),
            'red', 
            'welcome-write-blog',
            'Plus de 7 jours écoulés'
        ); ?>
        
        <?php fs_render_alert_card(
            'OPCO - Dossiers non reçus (formations passées)',
            fs_get_opco_missing_dossiers_past_formations(10),
            'red',
            'portfolio',
            'Plus de 10 jours depuis la formation'
        ); ?>
        
        <?php fs_render_alert_card(
            'Formateurs confirmés - Sans lettre de mission',
            fs_get_confirmed_trainers_without_mission_letter(),
            'red',
            'id-alt',
            'Lettre de mission non envoyée'
        ); ?>
    </div>
    <?php
}

// Fonction pour rendre les alertes pré-formation
function fs_render_pre_formation_alerts() {
    ?>
    <div class="fs-alert-grid">
        <?php fs_render_alert_card(
            'Formateurs optionnés non confirmés',
            fs_get_unconfirmed_option_trainers(15),
            'orange',
            'businessman',
            'À moins de 15 jours de la formation'
        ); ?>
        
        <?php fs_render_alert_card(
            'Formateurs prébloqués non confirmés',
            fs_get_unconfirmed_preblocked_trainers(15),
            'orange',
            'businessman',
            'À moins de 15 jours de la formation'
        ); ?>
        
        <?php fs_render_alert_card(
            'Formations confirmées sans formateur',
            fs_get_confirmed_formations_without_trainer(),
            'orange',
            'groups',
            'Formateur non ajouté au planning'
        ); ?>
        
        <?php fs_render_alert_card(
            'Inter-entreprises non confirmés',
            fs_get_unconfirmed_inter_formations(15),
            'orange',
            'calendar-alt',
            'À moins de 15 jours de la formation'
        ); ?>
        
        <?php fs_render_alert_card(
            'Inter-entreprises - Ressources manquantes',
            fs_get_inter_formations_missing_resources(),
            'orange',
            'admin-tools',
            'Salle/Matériel/Formateur/Prestataire non confirmé'
        ); ?>
        
        <?php fs_render_alert_card(
            'Options posées non confirmées',
            fs_get_unconfirmed_options(7),
            'orange',
            'clock',
            'Plus de 7 jours depuis la pose de l\'option'
        ); ?>
        
        <?php fs_render_alert_card(
            'Noms des stagiaires manquants',
            fs_get_formations_without_trainees(10),
            'orange',
            'id',
            'À moins de 10 jours de la formation'
        ); ?>
        
        <?php fs_render_alert_card(
            'OPCO - Dossiers non reçus (formations à venir)',
            fs_get_opco_missing_dossiers_upcoming_formations(),
            'orange',
            'portfolio',
            'Formation en attente'
        ); ?>
    </div>
    <?php
}

// Fonction pour rendre les alertes post-formation
function fs_render_post_formation_alerts() {
    ?>
    <div class="fs-alert-grid">
        <?php fs_render_alert_card(
            'Suivi non réalisé (+7 jours)',
            fs_get_completed_formations_without_followup(7),
            'orange',
            'phone',
            'Formation terminée - Suivi à effectuer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Suivi non réalisé (+15 jours)',
            fs_get_completed_formations_without_followup(15),
            'red',
            'phone',
            'Formation terminée - Suivi à effectuer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Suivi non réalisé (+30 jours)',
            fs_get_completed_formations_without_followup(30),
            'red',
            'phone',
            'Formation terminée - Suivi à effectuer'
        ); ?>
    </div>
    <?php
}

// Fonction pour rendre les alertes financières
function fs_render_financial_alerts() {
    ?>
    <div class="fs-alert-grid">
        <?php fs_render_alert_card(
            'Conventions signées non reçues',
            fs_get_signed_conventions_not_received(),
            'red',
            'media-document',
            'Conventions à relancer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Conventions non envoyées',
            fs_get_conventions_not_sent(),
            'red',
            'media-document',
            'Conventions à envoyer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Convocations en attente',
            fs_get_pending_convocations(),
            'orange',
            'email',
            'Formation confirmée - Convention non signée'
        ); ?>
        
        <?php fs_render_alert_card(
            'Factures non réglées (+30 jours)',
            fs_get_unpaid_invoices(30),
            'orange',
            'money-alt',
            'Première relance à effectuer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Factures non réglées (+60 jours)',
            fs_get_unpaid_invoices(60),
            'red',
            'money-alt',
            'Deuxième relance à effectuer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Factures non réglées (+90 jours)',
            fs_get_unpaid_invoices(90),
            'red',
            'money-alt',
            'Troisième relance à effectuer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Factures non réglées (+120 jours)',
            fs_get_unpaid_invoices(120),
            'red',
            'money-alt',
            'Procédure de recouvrement à lancer'
        ); ?>
        
        <?php fs_render_alert_card(
            'Devis à relancer',
            fs_get_quotes_to_follow_up(15),
            'orange',
            'media-text',
            'Plus de 15 jours sans réponse'
        ); ?>
    </div>
    <?php
}

// Fonction pour rendre une carte d'alerte générique
function fs_render_alert_card($title, $items, $severity, $icon, $description) {
    $count = count($items);
    $severity_class = "fs-severity-$severity";
    ?>
    <div class="fs-alert-card <?php echo $severity_class; ?>">
        <div class="fs-alert-header">
            <h3><span class="dashicons dashicons-<?php echo $icon; ?>"></span> <?php echo $title; ?></h3>
            <span class="fs-alert-count"><?php echo $count; ?></span>
        </div>
        
        <div class="fs-alert-description">
            <?php echo $description; ?>
        </div>
        
        <?php if ($count > 0) : ?>
            <div class="fs-alert-items">
                <ul>
                    <?php foreach ($items as $item) : ?>
                        <li>
                            <a href="<?php echo $item['edit_url']; ?>" target="_blank">
                                <?php echo $item['title']; ?>
                                <?php if (isset($item['date'])) : ?>
                                    <span class="fs-item-date"><?php echo $item['date']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($count > 5) : ?>
                    <div class="fs-alert-more">
                        <button class="fs-show-all" data-alert="<?php echo sanitize_title($title); ?>">
                            Voir tous (<?php echo $count; ?>)
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="fs-alert-empty">
                Aucun élément trouvé
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/******************************
 * FONCTIONS DE DONNÉES
 ******************************/

/**
 * Récupérer les formations en erreur
 */
function fs_get_error_formations() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT p.ID, p.post_title, pm.meta_value as error_reason
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'action-de-formation'
        AND pm.meta_key = 'fsbdd_formation_error'
        AND pm.meta_value = '1'
    ");
    
    $items = array();
    foreach ($results as $result) {
        $error_note = get_post_meta($result->ID, 'fsbdd_error_note', true);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title . ($error_note ? " - $error_note" : ""),
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formations terminées sans émargements
 */
function fs_get_completed_formations_without_signatures($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("-$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title, pm_end.meta_value as end_date
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_end ON p.ID = pm_end.post_id
        JOIN {$wpdb->postmeta} pm_emarg_state ON p.ID = pm_emarg_state.post_id
        WHERE p.post_type = 'action-de-formation'
        AND pm_end.meta_key = 'we_enddate'
        AND pm_emarg_state.meta_key = 'fsbdd_etatemargm'
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) <= %s
        AND (pm_emarg_state.meta_value != 'certifié' 
             AND pm_emarg_state.meta_value != 'reçu' 
             OR pm_emarg_state.meta_value IS NULL)
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $end_date_formatted = date_i18n('d/m/Y', $result->end_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $end_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les dossiers OPCO manquants pour formations passées
 */
function fs_get_opco_missing_dossiers_past_formations($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("-$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT o.ID, o.post_title, pm_end.meta_value as end_date
        FROM {$wpdb->posts} o
        JOIN {$wpdb->order_itemmeta} oim ON oim.meta_value = o.ID
        JOIN {$wpdb->order_items} oi ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} p ON p.ID = oi.order_id
        JOIN {$wpdb->postmeta} pm_end ON o.ID = pm_end.post_id
        JOIN {$wpdb->postmeta} pm_opco ON p.ID = pm_opco.post_id
        LEFT JOIN {$wpdb->postmeta} pm_dossier ON p.ID = pm_dossier.post_id AND pm_dossier.meta_key = 'fsbddtext_numdossier'
        WHERE o.post_type = 'action-de-formation'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_end.meta_key = 'we_enddate'
        AND pm_opco.meta_key = 'fsbdd_financeopco'
        AND pm_opco.meta_value = '2'
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) <= %s
        AND (pm_dossier.meta_value = '' OR pm_dossier.meta_value IS NULL)
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $end_date_formatted = date_i18n('d/m/Y', $result->end_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $end_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formateurs confirmés sans lettre de mission
 */
function fs_get_confirmed_trainers_without_mission_letter() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT DISTINCT f.post_title as formateur, a.ID as action_id, a.post_title as action_title, 
               pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_planning ON a.ID = pm_planning.post_id
        JOIN {$wpdb->posts} f ON f.ID = SUBSTRING_INDEX(SUBSTRING_INDEX(pm_planning.meta_value, 'fsbdd_user_formateurrel\";i:', -1), ';', 1)
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_etat ON a.ID = pm_etat.post_id
        LEFT JOIN {$wpdb->postmeta} pm_lm ON pm_lm.post_id = a.ID AND pm_lm.meta_key = 'fsbdd_lm_envoyee'
        WHERE a.post_type = 'action-de-formation'
        AND pm_planning.meta_key = 'fsbdd_planning'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_etat.meta_key = 'fsbdd_okformatr'
        AND pm_etat.meta_value = 'Réservé'
        AND (pm_lm.meta_value IS NULL OR pm_lm.meta_value != '1')
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->action_id,
            'title' => $result->formateur . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->action_id)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formateurs optionnés non confirmés
 */
function fs_get_unconfirmed_option_trainers($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("+$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT f.post_title as formateur, a.ID as action_id, a.post_title as action_title, 
               pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_planning ON a.ID = pm_planning.post_id
        JOIN {$wpdb->posts} f ON f.ID = SUBSTRING_INDEX(SUBSTRING_INDEX(pm_planning.meta_value, 'fsbdd_user_formateurrel\";i:', -1), ';', 1)
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_etat ON a.ID = pm_etat.post_id
        WHERE a.post_type = 'action-de-formation'
        AND pm_planning.meta_key = 'fsbdd_planning'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_etat.meta_key = 'fsbdd_okformatr'
        AND pm_etat.meta_value = 'Option'
        AND CAST(FROM_UNIXTIME(pm_date.meta_value) AS DATE) <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->action_id,
            'title' => $result->formateur . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->action_id)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formateurs prébloqués non confirmés
 */
function fs_get_unconfirmed_preblocked_trainers($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("+$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT f.post_title as formateur, a.ID as action_id, a.post_title as action_title, 
               pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_planning ON a.ID = pm_planning.post_id
        JOIN {$wpdb->posts} f ON f.ID = SUBSTRING_INDEX(SUBSTRING_INDEX(pm_planning.meta_value, 'fsbdd_user_formateurrel\";i:', -1), ';', 1)
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_etat ON a.ID = pm_etat.post_id
        WHERE a.post_type = 'action-de-formation'
        AND pm_planning.meta_key = 'fsbdd_planning'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_etat.meta_key = 'fsbdd_okformatr'
        AND pm_etat.meta_value = 'Pré bloqué FS'
        AND CAST(FROM_UNIXTIME(pm_date.meta_value) AS DATE) <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->action_id,
            'title' => $result->formateur . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->action_id)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formations confirmées sans formateur au planning
 */
function fs_get_confirmed_formations_without_trainer() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT a.ID, a.post_title, pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON a.ID = pm_confirm.post_id
        LEFT JOIN {$wpdb->postmeta} pm_planning ON a.ID = pm_planning.post_id AND pm_planning.meta_key = 'fsbdd_planning'
        WHERE a.post_type = 'action-de-formation'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value = '3'
        AND (
            pm_planning.meta_value IS NULL
            OR pm_planning.meta_value NOT LIKE '%fsbdd_user_formateurrel%'
        )
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les Inter-entreprises non confirmés proches
 */
function fs_get_unconfirmed_inter_formations($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("+$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT a.ID, a.post_title, pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_type ON a.ID = pm_type.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON a.ID = pm_confirm.post_id
        WHERE a.post_type = 'action-de-formation'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_type.meta_key = 'fsbdd_typesession'
        AND pm_type.meta_value IN ('1', '2')
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value != '3'
        AND CAST(FROM_UNIXTIME(pm_date.meta_value) AS DATE) <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les Inter-entreprises avec ressources manquantes
 */
function fs_get_inter_formations_missing_resources() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT a.ID, a.post_title, pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_type ON a.ID = pm_type.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON a.ID = pm_confirm.post_id
        LEFT JOIN {$wpdb->postmeta} pm_salle ON a.ID = pm_salle.post_id AND pm_salle.meta_key = 'fsbdd_salleconfirm'
        LEFT JOIN {$wpdb->postmeta} pm_materiel ON a.ID = pm_materiel.post_id AND pm_materiel.meta_key = 'fsbdd_materielconfirm'
        LEFT JOIN {$wpdb->postmeta} pm_formateur ON a.ID = pm_formateur.post_id AND pm_formateur.meta_key = 'fsbdd_formateurconfirm'
        LEFT JOIN {$wpdb->postmeta} pm_prestataire ON a.ID = pm_prestataire.post_id AND pm_prestataire.meta_key = 'fsbdd_prestataireconfirm'
        WHERE a.post_type = 'action-de-formation'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_type.meta_key = 'fsbdd_typesession'
        AND pm_type.meta_value IN ('1', '2')
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value = '3'
        AND (
            pm_salle.meta_value != '1' OR
            pm_materiel.meta_value != '1' OR
            pm_formateur.meta_value != '1' OR
            pm_prestataire.meta_value != '1' OR
            pm_salle.meta_value IS NULL OR
            pm_materiel.meta_value IS NULL OR
            pm_formateur.meta_value IS NULL OR
            pm_prestataire.meta_value IS NULL
        )
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les options posées non confirmées
 */
function fs_get_unconfirmed_options($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("-$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT a.ID, a.post_title, pm_date.meta_value as option_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_option ON a.ID = pm_option.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON a.ID = pm_confirm.post_id
        WHERE a.post_type = 'action-de-formation'
        AND pm_date.meta_key = 'fsbdd_option_date'
        AND pm_option.meta_key = 'fsbdd_option_posee'
        AND pm_option.meta_value = '1'
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value != '3'
        AND CAST(FROM_UNIXTIME(pm_date.meta_value) AS DATE) <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $option_date_formatted = date_i18n('d/m/Y', $result->option_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $option_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formations sans noms de stagiaires
 */
function fs_get_formations_without_trainees($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("+$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT a.ID, a.post_title, pm_date.meta_value as start_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_date ON a.ID = pm_date.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON a.ID = pm_confirm.post_id
        LEFT JOIN {$wpdb->postmeta} pm_trainees ON a.ID = pm_trainees.post_id AND pm_trainees.meta_key = 'fsbdd_gpeffectif'
        WHERE a.post_type = 'action-de-formation'
        AND pm_date.meta_key = 'we_startdate'
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value = '3'
        AND (pm_trainees.meta_value IS NULL OR pm_trainees.meta_value = '' OR pm_trainees.meta_value = 'a:0:{}')
        AND CAST(FROM_UNIXTIME(pm_date.meta_value) AS DATE) <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les OPCO - dossiers manquants pour formations à venir
 */
function fs_get_opco_missing_dossiers_upcoming_formations() {
    global $wpdb;
    
    $current_date = date('Y-m-d');
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT o.ID, o.post_title, pm_start.meta_value as start_date
        FROM {$wpdb->posts} o
        JOIN {$wpdb->order_itemmeta} oim ON oim.meta_value = o.ID
        JOIN {$wpdb->order_items} oi ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} p ON p.ID = oi.order_id
        JOIN {$wpdb->postmeta} pm_start ON o.ID = pm_start.post_id
        JOIN {$wpdb->postmeta} pm_opco ON p.ID = pm_opco.post_id
        LEFT JOIN {$wpdb->postmeta} pm_dossier ON p.ID = pm_dossier.post_id AND pm_dossier.meta_key = 'fsbddtext_numdossier'
        WHERE o.post_type = 'action-de-formation'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_start.meta_key = 'we_startdate'
        AND pm_opco.meta_key = 'fsbdd_financeopco'
        AND pm_opco.meta_value = '2'
        AND CAST(FROM_UNIXTIME(pm_start.meta_value) AS DATE) > %s
        AND (pm_dossier.meta_value = '' OR pm_dossier.meta_value IS NULL)
    ", $current_date));
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les formations terminées sans suivi
 */
function fs_get_completed_formations_without_followup($days) {
    global $wpdb;
    
    $min_days_cutoff = date('Y-m-d', strtotime("-$days days"));
    $max_days_cutoff = ($days == 7) ? date('Y-m-d', strtotime("-15 days")) : 
                       (($days == 15) ? date('Y-m-d', strtotime("-30 days")) : '1970-01-01');
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT a.ID, a.post_title, pm_end.meta_value as end_date
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_end ON a.ID = pm_end.post_id
        JOIN {$wpdb->order_itemmeta} oim ON oim.meta_value = a.ID
        JOIN {$wpdb->order_items} oi ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} p ON p.ID = oi.order_id
        LEFT JOIN {$wpdb->postmeta} pm_suivi_etat ON p.ID = pm_suivi_etat.post_id AND pm_suivi_etat.meta_key = 'fsbdd_etatsuivi'
        WHERE a.post_type = 'action-de-formation'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_end.meta_key = 'we_enddate'
        AND (pm_suivi_etat.meta_value != 'réalisé' OR pm_suivi_etat.meta_value IS NULL)
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) <= %s
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) > %s
    ", $min_days_cutoff, $max_days_cutoff));
    
    $items = array();
    foreach ($results as $result) {
        $end_date_formatted = date_i18n('d/m/Y', $result->end_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $end_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les conventions signées non reçues
 */
function fs_get_signed_conventions_not_received() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT p.ID, p.post_title, o.ID as action_id, o.post_title as action_title,
               pm_start.meta_value as start_date
        FROM {$wpdb->posts} p
        JOIN {$wpdb->order_items} oi ON p.ID = oi.order_id
        JOIN {$wpdb->order_itemmeta} oim ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} o ON o.ID = oim.meta_value
        JOIN {$wpdb->postmeta} pm_start ON o.ID = pm_start.post_id
        JOIN {$wpdb->postmeta} pm_conv_sent ON p.ID = pm_conv_sent.post_id
        JOIN {$wpdb->postmeta} pm_conv_sent_date ON p.ID = pm_conv_sent_date.post_id
        LEFT JOIN {$wpdb->postmeta} pm_conv_received ON p.ID = pm_conv_received.post_id AND pm_conv_received.meta_key = 'fsbdd_convention_recue_le'
        WHERE p.post_type = 'shop_order'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_start.meta_key = 'we_startdate'
        AND pm_conv_sent.meta_key = 'fsbdd_convention_envoyee'
        AND pm_conv_sent.meta_value = '1'
        AND pm_conv_sent_date.meta_key = 'fsbdd_convention_transmise_le'
        AND pm_conv_sent_date.meta_value != ''
        AND (pm_conv_received.meta_value = '' OR pm_conv_received.meta_value IS NULL)
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les conventions non envoyées
 */
function fs_get_conventions_not_sent() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT p.ID, p.post_title, o.ID as action_id, o.post_title as action_title,
               pm_start.meta_value as start_date
        FROM {$wpdb->posts} p
        JOIN {$wpdb->order_items} oi ON p.ID = oi.order_id
        JOIN {$wpdb->order_itemmeta} oim ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} o ON o.ID = oim.meta_value
        JOIN {$wpdb->postmeta} pm_start ON o.ID = pm_start.post_id
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        LEFT JOIN {$wpdb->postmeta} pm_conv_transmise ON p.ID = pm_conv_transmise.post_id AND pm_conv_transmise.meta_key = 'fsbdd_convention_transmise_le'
        WHERE p.post_type = 'shop_order'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_start.meta_key = 'we_startdate'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value = 'wc-confirme'
        AND (pm_conv_transmise.meta_value = '' OR pm_conv_transmise.meta_value IS NULL)
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les convocations en attente
 */
function fs_get_pending_convocations() {
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT p.ID, p.post_title, o.ID as action_id, o.post_title as action_title,
               pm_start.meta_value as start_date
        FROM {$wpdb->posts} p
        JOIN {$wpdb->order_items} oi ON p.ID = oi.order_id
        JOIN {$wpdb->order_itemmeta} oim ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} o ON o.ID = oim.meta_value
        JOIN {$wpdb->postmeta} pm_start ON o.ID = pm_start.post_id
        JOIN {$wpdb->postmeta} pm_confirm ON o.ID = pm_confirm.post_id
        JOIN {$wpdb->postmeta} pm_conv_transmise ON p.ID = pm_conv_transmise.post_id
        JOIN {$wpdb->postmeta} pm_conv_recue ON p.ID = pm_conv_recue.post_id
        LEFT JOIN {$wpdb->postmeta} pm_convoc_envoyee ON p.ID = pm_convoc_envoyee.post_id AND pm_convoc_envoyee.meta_key = 'fsbdd_convocation_envoyee_le'
        WHERE p.post_type = 'shop_order'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_start.meta_key = 'we_startdate'
        AND pm_confirm.meta_key = 'fsbdd_sessconfirm'
        AND pm_confirm.meta_value = '3'
        AND pm_conv_transmise.meta_key = 'fsbdd_convention_transmise_le'
        AND pm_conv_transmise.meta_value != ''
        AND pm_conv_recue.meta_key = 'fsbdd_convention_recue_le'
        AND pm_conv_recue.meta_value != ''
        AND (pm_convoc_envoyee.meta_value = '' OR pm_convoc_envoyee.meta_value IS NULL)
    ");
    
    $items = array();
    foreach ($results as $result) {
        $start_date_formatted = date_i18n('d/m/Y', $result->start_date);
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title . ' - ' . $result->action_title,
            'date' => $start_date_formatted,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les factures non réglées
 */
function fs_get_unpaid_invoices($days) {
    global $wpdb;
    
    $min_days_cutoff = date('Y-m-d', strtotime("-$days days"));
    $max_days_cutoff = ($days == 30) ? date('Y-m-d', strtotime("-60 days")) : 
                      (($days == 60) ? date('Y-m-d', strtotime("-90 days")) : 
                      (($days == 90) ? date('Y-m-d', strtotime("-120 days")) : '1970-01-01'));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title, pm_invoice_date.meta_value as invoice_date,
               pm_total.meta_value as total, pm_last_reminder.meta_value as last_reminder
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_invoice_date ON p.ID = pm_invoice_date.post_id
        JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id
        LEFT JOIN {$wpdb->postmeta} pm_payment_date ON p.ID = pm_payment_date.post_id AND pm_payment_date.meta_key = 'fsbdd_payment_date'
        LEFT JOIN {$wpdb->postmeta} pm_last_reminder ON p.ID = pm_last_reminder.post_id AND pm_last_reminder.meta_key = 'fsbdd_derniere_relance'
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value IN ('wc-facturesent', 'wc-factureok')
        AND pm_invoice_date.meta_key = 'fsbdd_facturedate'
        AND pm_total.meta_key = '_order_total'
        AND (pm_payment_date.meta_value IS NULL OR pm_payment_date.meta_value = '')
        AND STR_TO_DATE(pm_invoice_date.meta_value, '%%d/%%m/%%Y') <= %s
        AND STR_TO_DATE(pm_invoice_date.meta_value, '%%d/%%m/%%Y') > %s
    ", $min_days_cutoff, $max_days_cutoff));
    
    $items = array();
    foreach ($results as $result) {
        $title = $result->post_title . ' - ' . number_format($result->total, 2, ',', ' ') . ' €';
        if (!empty($result->last_reminder)) {
            $title .= ' (Dernière relance: ' . $result->last_reminder . ')';
        }
        
        $items[] = array(
            'id' => $result->ID,
            'title' => $title,
            'date' => $result->invoice_date,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Récupérer les devis à relancer
 */
function fs_get_quotes_to_follow_up($days) {
    global $wpdb;
    
    $cutoff_date = date('Y-m-d', strtotime("-$days days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title, pm_quote_date.meta_value as quote_date
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_quote_date ON p.ID = pm_quote_date.post_id
        LEFT JOIN {$wpdb->postmeta} pm_quote_followup ON p.ID = pm_quote_followup.post_id AND pm_quote_followup.meta_key = 'fsbdd_devis_relance'
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value = 'wc-devisproposition'
        AND pm_quote_date.meta_key = 'fsbdd_devisdate'
        AND (pm_quote_followup.meta_value != '1' OR pm_quote_followup.meta_value IS NULL)
        AND STR_TO_DATE(pm_quote_date.meta_value, '%%d/%%m/%%Y') <= %s
    ", $cutoff_date));
    
    $items = array();
    foreach ($results as $result) {
        $items[] = array(
            'id' => $result->ID,
            'title' => $result->post_title,
            'date' => $result->quote_date,
            'edit_url' => get_edit_post_link($result->ID)
        );
    }
    
    return $items;
}

/**
 * Statistiques
 */
function fs_get_unpaid_invoices_count() {
    global $wpdb;
    
    return $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_invoice_date ON p.ID = pm_invoice_date.post_id
        LEFT JOIN {$wpdb->postmeta} pm_payment_date ON p.ID = pm_payment_date.post_id AND pm_payment_date.meta_key = 'fsbdd_payment_date'
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value IN ('wc-facturesent', 'wc-factureok')
        AND pm_invoice_date.meta_key = 'fsbdd_facturedate'
        AND pm_invoice_date.meta_value != ''
        AND (pm_payment_date.meta_value = '' OR pm_payment_date.meta_value IS NULL)
    ");
}

function fs_get_unpaid_invoices_total() {
    global $wpdb;
    
    $total = $wpdb->get_var("
        SELECT SUM(pm_total.meta_value)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_invoice_date ON p.ID = pm_invoice_date.post_id
        JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id
        LEFT JOIN {$wpdb->postmeta} pm_payment_date ON p.ID = pm_payment_date.post_id AND pm_payment_date.meta_key = 'fsbdd_payment_date'
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value IN ('wc-facturesent', 'wc-factureok')
        AND pm_invoice_date.meta_key = 'fsbdd_facturedate'
        AND pm_invoice_date.meta_value != ''
        AND pm_total.meta_key = '_order_total'
        AND (pm_payment_date.meta_value = '' OR pm_payment_date.meta_value IS NULL)
    ");
    
    return number_format($total, 2, ',', ' ');
}

function fs_get_paid_invoices_count() {
    global $wpdb;
    
    $first_day = date('Y-m-01');
    $last_day = date('Y-m-t');
    
    return $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_solde ON p.ID = pm_solde.post_id
        JOIN {$wpdb->postmeta} pm_payment_date ON p.ID = pm_payment_date.post_id
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value = 'wc-factureok'
        AND pm_solde.meta_key = 'fsbdd_solde'
        AND pm_solde.meta_value = 0
        AND pm_payment_date.meta_key = 'fsbdd_payment_date'
        AND STR_TO_DATE(pm_payment_date.meta_value, '%%d/%%m/%%Y') BETWEEN %s AND %s
    ", $first_day, $last_day));
}

function fs_get_paid_invoices_total() {
    global $wpdb;
    
    $first_day = date('Y-m-01');
    $last_day = date('Y-m-t');
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(pm_total.meta_value)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        JOIN {$wpdb->postmeta} pm_solde ON p.ID = pm_solde.post_id
        JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id
        JOIN {$wpdb->postmeta} pm_payment_date ON p.ID = pm_payment_date.post_id
        WHERE p.post_type = 'shop_order'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value = 'wc-factureok'
        AND pm_solde.meta_key = 'fsbdd_solde'
        AND pm_solde.meta_value = 0
        AND pm_total.meta_key = '_order_total'
        AND pm_payment_date.meta_key = 'fsbdd_payment_date'
        AND STR_TO_DATE(pm_payment_date.meta_value, '%%d/%%m/%%Y') BETWEEN %s AND %s
    ", $first_day, $last_day));
    
    return number_format($total, 2, ',', ' ');
}

function fs_get_pending_invoices_count() {
    global $wpdb;
    
    $current_date = date('Y-m-d');
    
    return $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts} a
        JOIN {$wpdb->postmeta} pm_end ON a.ID = pm_end.post_id
        JOIN {$wpdb->order_itemmeta} oim ON oim.meta_value = a.ID
        JOIN {$wpdb->order_items} oi ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->posts} p ON p.ID = oi.order_id
        JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
        LEFT JOIN {$wpdb->postmeta} pm_invoice_date ON p.ID = pm_invoice_date.post_id AND pm_invoice_date.meta_key = 'fsbdd_facturedate'
        WHERE a.post_type = 'action-de-formation'
        AND oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND pm_end.meta_key = 'we_enddate'
        AND pm_status.meta_key = '_order_status'
        AND pm_status.meta_value NOT IN ('wc-facturesent', 'wc-factureok')
        AND (pm_invoice_date.meta_value = '' OR pm_invoice_date.meta_value IS NULL)
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) <= %s
    ", $current_date));
}

function fs_get_trainers_to_pay_count() {
    global $wpdb;
    
    $current_date = date('Y-m-d');
    
    return $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT f.ID)
        FROM {$wpdb->posts} f
        JOIN {$wpdb->postmeta} pm_planning ON pm_planning.meta_value LIKE CONCAT('%%\"fsbdd_user_formateurrel\";i:', f.ID, ';%%')
        JOIN {$wpdb->posts} a ON a.ID = pm_planning.post_id
        JOIN {$wpdb->postmeta} pm_end ON a.ID = pm_end.post_id
        LEFT JOIN {$wpdb->postmeta} pm_payment ON f.ID = pm_payment.post_id AND pm_payment.meta_key = 'fsbdd_formateur_paid'
        WHERE f.post_type = 'formateur'
        AND a.post_type = 'action-de-formation'
        AND pm_planning.meta_key = 'fsbdd_planning'
        AND pm_end.meta_key = 'we_enddate'
        AND (pm_payment.meta_value != '1' OR pm_payment.meta_value IS NULL)
        AND CAST(FROM_UNIXTIME(pm_end.meta_value) AS DATE) <= %s
    ", $current_date));
}

function fs_get_trainers_to_pay_total() {
    global $wpdb;
    
    $current_month = date('Y-m');
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(pm_cost.meta_value)
        FROM {$wpdb->posts} f
        JOIN {$wpdb->postmeta} pm_planning ON pm_planning.meta_value LIKE CONCAT('%%\"fsbdd_user_formateurrel\";i:', f.ID, ';%%')
        JOIN {$wpdb->posts} a ON a.ID = pm_planning.post_id
        JOIN {$wpdb->postmeta} pm_end ON a.ID = pm_end.post_id
        JOIN {$wpdb->postmeta} pm_cost ON f.ID = pm_cost.post_id
        LEFT JOIN {$wpdb->postmeta} pm_payment ON f.ID = pm_payment.post_id AND pm_payment.meta_key = 'fsbdd_formateur_paid'
        WHERE f.post_type = 'formateur'
        AND a.post_type = 'action-de-formation'
        AND pm_planning.meta_key = 'fsbdd_planning'
        AND pm_end.meta_key = 'we_enddate'
        AND pm_cost.meta_key = 'fsform_number_coutjour'
        AND (pm_payment.meta_value != '1' OR pm_payment.meta_value IS NULL)
        AND DATE_FORMAT(FROM_UNIXTIME(pm_end.meta_value), '%%Y-%%m') = %s
    ", $current_month));
    
    return number_format($total, 2, ',', ' ');
}


// Puis ajoutez ces fonctions à la fin du fichier pour servir CSS et JS via AJAX :
function fs_serve_dashboard_css() {
    header('Content-Type: text/css');
    echo fs_get_dashboard_css();
    exit;
}
add_action('wp_ajax_fs_dashboard_css', 'fs_serve_dashboard_css');
add_action('wp_ajax_nopriv_fs_dashboard_css', 'fs_serve_dashboard_css');

function fs_serve_dashboard_js() {
    header('Content-Type: application/javascript');
    echo fs_get_dashboard_js();
    exit;
}
add_action('wp_ajax_fs_dashboard_js', 'fs_serve_dashboard_js');
add_action('wp_ajax_nopriv_fs_dashboard_js', 'fs_serve_dashboard_js');
