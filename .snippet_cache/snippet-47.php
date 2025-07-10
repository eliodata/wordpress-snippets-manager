<?php
/**
 * Snippet ID: 47
 * Name: charges-filter.js
 * Description: 
 * @active false
 */

document.addEventListener("DOMContentLoaded", function () {
  const moisCheckboxes = document.querySelectorAll('input[name="filtre-mois"]');
  const anneeCheckboxes = document.querySelectorAll('input[name="filtre-annee"]');
  const rows = document.querySelectorAll("#charges-table tbody tr");

  function filterRows() {
    const selectedMonths = Array.from(moisCheckboxes)
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => checkbox.value);
    const selectedYears = Array.from(anneeCheckboxes)
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => checkbox.value);

    rows.forEach((row) => {
      const rowMonth = row.getAttribute("data-month");
      const rowYear = row.getAttribute("data-year");

      if (
        (selectedMonths.length === 0 || selectedMonths.includes(rowMonth)) &&
        (selectedYears.length === 0 || selectedYears.includes(rowYear))
      ) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  }

  moisCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", filterRows);
  });
  anneeCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", filterRows);
  });

  // Initialiser le filtrage au chargement
  filterRows();
});
