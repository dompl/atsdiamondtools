<?php
/* Channel-OFF inertness: no headers -> nothing changes. */
$dir='/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child/src/functions/woocommerce/';
foreach(['satellite-pricing-engine','satellite-channel','satellite-ruleset','satellite-pricing','satellite-restrictions'] as $f) require_once $dir.$f.'.php';
skylinewp_child_satellite_register_pricing();
skylinewp_child_satellite_register_restrictions();
$fail=0;$n=0;
function chk($l,$got,$want){$GLOBALS["n"]++;if($got===$want){printf("ok   %-46s %s\n",$l,json_encode($got));}else{$GLOBALS["fail"]++;printf("FAIL %-46s got %s want %s\n",$l,json_encode($got),json_encode($want));}}
function near($a,$b){ return abs((float)$a-(float)$b) < 0.005; }
$GLOBALS['n']=0; $GLOBALS['fail']=0;
chk('no channel resolved', skylinewp_child_satellite_current_channel(), null);
$p = wc_get_product(197670);
chk('price UNCHANGED at £155', near($p->get_price(), 155.00), true);
chk('purchasable unaffected',  $p->is_purchasable(), true);
echo "\n{$GLOBALS['n']} checks, {$GLOBALS['fail']} failed\n";
