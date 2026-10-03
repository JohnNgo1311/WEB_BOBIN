<?php
$currentLangCode = Language::getCurrent();
$currentLangInfo = Language::getCurrentInfo();
$supportedLangs  = Language::SUPPORTED;
?>
<div class="lang-switcher-dropdown" id="appLangSwitcher">
    <button type="button" class="lang-btn-current" title="Đổi ngôn ngữ / Change Language / 言語変更">
        <span class="lang-flag"><?= $currentLangInfo['flag'] ?></span>
        <span class="lang-code"><?= $currentLangInfo['short'] ?></span>
        <span class="lang-arrow">▾</span>
    </button>
    <div class="lang-dropdown-menu">
        <?php foreach ($supportedLangs as $code => $info): ?>
            <a href="javascript:void(0)" class="lang-menu-item <?= ($code === $currentLangCode) ? 'active' : '' ?>" data-lang="<?= $code ?>" onclick="if(window.setAppLanguage){ window.setAppLanguage('<?= $code ?>', true); } else { location.href='/WEB_BOBIN/public/index.php?url=auth/setLanguage&lang=<?= $code ?>'; }">
                <span class="lang-item-content">
                    <span><?= $info['flag'] ?></span>
                    <span><?= htmlspecialchars($info['name']) ?></span>
                </span>
                <span class="lang-item-check">✓</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
