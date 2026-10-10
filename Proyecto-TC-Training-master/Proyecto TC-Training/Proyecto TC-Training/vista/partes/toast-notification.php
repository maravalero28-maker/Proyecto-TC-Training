<?php
if (!empty($mensaje)): 
    $toastText = '';
    $toastType = 'info';

    if (is_array($mensaje)) {
        $toastText = $mensaje['text'] ?? '';
        $toastType = $mensaje['type'] ?? 'info';
    } else {
        $toastText = trim(strip_tags($mensaje));
        if (strpos($mensaje, 'alert-success') !== false) {
            $toastType = 'success';
        } elseif (strpos($mensaje, 'alert-danger') !== false) {
            $toastType = 'error';
        } elseif (strpos($mensaje, 'alert-warning') !== false) {
            $toastType = 'warning';
        }
    }
?>
<section id="toast-container" aria-live="polite" aria-atomic="true"></section>
<script>
    document.body.dataset.serverMessage = <?php echo json_encode($toastText); ?>;
    document.body.dataset.serverMessageType = <?php echo json_encode($toastType); ?>;
</script>
<?php endif; ?>
