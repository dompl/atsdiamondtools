# Satellite bridge — live integration harness

Runs the ATS-side bridge against a live WooCommerce to verify the hook wiring
(markup application, restrictions) that the standalone unit checks can't reach.
These are dev harnesses, not shipped code.

```bash
cd /path/to/wordpress            # the atsdiamondtools install
wp eval-file <theme>/docs/superpowers/integration/sat-run1.php   # channel ON
wp eval-file <theme>/docs/superpowers/integration/sat-run2.php   # channel OFF (inert)
```

`sat-run1.php` signs a request for a `test` channel, injects a stub 15% / .99
ruleset (excluding one product), then asserts a real product is marked up through
`get_price`, an excluded product is non-purchasable and unmarked, and the coupon
allowlist bites. `sat-run2.php` proves normal traffic is untouched. Both clean up
their transient/option afterwards. Last run: all assertions ok.
