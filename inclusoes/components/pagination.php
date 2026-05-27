<?php
/**
 * Component: Pagination (Elite Version)
 * Displays pagination controls with a premium dark design.
 */
if ($total_pages < 1) return;

$current_page = $feed_page ?? 1;
?>
<div class="elite-pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.75rem; margin-top: 4rem; margin-bottom: 2rem;">
    
    <!-- Prev Arrow -->
    <?php if ($current_page > 1): ?>
        <a href="javascript:void(0)" onclick="applyFeedFilters(<?php echo $current_page - 1; ?>)" class="pg-arrow">
            <i class="fas fa-chevron-left"></i>
        </a>
    <?php else: ?>
        <div class="pg-arrow pg-disabled">
            <i class="fas fa-chevron-left"></i>
        </div>
    <?php endif; ?>

    <!-- Pages -->
    <?php 
    $show_start = max(1, $current_page - 1);
    $show_end = min($total_pages, $show_start + 2);
    if ($show_end - $show_start < 2) $show_start = max(1, $show_end - 2);

    if ($show_start > 1): ?>
        <a href="javascript:void(0)" onclick="applyFeedFilters(1)" class="pg-item">1</a>
        <?php if ($show_start > 2): ?><span class="pg-ellipsis">...</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $show_start; $i <= $show_end; $i++): ?>
        <a href="javascript:void(0)" onclick="applyFeedFilters(<?php echo $i; ?>)" class="pg-item <?php echo $i == $current_page ? 'pg-active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($show_end < $total_pages): ?>
        <?php if ($show_end < $total_pages - 1): ?><span class="pg-ellipsis">...</span><?php endif; ?>
        <a href="javascript:void(0)" onclick="applyFeedFilters(<?php echo $total_pages; ?>)" class="pg-item"><?php echo $total_pages; ?></a>
    <?php endif; ?>

    <!-- Next Arrow -->
    <?php if ($current_page < $total_pages): ?>
        <a href="javascript:void(0)" onclick="applyFeedFilters(<?php echo $current_page + 1; ?>)" class="pg-arrow">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <div class="pg-arrow pg-disabled">
            <i class="fas fa-chevron-right"></i>
        </div>
    <?php endif; ?>

</div>

<style>
.elite-pagination {
    font-family: 'Outfit', sans-serif;
}
.pg-item, .pg-arrow {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--surface-5);
    border-radius: 12px;
    color: var(--surface-50);
    text-decoration: none;
    font-weight: 800;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.pg-item:hover, .pg-arrow:hover:not(.pg-disabled) {
    background: var(--surface-8);
    color: #fff;
    transform: translateY(-2px);
    border-color: var(--surface-10);
}
.pg-active {
    background: #f7941d !important;
    color: #000 !important;
    border-color: #f7941d !important;
    box-shadow: 0 8px 20px rgba(247, 148, 29, 0.3);
}
.pg-ellipsis {
    color: var(--surface-20);
    font-weight: 900;
    margin: 0 0.25rem;
}
.pg-disabled {
    opacity: 0.3;
    cursor: default;
}
.pg-arrow i {
    font-size: 0.8rem;
}
</style>
