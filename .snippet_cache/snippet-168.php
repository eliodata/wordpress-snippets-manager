<?php
/**
 * Snippet ID: 168
 * Name: CSS Tableau de bord des alertes page admin
 * Description: 
 * @active false
 */


function fs_get_dashboard_css() {
    ob_start();
?>

/**
 * Styles pour le Tableau de Bord des Formations
 */

.fs-dashboard-wrap {
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Résumé en haut */
.fs-dashboard-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.fs-stat-card {
    background-color: white;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
    padding: 15px;
    display: flex;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.fs-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.24);
}

.fs-stat-icon {
    background-color: #f9f9f9;
    padding: 12px;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}

.fs-stat-icon .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fs-stat-content {
    flex-grow: 1;
}

.fs-stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #23282d;
}

.fs-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #1e1e1e;
    display: block;
    line-height: 1.2;
}

.fs-stat-label {
    color: #646970;
    font-size: 12px;
}

/* Onglets */
.fs-dashboard-tabs {
    background-color: white;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
    overflow: hidden;
}

.fs-tab-nav {
    display: flex;
    background-color: #f0f0f1;
    border-bottom: 1px solid #c3c4c7;
    overflow-x: auto;
    white-space: nowrap;
}

.fs-tab-button {
    padding: 10px 20px;
    background: none;
    border: none;
    border-right: 1px solid #c3c4c7;
    cursor: pointer;
    font-size: 14px;
    color: #50575e;
    font-weight: 500;
    transition: all 0.2s ease;
}

.fs-tab-button:hover {
    background-color: #e0e0e0;
}

.fs-tab-button.active {
    color: #2271b1;
    background-color: white;
    border-bottom: 2px solid #2271b1;
    position: relative;
}

.fs-tab-content {
    display: none;
    padding: 20px;
}

.fs-tab-content.active {
    display: block;
}

/* Grille d'alertes */
.fs-alert-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.fs-alert-card {
    background-color: white;
    border-left: 4px solid #72aee6;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    padding: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
}

.fs-alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.12);
}

.fs-alert-card.fs-severity-red {
    border-left-color: #d63638;
}

.fs-alert-card.fs-severity-orange {
    border-left-color: #dba617;
}

.fs-alert-card.fs-severity-green {
    border-left-color: #00a32a;
}

.fs-alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.fs-alert-header h3 {
    margin: 0;
    font-size: 15px;
    color: #1d2327;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.fs-alert-header h3 .dashicons {
    margin-right: 8px;
    color: #646970;
}

.fs-severity-red .fs-alert-header h3 .dashicons {
    color: #d63638;
}

.fs-severity-orange .fs-alert-header h3 .dashicons {
    color: #dba617;
}

.fs-severity-green .fs-alert-header h3 .dashicons {
    color: #00a32a;
}

.fs-alert-count {
    background-color: #f0f0f1;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    color: #50575e;
}

.fs-severity-red .fs-alert-count {
    background-color: rgba(214, 54, 56, 0.1);
    color: #d63638;
}

.fs-severity-orange .fs-alert-count {
    background-color: rgba(219, 166, 23, 0.1);
    color: #dba617;
}

.fs-severity-green .fs-alert-count {
    background-color: rgba(0, 163, 42, 0.1);
    color: #00a32a;
}

.fs-alert-description {
    font-size: 12px;
    color: #646970;
    margin-bottom: 12px;
}

.fs-alert-items {
    margin-top: auto;
    max-height: 200px;
    overflow-y: auto;
}

.fs-alert-items ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.fs-alert-items li {
    margin-bottom: 8px;
    line-height: 1.4;
}

.fs-alert-items li:last-child {
    margin-bottom: 0;
}

.fs-alert-items a {
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: #2271b1;
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 4px;
    background-color: #f6f7f7;
    transition: background-color 0.2s ease;
}

.fs-alert-items a:hover {
    background-color: #f0f0f1;
    color: #135e96;
}

.fs-item-date {
    font-size: 11px;
    color: #50575e;
    margin-top: 2px;
}

.fs-alert-more {
    text-align: center;
    margin-top: 10px;
}

.fs-show-all {
    background: none;
    border: none;
    color: #2271b1;
    cursor: pointer;
    font-size: 12px;
    padding: 0;
    text-decoration: underline;
}

.fs-show-all:hover {
    color: #135e96;
}

.fs-alert-empty {
    padding: 20px;
    text-align: center;
    color: #646970;
    font-size: 14px;
    background-color: #f6f7f7;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 782px) {
    .fs-dashboard-summary {
        grid-template-columns: 1fr;
    }
    
    .fs-alert-grid {
        grid-template-columns: 1fr;
    }
    
    .fs-tab-button {
        padding: 10px 15px;
        font-size: 13px;
    }
}

<?php
    return ob_get_clean();
}
