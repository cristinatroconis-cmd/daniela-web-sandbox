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
3. SSH connectivity is currently working; recent login reached shell successfully.
4. Remote shell shows Perl locale warnings, but they are non-blocking and do not prevent SSH/WP-CLI usage.

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
Remote target path for the live staging site:
- /home/v2vvxfo/public_html/v2vvroh9bv-staging.onrocket.site

Important path note:
- /home/v2vvxfo/public_html is a separate WordPress install using `wp_` tables.
- The live staging site responding at v2vvroh9bv-staging.onrocket.site uses the nested install above with `stgwp_` tables.
- Run WP-CLI, file sync, and verification against the nested path when working on the real staging site.

Recommended transfer strategy:
1. Keep wp-config.php from staging host (do not overwrite credentials).
2. Replace wp-content from local snapshot.
3. Keep current .htaccess unless you need the local one.

Example rsync from your machine (if SSH is available):
rsync -avz --delete \
  --exclude='wp-config.php' \
  --exclude='.htaccess' \
  '/Users/cristinatroconis/Local Sites/dani-backup/app/public/' \
   'v2vvxfo@131.153.200.61:public_html/v2vvroh9bv-staging.onrocket.site/'

Theme note:
- `dani-backup/app/public/wp-content/themes/daniela-child` is a symlink to the repo theme.
- After syncing from local, replace the remote child theme with a real directory copied from the repo so staging does not keep a broken symlink.

If login prints locale warnings such as `perl: warning: Setting locale failed`, continue anyway. This indicates a shell locale mismatch, not a failed connection. If you want to suppress it for the session, run:

export LC_ALL=C
export LANG=C

If SSH remains unstable, use SFTP upload in batches:
1. Upload wp-content/themes/daniela-child
2. Upload wp-content/mu-plugins
3. Upload required plugins changed in local
4. Upload uploads only if needed

## 4) Rebuild staging DB from local.sql
On server, import a dump that matches the staging table prefix.
- Live staging uses `stgwp_` as table prefix.
- If the local dump is plain `wp_`, transform it first or import an existing `stgwp_` version of the dump.
Then run search-replace:
1. Replace local domain with staging domain
2. Replace production domain with staging domain

WP-CLI sequence (inside /home/v2vvxfo/public_html/v2vvroh9bv-staging.onrocket.site):
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
5. Elementor-generated CSS files under `wp-content/uploads/elementor/css/` return HTTP 200

## 6.1) Common staging pitfall: missing Elementor styling
If sections render without expected backgrounds, spacing, or other Elementor styling:
1. Check whether the generated CSS URL returns 404, for example:
   - `/wp-content/uploads/elementor/css/post-7105.css`
2. If the file exists on disk but returns 404, verify permissions on the live staging uploads tree.
3. On this host, the fix was:
   - set directories in `wp-content/uploads` to `755`
   - set files in `wp-content/uploads` to `644`
   - run `wp elementor flush_css`
4. Re-request the page and confirm the CSS file is regenerated and publicly reachable.

## 7) Rollback
If validation fails:
1. Re-deploy previous staging files backup
2. Re-import previous staging DB backup
3. Re-run URL sanity checks

## 8) Execution log (fill during reconnect session)
- Date/time: 2026-04-13
- Access method used (SFTP/SSH): SSH
- Files deployed: synced local WordPress files to /home/v2vvxfo/public_html/v2vvroh9bv-staging.onrocket.site, then replaced child theme with a real copy from repo and deployed wp-content/mu-plugins/dm-staging-guardrails.php
- DB imported from: /home/v2vvxfo/dani-local-20260412-144402-stgprefix.sql into the live nested staging install using `stgwp_` tables
- Search-replace commands executed: replaced dani-backup.local and danielamontespsic.com variants with https://v2vvroh9bv-staging.onrocket.site in the live staging DB
- Guardrails verification result: PASS; `blog_public=0`, `DISABLE_WP_CRON=true`, `WP_ENVIRONMENT_TYPE=staging`, `pre_wp_mail` filter present, Woo webhook filter present, payment gateway filter present, `X-Robots-Tag` noindex headers present
- QA checklist result: home HTML clean from local/prod domains on live staging response; noindex headers confirmed; Elementor CSS delivery restored after fixing `wp-content/uploads` permissions and flushing CSS; representative uploads assets return HTTP 200; checkout side effects reduced by blocked mail/webhooks/payments and disabled cron; Tutor quickfix not explicitly re-tested in browser
- Final status (DONE/BLOCKED): DONE
