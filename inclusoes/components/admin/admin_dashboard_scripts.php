<script>
/**
 * Admin Dashboard Logic
 */
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('admin-ready');

    const refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'admin-refresh-indicator';
    refreshIndicator.setAttribute('aria-hidden', 'true');
    document.body.appendChild(refreshIndicator);

    let refreshIndicatorTimer = null;
    const setRefreshing = (isRefreshing) => {
        document.body.classList.toggle('admin-data-refreshing', isRefreshing);
        refreshIndicator.classList.toggle('is-visible', isRefreshing);
        if (!isRefreshing) {
            clearTimeout(refreshIndicatorTimer);
            refreshIndicatorTimer = setTimeout(() => refreshIndicator.classList.remove('is-visible'), 250);
        }
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const safeSet = (id, val) => { 
        const el = document.getElementById(id); 
        if (!el) return;
        
        const newValue = (val !== undefined && val !== null) ? val : 0;
        const currentText = el.innerText.replace(/\./g, '');
        const oldValue = isNaN(currentText) ? 0 : parseInt(currentText);

        if (oldValue !== newValue) {
            // Animação simples de transição se o valor mudar
            el.style.transform = 'scale(1.1)';
            el.style.color = 'var(--accent-orange)';
            setTimeout(() => {
                el.innerText = typeof newValue === 'number' ? newValue.toLocaleString('pt-PT') : newValue;
                el.style.transform = 'scale(1)';
                el.style.color = '';
            }, 200);
        }
    };
    
    let userGrowthChart = null;
    let categoriesChart = null;
    let isRefreshingDashboard = false;

    async function refreshDashboard() {
        if (isRefreshingDashboard) return;
        isRefreshingDashboard = true;
        setRefreshing(true);
        try {
            const response = await fetch(BASE_URL + 'interface_programacao/admin/admin_stats.php');
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            
            safeSet('totalUsers',       data.total_users);
            safeSet('totalProjects',    data.total_projects);
            safeSet('totalMentorships', data.total_mentorships);
            safeSet('totalAdViews',     data.total_ad_views);
            
            if (!window.dashboardChartsInitialized) {
                initCharts(data);
                window.dashboardChartsInitialized = true;
            } else {
                updateCharts(data);
            }

            renderTable('topPostersTable',    data.top_posters,    'project_count', 'Projetos');
            renderTable('topCommentersTable', data.top_commenters, 'comment_count', 'Comentários');
            renderBirthdays(data.upcoming_birthdays);
        } catch (err) {
            console.error('Admin stats error:', err);
            ['totalUsers','totalProjects','totalMentorships','totalAdViews'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.innerText = '!'; el.style.color = '#ef4444'; el.title = 'Erro ao carregar dados'; }
            });
        } finally {
            isRefreshingDashboard = false;
            setRefreshing(false);
        }
    }

    function updateCharts(data) {
        const userGrowth = Array.isArray(data.user_growth) ? data.user_growth : [];
        const categories = Array.isArray(data.categories) ? data.categories : [];
        // Update userGrowthChart
        if (userGrowthChart) {
            userGrowthChart.data.labels = userGrowth.length > 0
                ? userGrowth.map(d => {
                    const [y, m] = d.month.split('-');
                    return new Date(y, m - 1).toLocaleDateString('pt-PT', { month: 'short', year: '2-digit' });
                  })
                : userGrowthChart.data.labels;
            userGrowthChart.data.datasets[0].data = userGrowth.map(d => d.count);
            userGrowthChart.update();
        }

        // Update categoriesChart
        if (categoriesChart) {
            categoriesChart.data.labels = categories.length > 0 ? categories.map(d => d.category || 'Sem cat.') : ['Sem dados'];
            categoriesChart.data.datasets[0].data = categories.length > 0 ? categories.map(d => d.count) : [1];
            categoriesChart.update();
        }
    }

    // Carregamento Inicial imediato
    refreshDashboard();

    // Atualizacao em tempo real com intervalo mais leve para reduzir carga no servidor.
    setInterval(() => {
        if (!document.hidden) refreshDashboard();
    }, 30000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshDashboard();
    });
    
    function getProfilePic(pic) {
        if (!pic || pic === 'default_profile.png') return '../recursos/images/default_profile.png';
        if (pic.startsWith('http')) return pic;
        if (pic.startsWith('carregamentos/')) return '../' + pic;
        return '../carregamentos/profiles/' + pic;
    }

    
    function renderBirthdays(users) {
        const list = document.getElementById('birthdayList');
        if(!list) return;
        if(!Array.isArray(users) || users.length === 0) {
            list.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.85rem;">Nenhum aniversário próximo.</p>';
            return;
        }
        list.innerHTML = users.map(u => `
            <div class="birthday-card">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                     <img src="${getProfilePic(u.profile_pic)}" 
                          onerror="this.src='../recursos/images/marca/logotipo.png'; this.style.padding='4px'; this.style.background='rgba(255,255,255,0.05)';"
                          style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                     <div>
                        <div style="font-size: 0.9rem; font-weight: 600;">${escapeHtml(u.full_name)}</div>
                        <div style="font-size: 0.75rem; color: var(--accent-orange);">${escapeHtml(u.formatted_date)}</div>
                     </div>
                </div>
                <button onclick="sendBirthdayWish(${Number(u.user_id || 0)}, decodeURIComponent('${encodeURIComponent(u.full_name || '')}'))" class="btn-wish">
                    <i class="fas fa-gift"></i>
                </button>
            </div>
        `).join('');
    }

    window.sendBirthdayWish = function(userId, name) {
        Swal.fire({
            title: 'Desejar Parabéns',
            text: `Enviar mensagem especial para ${name}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f7941d',
            background: '#ffffff',
            color: '#0f172a'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', userId);
                fetch('../interface_programacao/admin/admin_send_birthday_wish.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) Swal.fire('Enviado!', '', 'success');
                    });
            }
        });
    }

    function renderTable(id, users, countKey, label) {
        const tbody = document.getElementById(id);
        if(!tbody) return;
        if(!Array.isArray(users) || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:1rem; color:var(--text-secondary);">Sem dados.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(u => `
            <tr style="border-bottom: 1px solid var(--glass-border);">
                <td style="padding: 0.75rem 0;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="${getProfilePic(u.profile_pic)}" 
                             onerror="this.src='../recursos/images/marca/logotipo.png'; this.style.padding='4px'; this.style.background='rgba(255,255,255,0.05)';"
                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid var(--accent-orange);">
                        <span>${escapeHtml(u.full_name)}</span>
                    </div>
                </td>
                <td style="padding: 0.75rem 0; text-align: right; font-weight: bold;">
                    ${Number(u[countKey] || 0)} <span style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 400;">${label}</span>
                </td>
            </tr>
        `).join('');
    }

    function initCharts(data) {
        const userGrowth = Array.isArray(data.user_growth) ? data.user_growth : [];
        const categories = Array.isArray(data.categories) ? data.categories : [];
        const GRID_COLOR   = 'rgba(255, 255, 255, 0.06)';
        const TICK_COLOR   = 'rgba(255, 255, 255, 0.40)';
        const LEGEND_COLOR = '#ffffff';
        Chart.defaults.color = TICK_COLOR;

        const ctx1 = document.getElementById('userGrowthChart')?.getContext('2d');
        if (ctx1) {
            const grad = ctx1.createLinearGradient(0, 0, 0, 320);
            grad.addColorStop(0,   'rgba(247, 148, 29, 0.30)');
            grad.addColorStop(1,   'rgba(247, 148, 29, 0.00)');
            const labels = userGrowth.length > 0
                ? userGrowth.map(d => {
                    const [y, m] = d.month.split('-');
                    return new Date(y, m - 1).toLocaleDateString('pt-PT', { month: 'short', year: '2-digit' });
                  })
                : ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const counts = userGrowth.length > 0 ? userGrowth.map(d => d.count) : [0, 0, 0, 0, 0, 0];

            userGrowthChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Novos Utilizadores',
                        data: counts,
                        borderColor: '#f7941d',
                        borderWidth: 2.5,
                        backgroundColor: grad,
                        pointBackgroundColor: '#f7941d',
                        pointBorderColor: '#0d1628',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        tension: 0.45,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: 'rgba(13, 22, 40, 0.95)', borderColor: 'rgba(247, 148, 29, 0.3)', borderWidth: 1, titleColor: '#f7941d', bodyColor: '#fff', padding: 12, cornerRadius: 12, callbacks: { label: ctx => ` ${ctx.parsed.y} novos utilizadores` } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: GRID_COLOR, lineWidth: 1 }, border: { dash: [4, 4], color: 'transparent' }, ticks: { color: TICK_COLOR, font: { size: 12 }, padding: 8 } },
                        x: { grid: { display: false }, border: { color: GRID_COLOR }, ticks: { color: TICK_COLOR, font: { size: 12 }, maxRotation: 0 } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('categoriesChart')?.getContext('2d');
        if (ctx2) {
            const palette = ['#60a5fa', '#34d399', '#fb923c', '#fbbf24', '#a78bfa', '#f472b6'];
            const cats   = categories.length > 0 ? categories.map(d => d.category || 'Sem cat.') : ['Sem dados'];
            const counts = categories.length > 0 ? categories.map(d => d.count)                  : [1];
            const colors = categories.length > 0 ? palette.slice(0, cats.length)                 : ['var(--surface-10)'];

            categoriesChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: cats,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors,
                        hoverBackgroundColor: colors.map(c => c + 'cc'),
                        borderWidth: 3,
                        borderColor: '#0d1628',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { animateRotate: true, duration: 900 },
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: LEGEND_COLOR, padding: 16, usePointStyle: true, pointStyleWidth: 10, font: { size: 12, weight: '600' } } },
                        tooltip: { backgroundColor: 'rgba(13, 22, 40, 0.95)', borderColor: 'var(--surface-8)', borderWidth: 1, titleColor: '#f7941d', bodyColor: '#fff', padding: 12, cornerRadius: 12 }
                    }
                }
            });
        }
    }
});
</script>

