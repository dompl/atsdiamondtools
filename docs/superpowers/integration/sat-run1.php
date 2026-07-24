<?php
/* Channel-ON integration test: signed request + stub ruleset (15% charm). */
$key='test'; $secret='harness-secret'; $ts=time(); $body='';
$_SERVER['HTTP_X_EA_CHANNEL']   = $key;
$_SERVER['HTTP_X_EA_TIMESTAMP'] = (string)$ts;
$_SERVER['HTTP_X_EA_SIGN']      = hash_hmac('sha256', "$key\n$ts\n$body", $secret);
add_filter('skylinewp_child_satellite_channels', function() use($key,$secret){ return [$key=>['secret'=>$secret,'label'=>'Harness']]; });

$dir='/var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools/wp-content/themes/skylinewp-dev-child/src/functions/woocommerce/';
foreach(['satellite-pricing-engine','satellite-channel','satellite-ruleset','satellite-pricing','satellite-restrictions'] as $f) require_once $dir.$f.'.php';

$ruleset=['version'=>1,'markup'=>['type'=>'percent','value'=>1500,'maxUplift'=>5000,'rounding'=>'charm'],
          'followSales'=>true,'coupons'=>['ats10','free24'],'excluded'=>[197671],'freeShipping'=>true,'hiddenMethods'=>[]];
set_transient('sat_ruleset_'.$key, $ruleset, 300);

skylinewp_child_satellite_register_pricing();
skylinewp_child_satellite_register_restrictions();

$fail=0;$n=0;
function chk($l,$got,$want){$GLOBALS["n"]++;if($got===$want){printf("ok   %-46s %s\n",$l,json_encode($got));}else{$GLOBALS["fail"]++;printf("FAIL %-46s got %s want %s\n",$l,json_encode($got),json_encode($want));}}
function near($a,$b){ return abs((float)$a-(float)$b) < 0.005; }
$GLOBALS['n']=0; $GLOBALS['fail']=0;

chk('channel recognised from signed request', skylinewp_child_satellite_current_channel()['key'], 'test');

$exp = skylinewp_child_satellite_price_one(15500,null,true,$ruleset['markup'])['charged']; // 17799
chk('engine expects £155 -> 177.99', $exp, 17799);

$p = wc_get_product(197670); // £155, not excluded
chk('LIVE get_price marked up (£177.99)', near($p->get_price(), 177.99), true);
chk('LIVE get_regular_price marked up',   near($p->get_regular_price(), 177.99), true);
chk('LIVE not 155 anymore',               near($p->get_price(), 155.00), false);
chk('LIVE 197670 purchasable',            $p->is_purchasable(), true);

$e = wc_get_product(197671); // £35, excluded in ruleset
chk('LIVE excluded 197671 not purchasable', $e->is_purchasable(), false);
chk('LIVE excluded 197671 price NOT marked', near($e->get_price(), 35.00), true);

$c1=new WC_Coupon(); $c1->set_code('mb20');   // not allowlisted
chk('LIVE non-allowlisted coupon rejected', apply_filters('woocommerce_coupon_is_valid', true, $c1), false);
$c2=new WC_Coupon(); $c2->set_code('ats10');  // allowlisted
chk('LIVE allowlisted coupon accepted',     apply_filters('woocommerce_coupon_is_valid', true, $c2), true);

delete_transient('sat_ruleset_'.$key);
delete_option('sat_ruleset_lkg_'.$key);
echo "\n{$GLOBALS['n']} checks, {$GLOBALS['fail']} failed\n";
