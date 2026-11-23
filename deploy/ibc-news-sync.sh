#!/bin/bash
# Server-side sync for indonesiabriquettescharcoal.net News (PT DAN).
# Installed at ~/bin/ibc-news-sync.sh on the cPanel server (NOT executed from the repo,
# so changes pushed to GitHub can never change what this cron job runs).
# Runs hourly via cron: pulls the GitHub repo, then runs ~/bin/ibc-news-import.php,
# which only ADDS new posts to WordPress. It never edits or deletes anything.
set -euo pipefail
REPO="$HOME/repositories/indonesiabriquettes-news"
GIT="$(command -v git || echo /usr/local/cpanel/3rdparty/bin/git)"
PHP=""
for p in /usr/local/bin/php /usr/bin/php $(ls -d /opt/cpanel/ea-php*/root/usr/bin/php 2>/dev/null | sort -r); do
  if [ -x "$p" ]; then PHP="$p"; break; fi
done
LOCK="$HOME/.ibc-news-sync.lock"
exec 9>"$LOCK"
flock -n 9 || { echo "[$(date '+%F %T')] previous run still active"; exit 0; }

cd "$REPO"
"$GIT" fetch --quiet origin main
"$GIT" reset --quiet --hard origin/main
[ -n "$PHP" ] || { echo "[$(date '+%F %T')] ERROR: php CLI not found"; exit 1; }
"$PHP" -d display_errors=stderr "$HOME/bin/ibc-news-import.php"
