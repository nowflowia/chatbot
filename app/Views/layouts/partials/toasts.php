<?php
$flashSuccess = \Core\Session::getFlash('success');
$flashError   = \Core\Session::getFlash('error');
$flashInfo    = \Core\Session::getFlash('info');
?>
<div id="toast-container"></div>

<?php if ($flashSuccess): ?>
<script>document.addEventListener('DOMContentLoaded',function(){ Toast.show('<?= addslashes($flashSuccess) ?>', 'success'); });</script>
<?php endif; ?>
<?php if ($flashError): ?>
<script>document.addEventListener('DOMContentLoaded',function(){ Toast.show('<?= addslashes($flashError) ?>', 'error'); });</script>
<?php endif; ?>
<?php if ($flashInfo): ?>
<script>document.addEventListener('DOMContentLoaded',function(){ Toast.show('<?= addslashes($flashInfo) ?>', 'info'); });</script>
<?php endif; ?>
