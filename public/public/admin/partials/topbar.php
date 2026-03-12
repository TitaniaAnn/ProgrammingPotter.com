<?php
$user = Auth::getUser();
$flash = getFlash();
?>
<header class="admin-topbar">
    <button class="admin-topbar__burger" id="adminBurger" aria-label="Toggle sidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
    <div class="admin-topbar__right">
        <?php if ($user['avatar']): ?>
        <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" class="admin-topbar__avatar">
        <?php endif; ?>
        <span class="admin-topbar__name"><?= e($user['name'] ?? '') ?></span>
    </div>
</header>

<?php if ($flash): ?>
<div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
