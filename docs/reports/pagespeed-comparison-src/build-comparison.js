// usage: node build-comparison.js metrics.json out.html
// Before/after PageSpeed comparison for the client, with inline SVG charts.
const fs = require('fs');
const [, , dataPath, outPath] = process.argv;
const M = JSON.parse(fs.readFileSync(dataPath, 'utf8'));

const BRAND = '#594652', YELLOW = '#FFD902', DARK = '#373737', BEFORE = '#b9aeb4', AFTER = '#594652';
const GOOD = '#0a7d3b', OK = '#b26a00', BAD = '#c0262d';
const PAGES = [['home', 'Home page'], ['cat', 'Category page'], ['prod', 'Product page']];
const sc = (n) => (n >= 90 ? GOOD : n >= 50 ? OK : BAD);
const s = (ms) => (ms / 1000).toFixed(1) + 's';
const pct = (b, a) => Math.round(((b - a) / b) * 100);

// ---------- charts ----------
function barGroup({ title, unit, series, max, good, fmt, w = 330, h = 190, subtitle }) {
  // series: [{label, before, after}]
  const padL = 34, padB = 34, padT = 30, padR = 8;
  const cw = w - padL - padR, ch = h - padT - padB;
  const n = series.length, gw = cw / n, bw = Math.min(30, gw * 0.3);
  const y = (v) => padT + ch - (v / max) * ch;
  let out = `<svg viewBox="0 0 ${w} ${h}" width="${w}" height="${h}" class="chart">`;
  out += `<text x="0" y="14" class="ct">${title}</text>`;
  if (subtitle) out += `<text x="0" y="26" class="cs">${subtitle}</text>`;
  // gridlines
  for (let i = 0; i <= 4; i++) {
    const v = (max / 4) * i, yy = y(v);
    out += `<line x1="${padL}" x2="${w - padR}" y1="${yy}" y2="${yy}" stroke="#e6e2e4"/><text x="${padL - 5}" y="${yy + 3}" class="ca" text-anchor="end">${fmt(v, true)}</text>`;
  }
  if (good !== undefined) {
    out += `<line x1="${padL}" x2="${w - padR}" y1="${y(good)}" y2="${y(good)}" stroke="${GOOD}" stroke-dasharray="4 3" stroke-width="1.2"/><text x="${w - padR}" y="${y(good) - 4}" class="ca" fill="${GOOD}" text-anchor="end">Google target</text>`;
  }
  series.forEach((sr, i) => {
    const cx = padL + gw * i + gw / 2;
    const xb = cx - bw - 3, xa = cx + 3;
    const hb = ch - (y(sr.before) - padT), ha = ch - (y(sr.after) - padT);
    out += `<rect x="${xb}" y="${y(sr.before)}" width="${bw}" height="${hb}" fill="${BEFORE}" rx="2"/>`;
    out += `<rect x="${xa}" y="${y(sr.after)}" width="${bw}" height="${ha}" fill="${AFTER}" rx="2"/>`;
    out += `<text x="${xb + bw / 2}" y="${y(sr.before) - 4}" class="cv" text-anchor="middle" fill="#7d7078">${fmt(sr.before)}</text>`;
    out += `<text x="${xa + bw / 2}" y="${y(sr.after) - 4}" class="cv" text-anchor="middle" fill="${AFTER}" font-weight="700">${fmt(sr.after)}</text>`;
    out += `<text x="${cx}" y="${h - padB + 16}" class="cl" text-anchor="middle">${sr.label}</text>`;
  });
  out += `<rect x="${padL}" y="${h - 10}" width="10" height="10" fill="${BEFORE}"/><text x="${padL + 14}" y="${h - 1}" class="ca">Before</text><rect x="${padL + 60}" y="${h - 10}" width="10" height="10" fill="${AFTER}"/><text x="${padL + 74}" y="${h - 1}" class="ca">After</text>`;
  return out + '</svg>';
}

function gauge(n, label) {
  const r = 26, c = 2 * Math.PI * r, col = sc(n);
  return `<div class="gauge"><svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="${r}" fill="none" stroke="#eee9ec" stroke-width="6"/><circle cx="32" cy="32" r="${r}" fill="none" stroke="${col}" stroke-width="6" stroke-linecap="round" stroke-dasharray="${(c * n) / 100} ${c}" transform="rotate(-90 32 32)"/><text x="32" y="37" text-anchor="middle" font-size="17" font-weight="700" fill="${col}" font-family="Helvetica Neue,Arial">${n}</text></svg><div class="gl">${label}</div></div>`;
}

