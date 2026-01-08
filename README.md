# Snake (fun/snake)

16x16 클래식 스네이크. 키보드/버튼/스와이프로 조작할 수 있고, 먹이를 먹을 때마다 속도가 조금씩 빨라집니다. 로그인하면 점수가 저장되고 전체/개인 리더보드를 볼 수 있습니다.

## 로컬 실행
```
docker compose up -d
# http://localhost:8000/fun/snake/
```

## DB 준비
- 마이그레이션: `source /var/www/html/fun/snake/dbinit/0001_init.sql`
- 테이블: `snake_scores` (score/length/최고속도/시간, 회원 로그인 기준 랭킹)

## DB Init Quickstart
1) Apply once:
```bash
mysql -u <user> -p <db> < fun/snake/dbinit/0001_init.sql
```
2) API는 `information_schema` 체크 후 테이블이 없을 때만 초기화합니다.

## API
- POST `/fun/snake/api/game.php` action `submit` (로그인 필요): `{ sessionToken, score, length, durationMs, maxSpeedFps }`
- GET `/fun/snake/api/game.php?action=leaderboard&limit=10`
- GET `/fun/snake/api/game.php?action=history&limit=10` (로그인 시)

## 조작법
- 방향키 / WASD / 화면 버튼 / 스와이프
- 스페이스바: 일시정지/재개
- 시작/다시하기 버튼으로 새 판을 시작

## 특징
- 16x16 고정 보드, 초기 속도 160ms → 최소 80ms까지 가속
- 먹이마다 +10점, 길이/속도 HUD 표시
- 게임 오버/일시정지 오버레이
- 로컬 스토리지에 최고 점수 저장

## 배포
- 다른 fun 서비스와 동일하게 `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` 시크릿을 사용합니다.
- 새로운 서비스이므로 별도 리포지토리로 초기화한 뒤 루트에서 서브모듈로 추가해 주세요.
