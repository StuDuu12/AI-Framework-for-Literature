<!-- Vendor JS Files -->
<script src="<?= URL_ROOT ?>/assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/chart.js/chart.umd.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/echarts/echarts.min.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/quill/quill.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/tinymce/tinymce.min.js"></script>
<script src="<?= URL_ROOT ?>/assets/vendor/php-email-form/validate.js"></script>

<!-- GSAP Animation Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<!-- Template Main JS File -->
<script src="<?= URL_ROOT ?>/assets/js/main.js"></script>

<!-- Toast Notification System -->
<script src="<?= URL_ROOT ?>/assets/js/toast.js"></script>
<script src="<?= URL_ROOT ?>/assets/js/global-search.js"></script>

<!-- Language Switcher (before notifications — uses translate()) -->
<?php $translationsVer = @filemtime(PATH_ROOT . '/assets/js/translations.js') ?: time(); ?>
<?php $langSwitcherVer = @filemtime(PATH_ROOT . '/assets/js/language-switcher.js') ?: time(); ?>
<script src="<?= URL_ROOT ?>/assets/js/translations.js?v=<?= $translationsVer ?>"></script>
<script src="<?= URL_ROOT ?>/assets/js/language-switcher.js?v=<?= $langSwitcherVer ?>"></script>
<?php $langHelperVer = @filemtime(PATH_ROOT . '/assets/js/language-helper.js') ?: time(); ?>
<script src="<?= URL_ROOT ?>/assets/js/language-helper.js?v=<?= $langHelperVer ?>"></script>
<script src="<?= URL_ROOT ?>/assets/js/notifications.js"></script>

<!-- Animation Controller (must load after GSAP) -->
<?php $animationsJsVer = @filemtime(PATH_ROOT . '/assets/js/animations.js') ?: time(); ?>
<script src="<?= URL_ROOT ?>/assets/js/animations.js?v=<?= $animationsJsVer ?>"></script>