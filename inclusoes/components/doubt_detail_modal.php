<?php /** Component: Doubt Detail Modal — Premium Redesign */ ?>

<div id="doubtDetailModal" style="
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.88); z-index: 9999;
    align-items: center; justify-content: center;
    backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    overflow-y: auto; padding: 2rem 1rem;">

    <div style="
        width: 100%; max-width: 820px; margin: auto;
        background: #0b1120;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 28px; position: relative;
        box-shadow: 0 40px 80px rgba(0,0,0,0.6);
        animation: dqModalIn 0.35s cubic-bezier(0.34,1.56,0.64,1) both;">

        <!-- Close -->
        <button onclick="closeDoubtDetailModal()" style="
            position: absolute; top: 1.25rem; right: 1.25rem; z-index: 10;
            background: var(--surface-5); border: 1px solid var(--surface-8);
            color: var(--surface-40); width: 36px; height: 36px;
            border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; transition: 0.25s;"
            onmouseover="this.style.background='rgba(239,68,68,0.15)'; this.style.color='#ef4444'; this.style.borderColor='rgba(239,68,68,0.3)'"
            onmouseout="this.style.background='var(--surface-5)'; this.style.color='var(--surface-40)'; this.style.borderColor='var(--surface-8)'">
            <i class="fas fa-times"></i>
        </button>

        <!-- Content injected by JS -->
        <div id="doubtDetailContent" style="padding: 2.5rem; overflow-y: auto; max-height: 88vh;">
            <div style="display: flex; flex-direction: column; align-items: center; padding: 4rem; gap: 1rem; color: var(--surface-20);">
                <div style="width: 44px; height: 44px; border-radius: 50%; border: 3px solid rgba(247,148,29,0.2); border-top-color: #f7941d; animation: dqSpin 0.75s linear infinite;"></div>
                <p style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin: 0;">A carregar...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes dqModalIn {
    from { opacity: 0; transform: scale(0.93) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes dqSpin { to { transform: rotate(360deg); } }

/* ── detalhe interno ── */
.dq-detail-header { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--surface-5); }
.dq-detail-title  { font-size: 1.4rem; font-weight: 900; color: #fff; letter-spacing: -0.5px; line-height: 1.3; margin: 0 2.5rem 1.25rem 0; }
.dq-detail-author { display: flex; align-items: center; gap: 0.9rem; }
.dq-detail-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(247,148,29,0.35); }
.dq-detail-name   { font-weight: 700; color: #fff; font-size: 0.9rem; }
.dq-detail-meta   { font-size: 0.7rem; color: var(--surface-30); margin-top: 2px; }
.dq-detail-body   {
    background: rgba(255,255,255,0.025);
    border: 1px solid var(--surface-5);
    border-left: 3px solid var(--elite-orange, #f7941d);
    border-radius: 0 16px 16px 0;
    padding: 1.5rem 1.75rem;
    font-size: 0.95rem; line-height: 1.8;
    color: rgba(255,255,255,0.75);
    white-space: pre-wrap; margin-bottom: 2rem;
}

/* ── comments ── */
.dq-comments-title {
    font-size: 0.65rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: 2.5px; color: var(--surface-25);
    margin-bottom: 1.5rem; padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--surface-5);
}
.dq-comment-item {
    display: flex; gap: 0.85rem; margin-bottom: 1.5rem;
    padding-bottom: 1.5rem; border-bottom: 1px solid var(--surface-3);
}
.dq-comment-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.06); flex-shrink: 0; }
.dq-comment-bubble { flex: 1; }
.dq-comment-name   { font-size: 0.82rem; font-weight: 700; color: #fff; }
.dq-comment-date   { font-size: 0.65rem; color: var(--surface-25); margin-left: 8px; }
.dq-comment-text   { font-size: 0.87rem; color: var(--surface-60); line-height: 1.7; margin-top: 0.5rem; }
.dq-comment-actions { display: flex; gap: 1rem; margin-top: 0.65rem; align-items: center; }
.dq-comment-btn {
    background: none; border: none; cursor: pointer;
    font-size: 0.68rem; font-weight: 700;
    color: var(--surface-25); display: flex; align-items: center; gap: 5px;
    padding: 4px 0; transition: color 0.2s; text-transform: uppercase; letter-spacing: 1px;
}
.dq-comment-btn:hover { color: var(--elite-orange, #f7941d); }
.dq-comment-btn.helpful { color: #10b981; }
.dq-comment-solution { background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 6px; padding: 2px 10px; font-size: 0.6rem; font-weight: 800; color: #10b981; text-transform: uppercase; letter-spacing: 1px; }

.dq-helpful-comment { border-left: 2px solid #10b981 !important; background: rgba(16,185,129,0.04) !important; }

/* ── reply form ── */
.dq-reply-form { margin-top: 2.5rem; }
.dq-reply-form textarea {
    width: 100%; box-sizing: border-box;
    padding: 1rem 1.25rem;
    background: var(--surface-3);
    border: 1px solid var(--surface-8);
    border-radius: 14px; color: #fff; font-size: 0.88rem;
    line-height: 1.6; resize: vertical; outline: none;
    transition: border-color 0.25s; font-family: inherit;
}
.dq-reply-form textarea:focus { border-color: rgba(247,148,29,0.4); }
.dq-reply-form textarea::placeholder { color: var(--surface-20); }
.dq-reply-submit {
    margin-top: 0.75rem; float: right;
    background: var(--elite-orange, #f7941d); color: #fff; border: none;
    padding: 0.75rem 2rem; border-radius: 12px;
    font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px;
    cursor: pointer; transition: 0.3s;
}
.dq-reply-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(247,148,29,0.3); }
.dq-action-btns { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.dq-action-btn-sm {
    padding: 0.55rem 1.25rem; border-radius: 10px; border: 1px solid var(--surface-10);
    font-size: 0.67rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
    cursor: pointer; display: flex; align-items: center; gap: 7px; transition: 0.25s;
}
</style>