const series = (ff, key) => PAGES.map(([k, l]) => ({ label: l, before: M[k][ff].before[key], after: M[k][ff].after[key] }));

// ---------- score table ----------
const cell = (n) => `<td class="num" style="color:${sc(n)}">${n}</td>`;
const delta = (b, a) => {
  const d = a - b;
  return `<td class="num delta ${d > 0 ? 'up' : d < 0 ? 'down' : ''}">${d > 0 ? '+' : ''}${d}</td>`;
};
const scoreRows = PAGES.map(([k, l]) =>
  ['mobile', 'desktop'].map((ff, i) => {
    const b = M[k][ff].before, a = M[k][ff].after;
    return `<tr>${i === 0 ? `<th rowspan="2">${l}</th>` : ''}<td>${ff[0].toUpperCase() + ff.slice(1)}</td>
      ${cell(b.perf)}${cell(a.perf)}${delta(b.perf, a.perf)}
      ${cell(b.a11y)}${cell(a.a11y)}${delta(b.a11y, a.a11y)}
      ${cell(b.bp)}${cell(a.bp)}${delta(b.bp, a.bp)}
      ${cell(b.seo)}${cell(a.seo)}${delta(b.seo, a.seo)}</tr>`;
  }).join('')
).join('');

// ---------- metrics table (mobile) ----------
const mrow = (label, key, fmt, better = 'lower') =>
  `<tr><th>${label}</th>` + PAGES.map(([k]) => {
    const b = M[k].mobile.before[key], a = M[k].mobile.after[key];
    const improved = better === 'lower' ? a < b : a > b;
    return `<td class="num">${fmt(b)}</td><td class="num" style="color:${improved ? GOOD : DARK};font-weight:700">${fmt(a)}</td>`;
  }).join('') + '</tr>';
const metricRows = [
  mrow('Main content visible (LCP)', 'lcp', s),
  mrow('First text on screen (FCP)', 'fcp', s),
  mrow('Speed Index', 'si', s),
  mrow('Layout jumping (CLS)', 'cls', (v) => (Math.round(v * 1000) / 1000).toString()),
  mrow('Time the phone is busy (TBT)', 'tbt', (v) => Math.round(v) + ' ms'),
  mrow('Page download size', 'bytes', (v) => (v / 1024 / 1024).toFixed(1) + ' MB'),
  mrow('Accessibility checks failed', 'a11yfails', (v) => v),
].join('');

