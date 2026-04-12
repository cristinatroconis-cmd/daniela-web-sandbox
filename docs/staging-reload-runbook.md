# Staging Reload Runbook (from local, isolated from production)

Objective:
- Rebuild staging using local snapshot/code
- Keep staging isolated so edits/actions do not impact production

## 0) Reconnect handoff (show this to Copilot when connection is back)

Paste this message:

"Resume staging rebuild now. Use docs/staging-reload-runbook.md as source of truth. Goal: rebuild staging from local snapshot and keep it isolated from production. Execute end-to-end: upload files, import DB, run URL replacements, verify guardrails, and report final validation results. Do not touch production."

Current context to resume quickly:
1. File already prepared in repo: wp-content/mu-plugins/dm-staging-guardrails.php
2. Child theme hardcoded local image URLs were already fixed in home section.
3. Connectivity has been intermittent (SFTP sometimes works, SSH often times out).

Definition of done:
1. Staging reflects local snapshot.
2. Staging URL is canonical everywhere.
3. Staging cannot send real emails or real Woo webhooks.
4. noindex is active on staging.
5. Final QA checklist in section 6 passes.

## 1) Inputs to prepare
- Local files source: /Users/cristinatroconis/Local Sites/dani-backup/app/public
- Local DB source: /Users/cristinatroconis/Local Sites/dani-backup/app/sql/local.sql
- Staging host: v2vvroh9bv-staging.onrocket.site
- Remote user: v2vvxfo

## 2) Pre-flight checks
1. Confirm a production backup exists in Rocket panel (already done by you).
2. Confirm this MU plugin is deployed on staging:
   - wp-content/mu-plugins/dm-staging-guardrails.php
3. Confirm child theme changes are current in repo before upload.

## 3) Rebuild staging files from local
Remote target path usually:
- /home/v2vvxfo/public_html

Recommended transfer strategy:
1. Keep wp-config.php from staging host (do not overwrite credentials).
2. Replace wp-content from local snapshot.
3. Keep current .htaccess unless you need the local one.

Example rsync from your machine (if SSH is available):
rsync -avz --delete \
  --exclude='wp-config.php' \
  --exclude='.htaccess' \
  '/Users/cristinatroconis/Local Sites/dani-backup/app/public/' \
  'v2vvxfo@131.153.200.61:public_html/'

If SSH remains unstable, use SFTP upload in batches:
1. Upload wp-content/themes/daniela-child
2. Upload wp-content/mu-plugins
3. Upload required plugins changed in local
4. Upload uploads only if needed

## 4) Rebuild staging DB from local.sql
On server, import local.sql into staging DB.
Then run search-replace:
1. Replace local domain with staging domain
2. Replace production domain with staging domain

WP-CLI sequence (inside public_html):
wp search-replace 'http://dani-backup.local' 'https://v2vvroh9bv-staging.onrocket.site' --all-tables --precise
wp search-replace 'https://danielamontespsic.com' 'https://v2vvroh9bv-staging.onrocket.site' --all-tables --precise
wp search-replace 'http://danielamontespsic.com' 'https://v2vvroh9bv-staging.onrocket.site' --all-tables --precise
wp option update home 'https://v2vvroh9bv-staging.onrocket.site'
wp option update siteurl 'https://v2vvroh9bv-staging.onrocket.site'

## 5) Isolation hardening (must pass)
1. noindex active:
   - wp option get blog_public should be 0
2. Outgoing email blocked by MU plugin
3. WooCommerce webhooks blocked by MU plugin
4. Payment gateways in test mode or disabled
5. Disable or pause cron jobs that should not run in staging

## 6) Validation checklist
1. Home loads with staging URLs only
2. Checkout renders but does not trigger real side effects
3. Tutor quickfix behavior still OK
4. No hardcoded local/prod links in child theme templates

## 7) Rollback
If validation fails:
1. Re-deploy previous staging files backup
2. Re-import previous staging DB backup
3. Re-run URL sanity checks

## 8) Execution log (fill during reconnect session)
- Date/time:
- Access method used (SFTP/SSH):
- Files deployed:
- DB imported from:
- Search-replace commands executed:
- Guardrails verification result:
- QA checklist result:
- Final status (DONE/BLOCKED):
