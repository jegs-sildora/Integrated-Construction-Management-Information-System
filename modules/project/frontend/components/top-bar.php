<?php
$breadcrumbs = $breadcrumbs ?? [
    ['label' => 'Labor & Workforce', 'link' => null],  // always default
    ['label' => $title ?? 'Page Title', 'link' => null] // current page
];

$userName = "Manager"; // Replace with dynamic username
$avatarInitials = strtoupper(substr($userName, 0, 1));
?>

<div class="top-bar">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index !== array_key_last($breadcrumbs) && $crumb['link']): ?>
                <a href="<?= htmlspecialchars($crumb['link']) ?>" class="breadcrumb-link"><?= htmlspecialchars($crumb['label']) ?></a>
                <span class="breadcrumb-separator">&gt;</span>
            <?php elseif ($index !== array_key_last($breadcrumbs)): ?>
                <span class="breadcrumb-text"><?= htmlspecialchars($crumb['label']) ?></span>
                <span class="breadcrumb-separator">&gt;</span>
            <?php else: ?>
                <span class="breadcrumb-current"><?= htmlspecialchars($crumb['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- User Profile / Actions -->
    <div class="user-actions">
        <div class="user-profile">
            <div class="user-info">
                <span class="name"><?= htmlspecialchars($userName) ?></span>
                <span class="role">Admin</span>
            </div>
            <div class="avatar"><?= $avatarInitials ?></div>
        </div>
    </div>
</div>
