<?php
/**
 * Component: Admin User Table
 * Expected Variables: $users (array)
 */
?>
<div class="table-container">
    <table class="aksanti-table">
        <thead>
            <tr>
                <th>Utilizador</th>
                <th>E-mail</th>
                <th>Tipo</th>
                <th>Adesão</th>
                <th style="text-align: right;">Gestão</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="position: relative;">
                            <img src="<?= $u['profile_pic'] && $u['profile_pic'] !== 'default_profile.png' ? $base_url . $u['profile_pic'] : $base_url . 'recursos/images/default_profile.png' ?>" style="width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 1px solid var(--surface-10);">
                            <?php if (isset($u['is_verified']) && $u['is_verified']): ?>
                                <div style="position: absolute; bottom: -4px; right: -4px; width: 16px; height: 16px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #050a15;">
                                    <i class="fas fa-check" style="font-size: 8px; color: white;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div style="font-size: 0.7rem; color: var(--surface-30); text-transform: uppercase; font-weight: 800;">ID: #<?php echo $u['user_id']; ?></div>
                        </div>
                    </div>
                </td>
                <td style="color: var(--surface-60); font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <span class="user-badge-premium badge-<?= $u['user_type'] ?>">
                        <?php echo $u['user_type']; ?>
                    </span>
                </td>
                <td style="font-size: 0.85rem; color: var(--surface-50);">
                    <?php echo date('d M, Y', strtotime($u['created_at'])); ?>
                </td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <button onclick='editUser(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, "UTF-8"); ?>)' class="btn-action" title="Editar"><i class="fas fa-pen-nib"></i></button>
                        
                        <?php if ($u['user_type'] === 'admin'): ?>
                            <button onclick="managePermissions(<?php echo $u['user_id']; ?>, '<?php echo addslashes($u['full_name']); ?>')" class="btn-action info" title="Permissões"><i class="fas fa-key-skeleton"></i></button>
                        <?php endif; ?>

                        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $u['user_id']; ?>)" class="btn-action danger" title="Eliminar">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.user-badge-premium { 
    padding: 0.35rem 0.75rem; border-radius: 8px; font-size: 0.65rem; font-weight: 800; 
    text-transform: uppercase; letter-spacing: 0.5px;
    background: var(--surface-5);
    border: 1px solid var(--surface-8);
}
.badge-admin { color: #f7941d; border-color: rgba(247, 148, 29, 0.2); background: rgba(247, 148, 29, 0.05); }
.badge-investor { color: #60a5fa; border-color: rgba(96, 165, 250, 0.2); background: rgba(96, 165, 250, 0.05); }
.badge-mentor { color: #34d399; border-color: rgba(52, 211, 153, 0.2); background: rgba(52, 211, 153, 0.05); }

.btn-action {
    width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--surface-8);
    display: flex; align-items: center; justify-content: center; background: var(--surface-3);
    color: var(--surface-50); cursor: pointer; transition: all 0.3s;
}
.btn-action:hover { background: #f7941d; color: #000; border-color: #f7941d; transform: scale(1.05); }
.btn-action.info { color: #60a5fa; }
.btn-action.danger { color: #f43f5e; }
.btn-action.danger:hover { background: #f43f5e; color: #fff; border-color: #f43f5e; }
</style>

