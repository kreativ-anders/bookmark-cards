<?php
/**
 * Form feedback for login, register and user pages
 * @var array|string|null $alert   error message(s), e.g. from invalid()
 * @var string|null       $success success message
 */
$errors = array_filter(is_array($alert ?? null) ? $alert : [$alert ?? null]);
?>
<?php if ($errors): ?>
<div class="alert alert-error" role="alert">
  <?php foreach ($errors as $message): ?>
  <p><?= esc($message) ?></p>
  <?php endforeach ?>
</div>
<?php endif ?>
<?php if (!empty($success)): ?>
<div class="alert alert-success" role="status">
  <p><?= esc($success) ?></p>
</div>
<?php endif ?>
