# Browser caching for static files (Plesk / nginx)

Production is served by nginx via Plesk, which hands out images, fonts, CSS
and JS directly. Apache `.htaccess` `Expires` rules are therefore ignored
and Lighthouse reports "Serve static assets with an efficient cache policy"
on every page. The theme cannot change this (no sudo on the box); it is a
one-off change in the Plesk control panel.

## Where

Plesk > Websites & Domains > atsdiamondtools.co.uk > **Apache & nginx Settings**

## What to paste into "Additional nginx directives"

```nginx
location ~* \.(?:css|js|mjs)$ {
    expires 30d;
    add_header Cache-Control "public, max-age=2592000";
    access_log off;
}

location ~* \.(?:jpg|jpeg|png|gif|webp|avif|svg|ico|woff|woff2|ttf|otf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, max-age=31536000, immutable";
    access_log off;
}
```

Leave "Serve static files directly by nginx" ticked. Click **OK / Apply**.
No restart is needed; Plesk reloads nginx.

## Check it worked

```bash
curl -sI https://www.atsdiamondtools.co.uk/wp-content/uploads/2026/09/Top-logo-65x.webp | grep -i cache-control
```

Should print `Cache-Control: public, max-age=31536000, immutable`.

## Caveat

Theme bundle files (`bundle.js`, `build.css`) have no cache-busting query
string, so after a theme deploy visitors may keep the old CSS/JS for up to
30 days. Purge WP Rocket after each deploy, and if a visual change must show
immediately, bump the file name or add a version query in `enqueue.php`.
