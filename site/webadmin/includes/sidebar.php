<?php
require_once __DIR__ . '/icons.php';
$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active($file, $current) { return $file === $current ? ' active' : ''; }
?>
<aside class="a-sidebar">
  <div class="brand"><img src="../assets/img/logo.jpg" alt=""><span>KMS Admin</span></div>
  <nav class="a-nav">
    <a href="contacts.php" class="<?php echo trim(nav_active('contacts.php', $current)); ?>"><?php echo admin_icon('mail'); ?><span>Správy</span></a>
    <a href="announcements.php" class="<?php echo trim(nav_active('announcements.php', $current)); ?>"><?php echo admin_icon('bell'); ?><span>Oznamy</span></a>
    <a href="events.php" class="<?php echo trim(nav_active('events.php', $current)); ?>"><?php echo admin_icon('calendar'); ?><span>Kalendár</span></a>
    <a href="editor.php" class="<?php echo trim(nav_active('editor.php', $current)); ?>"><?php echo admin_icon('layout'); ?><span>Editor stránok</span></a>
    <a href="settings.php" class="<?php echo trim(nav_active('settings.php', $current)); ?>"><?php echo admin_icon('settings'); ?><span>Nastavenia</span></a>
  </nav>
  <div class="a-sidebar-footer">
    <div class="user">Prihlásený: <strong><?php echo htmlspecialchars($ADMIN_USERNAME ?? 'admin'); ?></strong></div>
    <button class="a-logout-btn" data-admin-logout data-testid="admin-logout-btn"><?php echo admin_icon('logout', 16); ?> Odhlásiť sa</button>
    <a class="a-view-site" href="../index.html" target="_blank"><?php echo admin_icon('external', 14); ?> Zobraziť stránku</a>
  </div>
</aside>
