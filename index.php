<?php
require_once __DIR__ . '/src/bootstrap.php';

$serviceName = $serviceConfig['name'] ?? 'Snake';
$serviceDescription = $serviceConfig['description'] ?? '키보드와 스와이프로 즐기는 16x16 클래식 스네이크.';
$isLoggedIn = !empty($session['loggedIn']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <title><?= htmlspecialchars($serviceName, ENT_QUOTES) ?> — 1q2w Fun</title>
    <meta name="description" content="<?= htmlspecialchars($serviceDescription, ENT_QUOTES) ?>" />

    <!-- Shared styles -->
    <link rel="stylesheet" href="/fun/common/header.css?v=1.0" />

    <!-- Service styles -->
    <link rel="stylesheet" href="/fun/snake/css/app.css?v=1.0" />
</head>
<body class="snake">
<?php
$brand = '1q2w.kr';
$home = '/';
$service = $serviceName;
$portalUrl = '/fun/common/';

$headerInclude = __DIR__ . '/../common/header.php';
if (!file_exists($headerInclude)) {
    $headerInclude = __DIR__ . '/../../common/header.php';
}
if (file_exists($headerInclude)) {
    include $headerInclude;
}
?>

<main class="snake__container" id="main" tabindex="-1">
    <header class="snake__hero">
        <div>
            <p class="snake__eyebrow">새로운 퍼즐 · 16x16 보드</p>
            <h1 class="snake__title"><?= htmlspecialchars($serviceName, ENT_QUOTES) ?></h1>
            <p class="snake__lead"><?= htmlspecialchars($serviceDescription, ENT_QUOTES) ?></p>
        </div>
        <div class="snake__cta">
            <button class="snake__btn snake__btn--primary" data-action="start">시작 / 다시하기</button>
            <button class="snake__btn" data-action="pause">일시정지</button>
        </div>
    </header>

    <section class="snake__hud" aria-label="게임 정보">
        <div class="snake__stat">
            <div class="snake__label">점수</div>
            <div class="snake__value" data-score>0</div>
        </div>
        <div class="snake__stat">
            <div class="snake__label">최고</div>
            <div class="snake__value" data-best>0</div>
        </div>
        <div class="snake__stat">
            <div class="snake__label">속도</div>
            <div class="snake__value"><span data-speed>0</span> fps</div>
        </div>
        <div class="snake__stat">
            <div class="snake__label">길이</div>
            <div class="snake__value" data-length>0</div>
        </div>
    </section>

    <section class="snake__board-shell">
        <div class="snake__board" data-board role="grid" aria-label="Snake 보드" aria-live="polite"></div>
        <div class="snake__overlay" data-overlay hidden>
            <div class="snake__overlay-box">
                <p class="snake__overlay-title" data-overlay-title>준비 완료</p>
                <p class="snake__overlay-text" data-overlay-text>시작 버튼을 눌러 주세요.</p>
                <div class="snake__overlay-actions">
                    <button class="snake__btn snake__btn--primary" data-overlay-action>다시 시작</button>
                </div>
            </div>
        </div>
    </section>

    <section class="snake__controls" aria-label="방향 버튼">
        <div class="snake__controls-row">
            <button class="snake__ctrl" data-direction="up" aria-label="위로 이동">▲</button>
        </div>
        <div class="snake__controls-row">
            <button class="snake__ctrl" data-direction="left" aria-label="왼쪽으로 이동">◀</button>
            <button class="snake__ctrl" data-direction="down" aria-label="아래로 이동">▼</button>
            <button class="snake__ctrl" data-direction="right" aria-label="오른쪽으로 이동">▶</button>
        </div>
        <p class="snake__controls-hint">키보드 방향키 / WASD · 터치 스와이프 지원</p>
    </section>

    <section class="snake__guide">
        <h2>플레이 방법</h2>
        <ul>
            <li>16x16 보드에서 뱀을 이동하며 먹이를 먹으면 점수가 증가합니다.</li>
            <li>먹이를 먹을 때마다 속도가 조금씩 빨라집니다.</li>
            <li>벽이나 자기 몸에 부딪히면 게임 오버입니다.</li>
            <li>최고 점수는 브라우저에 저장됩니다.</li>
            <?php if (!$isLoggedIn): ?>
            <li>로그인하면 다른 fun 서비스와 세션이 공유됩니다.</li>
            <?php endif; ?>
        </ul>
    </section>
</main>

<div role="status" aria-live="polite" class="visually-hidden" data-status-region></div>

<footer class="snake__footer" role="contentinfo">
    <div class="snake__footer-inner">
        <a href="#" data-cookie-settings>쿠키 설정</a>
        <span aria-hidden="true">·</span>
        <a href="https://1q2w.kr/privacy" target="_blank" rel="noopener">개인정보처리방침</a>
        <span aria-hidden="true">·</span>
        <a href="https://1q2w.kr/terms" target="_blank" rel="noopener">이용약관</a>
    </div>
</footer>

<!-- Shared scripts -->
<script src="/fun/common/header.js" defer></script>

<!-- Game scripts -->
<script src="/fun/snake/js/app.js?v=1.0" defer></script>
</body>
</html>