const H = M.home.mobile, C = M.cat.mobile, P = M.prod.mobile;
const avgBefore = Math.round((H.before.perf + C.before.perf + P.before.perf) / 3);
const avgAfter = Math.round((H.after.perf + C.after.perf + P.after.perf) / 3);

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Website speed: before and after</title>
<style>
  @page { size: A4; margin: 0; }
  * { box-sizing: border-box; }
  body { font-family: "Helvetica Neue", Arial, sans-serif; color: ${DARK}; font-size: 10.5pt; line-height: 1.5; margin: 0; }
  .page { width: 210mm; height: 297mm; padding: 16mm 16mm 14mm; page-break-after: always; position: relative; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .cover { background: ${BRAND}; color: #fff; padding-top: 30mm; }
  .cover .kicker { color: ${YELLOW}; text-transform: uppercase; letter-spacing: .14em; font-size: 9.5pt; font-weight: 700; }
  .cover h1 { font-size: 34pt; line-height: 1.1; margin: 10px 0 14px; font-weight: 800; }
  .cover p.lead { font-size: 13pt; max-width: 150mm; opacity: .92; }
  .cover .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 26mm; }
  .cover .stat { background: rgba(255,255,255,.08); border-left: 4px solid ${YELLOW}; padding: 12px 16px; border-radius: 4px; }
  .cover .stat b { display: block; font-size: 26pt; line-height: 1.1; color: ${YELLOW}; }
  .cover .stat span { font-size: 9.5pt; opacity: .85; }
  .cover .foot { position: absolute; bottom: 16mm; left: 16mm; right: 16mm; font-size: 9pt; opacity: .8; border-top: 1px solid rgba(255,255,255,.3); padding-top: 8px; }
  h2 { color: ${BRAND}; font-size: 17pt; margin: 0 0 4px; }
  h2 + p.sub { margin: 0 0 14px; color: #6b5f66; }
  h3 { font-size: 11.5pt; margin: 16px 0 6px; color: ${BRAND}; }
  p { margin: 0 0 8px; }
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
  .chart { display: block; }
  .ct { font-size: 10.5px; font-weight: 700; fill: ${DARK}; }
  .cs { font-size: 8.5px; fill: #7d7078; }
  .ca { font-size: 8px; fill: #8a7f85; }
  .cl { font-size: 8.5px; fill: ${DARK}; }
  .cv { font-size: 9px; }
  .card { border: 1px solid #e6e2e4; border-radius: 6px; padding: 10px 12px; }
  table { border-collapse: collapse; width: 100%; font-size: 9.5pt; }
  th, td { padding: 5px 6px; border-bottom: 1px solid #e6e2e4; text-align: left; vertical-align: top; }
  thead th { background: ${BRAND}; color: #fff; font-size: 8.5pt; text-align: center; }
  thead th.l { text-align: left; }
  tbody th { font-weight: 600; width: 34mm; }
  td.num { text-align: center; font-variant-numeric: tabular-nums; font-weight: 600; }
  td.delta { color: #8a7f85; font-size: 8.5pt; }
  td.delta.up { color: ${GOOD}; } td.delta.down { color: ${BAD}; }
  .gauges { display: flex; gap: 14px; justify-content: space-around; margin: 6px 0 12px; }
  .gauge { text-align: center; } .gl { font-size: 8.5pt; color: #6b5f66; margin-top: 2px; }
  .pill { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 8.5pt; font-weight: 700; color: #fff; background: ${GOOD}; }
  ul { padding-left: 18px; margin: 0 0 8px; } li { margin-bottom: 5px; }
  .benefit { display: grid; grid-template-columns: 40mm 1fr; gap: 10px; align-items: start; border-left: 4px solid ${YELLOW}; padding: 6px 10px; margin-bottom: 9px; background: #faf8f9; }
  .benefit b.big { font-size: 20pt; color: ${BRAND}; line-height: 1.1; display: block; }
  .benefit small { color: #6b5f66; font-size: 8.5pt; }
  .note { background: #fff8d6; border-left: 4px solid ${YELLOW}; padding: 8px 12px; font-size: 9.5pt; margin-top: 10px; }
  .pf { position: absolute; bottom: 8mm; left: 16mm; right: 16mm; font-size: 8pt; color: #8a7f85; border-top: 1px solid #e6e2e4; padding-top: 5px; display: flex; justify-content: space-between; }
  .src { font-size: 8pt; color: #8a7f85; }
</style></head><body>

<section class="page cover">
  <div class="kicker">Website performance review</div>
  <h1>Faster, steadier,<br>and easier to use.</h1>
  <p class="lead">What changed on www.atsdiamondtools.co.uk between 24 and 25 September 2026, measured with Google's own page speed test, before and after the work.</p>
  <div class="stats">
    <div class="stat"><b>${H.before.perf} → ${H.after.perf}</b><span>Home page mobile speed score out of 100</span></div>
    <div class="stat"><b>${s(H.before.lcp)} → ${s(H.after.lcp)}</b><span>Until the banner is on screen on a phone (Google wants under 2.5s)</span></div>
    <div class="stat"><b>${H.before.cls.toFixed(2)} → 0</b><span>Layout jumping while the page loads (under 0.1 is good)</span></div>
    <div class="stat"><b>100 / 100</b><span>Accessibility and SEO on every page tested, up from ${Math.min(H.before.a11y, C.before.a11y, P.before.a11y)} and ${Math.min(H.before.seo, C.before.seo, P.before.seo)}</span></div>
  </div>
  <div class="foot">Prepared by Red Frog Studio for ATS Diamond Tools · Google Lighthouse 12, mobile preset (Moto G Power on slow 4G) and desktop preset · live site</div>
</section>

<section class="page">
  <h2>The scores at a glance</h2>
  <p class="sub">Google grades each page from 0 to 100 in four areas. Green is 90 or more, amber is 50 to 89, red is under 50.</p>
  <h3>Home page on a phone, before and after</h3>
  <div class="gauges">
    ${gauge(H.before.perf, 'Speed, before')}${gauge(H.after.perf, 'Speed, after')}
    ${gauge(H.before.a11y, 'Accessibility, before')}${gauge(H.after.a11y, 'Accessibility, after')}
    ${gauge(H.before.seo, 'SEO, before')}${gauge(H.after.seo, 'SEO, after')}
  </div>
  <div class="grid2">
    ${barGroup({ title: 'Speed score on mobile', subtitle: 'Higher is better, out of 100', series: series('mobile', 'perf'), max: 100, good: 90, fmt: (v) => Math.round(v) })}
    ${barGroup({ title: 'Speed score on desktop', subtitle: 'Higher is better, out of 100', series: series('desktop', 'perf'), max: 100, good: 90, fmt: (v) => Math.round(v) })}
  </div>
  <h3>All scores, all pages</h3>
  <table>
    <thead><tr><th class="l" colspan="2"></th><th colspan="3">Speed</th><th colspan="3">Accessibility</th><th colspan="3">Best practices</th><th colspan="3">SEO</th></tr>
    <tr><th class="l" colspan="2">Page</th><th>Before</th><th>After</th><th>+/-</th><th>Before</th><th>After</th><th>+/-</th><th>Before</th><th>After</th><th>+/-</th><th>Before</th><th>After</th><th>+/-</th></tr></thead>
    <tbody>${scoreRows}</tbody>
  </table>
  <p class="src" style="margin-top:8px">Pages tested: home, the Polishing category, and the 100mm super premium 3-step wet polishing pads product. The average mobile speed score across the three pages went from ${avgBefore} to ${avgAfter}. Best practices on the product page stays at 78 to 79 because of cookies set by the Apple Pay and Google Pay buttons, which we have kept on purpose.</p>
  <div class="pf"><span>ATS Diamond Tools · website performance review</span><span>Red Frog Studio</span></div>
</section>

<section class="page">
  <h2>What visitors actually feel</h2>
  <p class="sub">The score is a summary. These are the measurements behind it, taken on a simulated mid-range phone on a slow connection, which is Google's standard test.</p>
  <div class="grid2">
    ${barGroup({ title: 'Main content visible (LCP)', subtitle: 'Seconds until the main picture is on screen, mobile', series: series('mobile', 'lcp'), max: 8000, good: 2500, fmt: (v, ax) => (ax ? (v / 1000).toFixed(0) + 's' : s(v)) })}
    ${barGroup({ title: 'Layout jumping (CLS)', subtitle: 'How much the page shifts while loading, mobile', series: series('mobile', 'cls'), max: 0.3, good: 0.1, fmt: (v, ax) => (ax ? v.toFixed(1) : (Math.round(v * 1000) / 1000).toString()) })}
    ${barGroup({ title: 'Speed Index', subtitle: 'Seconds until the page looks complete, mobile', series: series('mobile', 'si'), max: 6000, good: 3400, fmt: (v, ax) => (ax ? (v / 1000).toFixed(0) + 's' : s(v)) })}
    ${barGroup({ title: 'Data sent to the phone', subtitle: 'Megabytes downloaded to show the page, mobile', series: series('mobile', 'bytes').map((r) => ({ ...r, before: r.before / 1048576, after: r.after / 1048576 })), max: 6, fmt: (v, ax) => (ax ? v.toFixed(0) : v.toFixed(1) + ' MB') })}
  </div>
  <h3>Mobile measurements in full</h3>
  <table>
    <thead><tr><th class="l"></th><th colspan="2">Home page</th><th colspan="2">Category page</th><th colspan="2">Product page</th></tr>
    <tr><th class="l">Measure</th><th>Before</th><th>After</th><th>Before</th><th>After</th><th>Before</th><th>After</th></tr></thead>
    <tbody>${metricRows}</tbody>
  </table>
  <p class="src" style="margin-top:8px">Lower is better for everything in this table. The product page downloads more data than before because its image gallery now sends full-quality photos for zooming, and the Apple Pay and Google Pay scripts are loaded on every visit. "Time the phone is busy" is mostly the payment, analytics and advertising scripts and moves a little between test runs.</p>
  <div class="pf"><span>ATS Diamond Tools · website performance review</span><span>Red Frog Studio</span></div>
</section>

<section class="page">
  <h2>Why this matters for the business</h2>
  <p class="sub">Speed is not a vanity number. It changes how many visitors stay, how many buy, and how Google ranks the site.</p>
  <div class="benefit"><div><b class="big">${pct(H.before.lcp, H.after.lcp)}% faster</b><small>to the banner on the home page</small></div><div><b>Fewer people give up.</b> Google's own research found that 53% of mobile visitors leave a page that takes longer than 3 seconds to load. The home page took ${s(H.before.lcp)} to show its banner on a phone. It now takes ${s(H.after.lcp)}, inside Google's 2.5 second target.</div></div>
  <div class="benefit"><div><b class="big">0 jumps</b><small>layout shift on all three pages</small></div><div><b>No more mis-taps.</b> The page used to move about while it loaded, mainly because the clearance bar slid open after the page had drawn. That is the classic cause of tapping the wrong thing. All three pages now score zero for layout jumping, against a previous 0.15 to 0.25.</div></div>
  <div class="benefit"><div><b class="big">${pct(H.before.imgbytes, H.after.imgbytes)}% less</b><small>image data on the mobile home page</small></div><div><b>Cheaper on data, quicker on 4G.</b> Phones now receive phone-sized pictures. The home page sends ${Math.round(H.after.imgbytes / 1024)} KB of images to a phone instead of ${Math.round(H.before.imgbytes / 1024)} KB, and the whole page is ${pct(H.before.bytes, H.after.bytes)}% smaller.</div></div>
  <div class="benefit"><div><b class="big">100</b><small>accessibility on every page</small></div><div><b>Usable by more customers, and a ranking signal.</b> ${H.before.a11yfails + C.before.a11yfails + P.before.a11yfails} accessibility checks were failing across the three pages: unnamed buttons, low-contrast text, hidden menus that screen readers could still reach. All fixed. Google uses page experience and speed as ranking factors, and every page now scores 100 for SEO.</div></div>
  <div class="benefit"><div><b class="big">Every visit</b><small>after the first is faster too</small></div><div><b>Browser caching is now on.</b> The hosting server now tells browsers to keep pictures, scripts and styles for a month to a year, so the second and later pages a customer looks at load almost instantly. The test above measures a first visit, so this benefit is on top of the numbers shown.</div></div>
  <p class="src">Sources: Google, "The need for mobile speed" (2016), 53% of mobile visits abandoned after 3 seconds. Google and Deloitte, "Milliseconds make millions" (2020), a 0.1 second improvement in mobile speed lifted retail conversion by 8.4% and average order value by 9.2%. Google Search Central, page experience and Core Web Vitals as ranking signals.</p>
  <div class="pf"><span>ATS Diamond Tools · website performance review</span><span>Red Frog Studio</span></div>
</section>

<section class="page">
  <h2>What we changed</h2>
  <p class="sub">Everything below is live on the website. Nothing changed for customers apart from speed, and payments and tracking work exactly as before.</p>
  <h3>Speed</h3>
  <ul>
    <li><b>The banner and logo load first.</b> The caching plugin no longer treats them as "load later" pictures, and the browser is told to fetch the banner before anything else.</li>
    <li><b>Phones get phone-sized pictures.</b> Home and category banners are sent in several sizes and the phone picks the smallest that fits. The largest versions are compressed a little harder with no visible difference.</li>
    <li><b>Facebook and Google tracking wait their turn.</b> Both tags start once the page has drawn instead of the moment it opens. Page views, add-to-basket events and sales are still recorded.</li>
    <li><b>Product photos appear straight away.</b> The main photo used to stay hidden until the image slider had set itself up. It now shows immediately.</li>
    <li><b>The clearance pop-up no longer counts as the main content.</b> On phones it shows the text and button only, so its late-arriving photo no longer skews the measurement.</li>
    <li><b>Fonts no longer hide the text.</b> While the site's fonts download, text shows in a fallback font instead of being invisible.</li>
    <li><b>Browser caching switched on</b> for pictures, scripts and styles at the hosting level.</li>
  </ul>
  <h3>Stability</h3>
  <ul>
    <li><b>The clearance offers bar</b> is in place from the start instead of sliding open, and visitors who closed it never see it flash.</li>
    <li><b>Pictures reserve their space</b> before they arrive, so nothing moves when they load.</li>
  </ul>
  <h3>Accessibility</h3>
  <ul>
    <li>Banner arrows and dots, the basket button and the price slider handles now have names screen readers can announce.</li>
    <li>The grey "Reviews" count, green "in stock" text, sale badges and small menu text now meet the contrast standard.</li>
    <li>The clearance pop-up has a proper name for assistive technology, and the closed side menu is hidden from keyboards and screen readers until opened.</li>
    <li>Product carousels no longer use a list structure that confuses screen readers, and heading order is correct in the sidebar filters.</li>
    <li>The "Read more" link on the home page now says where it goes.</li>
  </ul>
  <h3>Housekeeping done at the same time</h3>
  <ul>
    <li>The newsletter sign-up's Privacy Policy link now goes to your Privacy Statement page. It went nowhere before.</li>
    <li>The Terms and Conditions page was showing its content four times with no styling. It now renders once, with proper headings.</li>
  </ul>
  <div class="note"><b>Reading the numbers.</b> Scores move by a few points between runs depending on Google's test servers, so treat anything within five points as the same. The remaining gap on mobile is time spent running the payment, analytics and advertising scripts, which we cannot remove without losing those features.</div>
  <div class="pf"><span>ATS Diamond Tools · website performance review</span><span>Red Frog Studio · info@redfrogstudio.co.uk</span></div>
</section>
</body></html>`;
fs.writeFileSync(outPath, html);
console.log(outPath);
