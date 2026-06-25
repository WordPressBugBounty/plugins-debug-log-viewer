"use strict";
(self["webpackChunkdebug_log_viewer"] = self["webpackChunkdebug_log_viewer"] || []).push([["front_assets_js_components_welcome-screen_js"],{

/***/ "./front/assets/js/components/welcome-screen.js"
/*!******************************************************!*\
  !*** ./front/assets/js/components/welcome-screen.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _log_view_api_service_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./log-view/api-service.js */ "./front/assets/js/components/log-view/api-service.js");
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* global $, document, location */


$(document).ready(function () {
  // Initialize API service
  const apiService = new _log_view_api_service_js__WEBPACK_IMPORTED_MODULE_0__.LogViewerApiService();

  // Handle start debugging button hover effects
  $('#first_run_enable_logging').on('mouseenter', function () {
    $(this).addClass('btn-hover-effect');
  }).on('mouseleave', function () {
    $(this).removeClass('btn-hover-effect');
  });

  // Handle start debugging button click with loading state
  $('#first_run_enable_logging').on('click', async function () {
    const $btn = $(this);
    const originalText = $btn.html();

    // Set loading state
    $btn.prop('disabled', true);
    $btn.html('<i class="fas fa-spinner fa-spin"></i> Enabling Debug Logging...');
    $btn.removeClass('btn-hover-effect');
    try {
      await apiService.enableLogging();

      // Show success state in button
      $btn.html('<i class="fas fa-check-circle"></i> Debug Logging Enabled!');
      $btn.removeClass('btn-primary').addClass('btn-success');

      // Reload page after short delay to show success message
      setTimeout(() => location.reload(), 100);
    } catch (error) {
      // Show error state in button
      $btn.html('<i class="fas fa-exclamation-triangle"></i> Failed to Enable');
      $btn.removeClass('btn-primary').addClass('btn-danger');

      // Reset button after showing error
      setTimeout(() => {
        $btn.prop('disabled', false);
        $btn.html(originalText);
        $btn.removeClass('btn-danger').addClass('btn-primary');
      }, 2000);
    }
  });

  // Handle manual instructions toggle with simple show/hide
  $('.manual-debugging-instructions').on('click', function (e) {
    e.preventDefault();
    const target = $(this).attr('href');
    $(target).toggle();

    // Toggle chevron icon
    const icon = $(this).find('.toggle-icon');
    icon.toggleClass('fa-chevron-down fa-chevron-up');
  });
});

/***/ }

}]);