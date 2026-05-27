<?php
/**
 * Component: Announcements
 * Displays active global announcements (alerts, successes, info).
 */
$announcements = $db->query("SELECT * FROM announcements WHERE is_active = true ORDER BY created_at DESC")->fetchAll();
foreach($announcements as $an):
    $bg = $an['type'] == 'alert' ? '#ef4444' : ($an['type'] == 'success' ? '#10b981' : '#3b82f6');
?>
<div style="background: <?php echo $bg; ?>; color: white; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <i class="fas fa-bullhorn" style="font-size: 1.2rem;"></i>
    <span style="font-weight: 500; font-size: 0.95rem;"><?php echo htmlspecialchars($an['message']); ?></span>
</div>
<?php endforeach; ?>
