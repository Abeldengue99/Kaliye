<script>
/**
 * Admin User Management Scripts
 * Expected Variable: $institutions (JSON encoded)
 */
const AVAILABLE_INSTITUTIONS = [];

async function editUser(user) {
    const { value: formValues } = await Swal.fire({
        title: 'Editar Utilizador',
        html: `
            <div style="text-align: left;">
                <label style="font-size: 0.8rem; color: #94a3b8;">Tipo de Perfil:</label>
                <select id="swal-type" class="swal2-input" style="width: 100%; margin: 0.5rem 0 1rem 0;">
                    <option value="univ_student" ${user.user_type === 'univ_student' ? 'selected' : ''}>Estudante UniversitÃ¡rio</option>
                    <option value="high_student" ${user.user_type === 'high_student' ? 'selected' : ''}>Estudante do Ensino MÃ©dio</option>
                    <option value="mentor" ${user.user_type === 'mentor' ? 'selected' : ''}>Mentor</option>
                    <option value="investor" ${user.user_type === 'investor' ? 'selected' : ''}>Investidor</option>
                    <option value="admin" ${user.user_type === 'admin' ? 'selected' : ''}>Administrador</option>
                </select>
                
                <label style="font-size: 0.8rem; color: #94a3b8;">InstituiÃ§Ã£o (Texto):</label>
                <input id="swal-institution" class="swal2-input" value="${user.institution || ''}" placeholder="Ex: Universidade Agostinho Neto" style="width: 100%; margin: 0.5rem 0 1rem 0;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar AlteraÃ§Ãµes',
        preConfirm: () => {
            return {
                user_id: user.user_id,
                user_type: document.getElementById('swal-type').value,
                institution: document.getElementById('swal-institution').value
            }
        }
    });

    if (formValues) saveUserChanges(formValues);
}

function saveUserChanges(data) {
    const formData = new FormData();
    Object.keys(data).forEach(key => formData.append(key, data[key]));

    fetch('../../interface_programacao/admin/admin_update_user_role.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(result => {
        if(result.success) Swal.fire('Sucesso!', 'Perfil atualizado.', 'success').then(() => location.reload());
        else Swal.fire('Erro!', result.message, 'error');
    });
}

async function inviteAdmin() {
    // Permissions list same as before
    const permissions = [
        { slug: 'dashboard', label: 'Dashboard' },
        { slug: 'users', label: 'GestÃ£o de Utilizadores' },
        { slug: 'ads', label: 'Publicidade' },
        { slug: 'moderation', label: 'ModeraÃ§Ã£o' },
        { slug: 'support', label: 'Suporte' },
        { slug: 'kyc', label: 'KYC' },
        { slug: 'mentor_approval', label: 'Acolhimento Mentores' },
        { slug: 'finances', label: 'FinanÃ§as' },
        { slug: 'settings', label: 'Definicoes' }
    ];

    let permsHtml = `<div style="text-align: left; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 15px;">`;
    permissions.forEach(p => {
        permsHtml += `
            <div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                <input type="checkbox" id="invite_perm_${p.slug}" class="invite-perm-checkbox" value="${p.slug}">
                <label for="invite_perm_${p.slug}" style="font-size: 0.8rem; cursor: pointer;">${p.label}</label>
            </div>
        `;
    });
    permsHtml += `</div>`;

    const { value: formValues } = await Swal.fire({
        title: 'Convidar Administrador',
        html: `
            <div style="text-align: left;">
                <input id="invite_name" class="swal2-input" placeholder="Nome Completo">
                <input id="invite_email" type="email" class="swal2-input" placeholder="Email">
                <p style="margin-top: 15px; font-weight: bold; color: var(--accent-orange);">PermissÃµes:</p>
                ${permsHtml}
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Gerar Acesso',
        preConfirm: () => {
            const name = document.getElementById('invite_name').value;
            const email = document.getElementById('invite_email').value;
            const perms = Array.from(document.querySelectorAll('.invite-perm-checkbox:checked')).map(cb => cb.value);
            if (!name || !email) { Swal.showValidationMessage('Preencha os campos obrigatÃ³rios'); return false; }
            return { name, email, perms };
        }
    });

    if (formValues) {
        fetch('../../interface_programacao/admin/admin_invite_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                full_name: formValues.name,
                email: formValues.email,
                permissions: formValues.perms
            })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Acesso Gerado!',
                    html: `<p>Email: ${result.credentials.email}</p><p>Senha: <strong>${result.credentials.password}</strong></p>`
                }).then(() => location.reload());
            } else {
                Swal.fire('Erro', result.message, 'error');
            }
        });
    }
}

async function managePermissions(userId, userName) {
    try {
        const response = await fetch(`../../interface_programacao/admin/admin_get_user_permissions.php?user_id=${userId}`);
        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        const permissions = [
            { slug: 'dashboard', label: 'Dashboard' }, { slug: 'users', label: 'Utilizadores' },
            { slug: 'ads', label: 'Publicidade' }, { slug: 'moderation', label: 'ModeraÃ§Ã£o' },
            { slug: 'support', label: 'Suporte' }, { slug: 'kyc', label: 'KYC' },
            { slug: 'mentor_approval', label: 'AprovaÃ§Ã£o Mentores' }, { slug: 'finances', label: 'FinanÃ§as' },
            { slug: 'settings', label: 'Definicoes' }
        ];

        let html = `<div style="text-align: left; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">`;
        permissions.forEach(p => {
            const checked = data.permissions.includes(p.slug) ? 'checked' : '';
            html += `
                <div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 8px; border-radius: 6px;">
                    <input type="checkbox" id="perm_${p.slug}" class="perm-checkbox" value="${p.slug}" ${checked}>
                    <label for="perm_${p.slug}" style="font-size: 0.8rem; cursor: pointer;">${p.label}</label>
                </div>
            `;
        });
        html += `</div>`;

        const { value: selectedPerms } = await Swal.fire({
            title: `PermissÃµes: ${userName}`,
            html: html,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Atualizar',
            preConfirm: () => Array.from(document.querySelectorAll('.perm-checkbox:checked')).map(cb => cb.value)
        });

        if (selectedPerms) {
            fetch('../../interface_programacao/admin/admin_save_user_permissions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, permissions: selectedPerms })
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Nao foi possivel guardar permissoes.');
                Swal.fire('Sucesso!', 'Permissoes atualizadas.', 'success');
            })
            .catch(err => Swal.fire('Erro', err.message, 'error'));
        }
    } catch (err) { Swal.fire('Erro', err.message, 'error'); }
}

function deleteUser(userId) {
    Swal.fire({
        title: 'Confirmar ExclusÃ£o?',
        text: "O utilizador serÃ¡ removido permanentemente!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Sim, eliminar!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id', userId);
            fetch('../../interface_programacao/admin/admin_delete_user.php', { method: 'POST', body: formData })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if(data.success) Swal.fire('Eliminado!', '', 'success').then(() => location.reload());
                else Swal.fire('Erro', data.message, 'error');
            })
            .catch(err => Swal.fire('Erro', err.message || 'Falha ao eliminar utilizador.', 'error'));
        }
    });
}
</script>

