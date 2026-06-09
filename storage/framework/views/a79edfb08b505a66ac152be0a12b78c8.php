<?php
    $badgeClass = method_exists($value, 'badgeClass') ? $value->badgeClass() : 'bg-secondary-subtle text-secondary';
    $label = method_exists($value, 'label') ? $value->label() : (is_object($value) ? $value->value : str($value)->replace('_', ' ')->title());
?>

<span class="badge <?php echo e($badgeClass); ?>"><?php echo e($label); ?></span>
<?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/components/status-badge.blade.php ENDPATH**/ ?>