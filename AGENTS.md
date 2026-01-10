# Snake Service Guide

## Baseline
- Entry points: `index.php` and `api/game.php`.
- Shared auth: load `/www/fun/common/rhymix_bridge.php` when present.
- Service status: call `fun_service_require_enabled('snake')` from `fun/common/service/guard.php` in both web and API entry points.
- Shared header: include `/fun/common/header.php` in the UI for consistent navigation and consent widgets.

## Deployment
- GitHub Actions deploys to `ciiwol/www/fun/snake/` (FTP root, trailing `/` required).
- For local uploads, use workspace `scripts/ftp_upload.py` and select `fun/snake`.
