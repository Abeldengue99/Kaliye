/**
 * recursos/js/social_interactions.js
 * Handles social interactions like Likes, Comments, and Modals on the feed.
 */

// 1. PROJECT LIKES
function toggleLike(projectId) {
    const btn = document.getElementById(`like-btn-${projectId}`);
    
    fetch(`${BASE_URL}interface_programacao/projects/like_project.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ project_id: projectId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update Like Count Text
            const countSpan = document.getElementById(`like-count-${projectId}`);
            if (countSpan) {
                countSpan.innerText = data.new_count;
            }

            // Update Button State (color)
            if (data.action === 'liked') {
                btn.style.color = 'var(--elite-orange)';
                btn.querySelector('i').classList.replace('far', 'fas');
            } else {
                btn.style.color = 'rgba(255,255,255,0.3)';
                btn.querySelector('i').classList.replace('fas', 'far');
            }
        }
    })
    .catch(error => console.error('Error toggling like:', error));
}

// 2. SHOW LIKES MODAL
function showFeedLikes(projectId) {
    const modal = document.getElementById('likesModal');
    const list = document.getElementById('likesList');
    const overlay = document.getElementById('likesOverlay');

    if (!modal || !list) {
        console.error('Likes modal elements not found in DOM');
        return;
    }

    list.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
    modal.style.display = 'block';
    if (overlay) overlay.style.display = 'block';

    fetch(`${BASE_URL}servicos/projects/get_project_likes.php?project_id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            list.innerHTML = '';
            if (data.success && data.likes.length > 0) {
                data.likes.forEach(user => {
                    const item = document.createElement('div');
                    item.style.display = 'flex';
                    item.style.alignItems = 'center';
                    item.style.gap = '1rem';
                    item.style.padding = '0.5rem 0';
                    item.style.borderBottom = '1px solid var(--glass-border)';

                    const img = user.profile_pic && user.profile_pic !== 'default_profile.png'
                        ? `${BASE_URL}carregamentos/profiles/${user.profile_pic}`
                        : `${BASE_URL}recursos/images/default_profile.png`;

                    item.innerHTML = `
                        <img src="${img}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #000;">
                        <span style="font-weight: 600; color: var(--text-primary); font-size: 0.9rem;">${user.full_name}</span>
                    `;
                    list.appendChild(item);
                });
            } else {
                list.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-secondary);">Ainda ninguém gostou deste projeto.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching likes:', error);
            list.innerHTML = '<div style="text-align: center; color: var(--danger);">Erro ao carregar likes.</div>';
        });
}

function closeLikesModal() {
    const modal = document.getElementById('likesModal');
    const overlay = document.getElementById('likesOverlay');
    if (modal) modal.style.display = 'none';
    if (overlay) overlay.style.display = 'none';
}

// 3. TOGGLE COMMENTS SECTION
function toggleComments(projectId) {
    const section = document.getElementById(`comments-section-${projectId}`);
    if (section) {
        const isHidden = section.style.display === 'none';
        section.style.display = isHidden ? 'block' : 'none';

        if (isHidden) {
            const input = document.getElementById(`comment-input-${projectId}`);
            if (input) setTimeout(() => input.focus(), 100);
        }
    }
}

// 4. SUBMIT COMMENT
function submitModalComment(projectId) {
    const input = document.getElementById(`comment-input-${projectId}`);
    const btn = document.getElementById(`comment-submit-btn-${projectId}`);
    const content = input.value.trim();

    if (!content) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`${BASE_URL}interface_programacao/projects/post_project_comment.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_id: projectId, content: content })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            // Refresh details to show new comment using global function
            if (typeof window.openProjectDetails === 'function') {
                window.openProjectDetails(projectId);
            }
        } else {
            Swal.fire('Erro', data.message, 'error');
        }
    })
    .catch(err => console.error('Error submitting comment:', err))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Publicar';
    });
}
