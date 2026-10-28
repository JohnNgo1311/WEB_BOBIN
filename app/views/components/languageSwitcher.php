<?php
$currentLangCode = Language::getCurrent();
$supportedLangs  = Language::SUPPORTED;
?>
<div class="lang-flag-switcher" id="appLangSwitcher" role="group" aria-label="Đổi ngôn ngữ / Change Language / 言語変更">
    <?php foreach ($supportedLangs as $code => $info): ?>
        <button type="button"
            class="lang-flag-btn <?= ($code === $currentLangCode) ? 'active' : '' ?>"
            data-lang="<?= htmlspecialchars($code) ?>"
            title="<?= htmlspecialchars($info['name']) ?>"
            aria-label="<?= htmlspecialchars($info['name']) ?>">
            <?= Language::renderFlagSvg($code, 24, 16) ?>
        </button>
    <?php endforeach; ?>
</div>