<?php
/**
 * Review Generator — Text engine (Plan A)
 *
 * Combinatorial fragment library. Composes varied, human-looking review text:
 * short one-liners through to multi-sentence reviews, category-aware flavour,
 * occasional product-name mentions, and light typo injection. No emojis.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map a product to a flavour key from its categories / title keywords.
 *
 * @param WC_Product $product Product.
 * @return string One of: blade, core, drill, grind, saw, hand, general.
 */
function ats_reviews_flavour_for_product( $product ) {
	$haystack = strtolower( $product->get_name() );
	$terms    = get_the_terms( $product->get_id(), 'product_cat' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $t ) {
			$haystack .= ' ' . strtolower( $t->name );
		}
	}

	$map = array(
		'blade' => array( 'blade', 'cutting disc', 'cut off' ),
		'core'  => array( 'core', 'core bit', 'core drill' ),
		'grind' => array( 'grind', 'grinding', 'cup wheel', 'polish' ),
		'drill' => array( 'drill bit', 'sds', 'auger', 'masonry bit' ),
		'saw'   => array( 'saw', 'chainsaw', 'circular' ),
		'hand'  => array( 'chisel', 'trowel', 'hand tool', 'bolster', 'float' ),
	);

	foreach ( $map as $flavour => $needles ) {
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				return $flavour;
			}
		}
	}
	return 'general';
}

/**
 * Short one-liner reviews (5-star).
 *
 * @return array
 */
function ats_reviews_oneliners_five() {
	return array(
		'Great product', 'Excellent quality', 'Does exactly what it says', 'Spot on',
		'Brilliant, no complaints', 'Top quality, would buy again', 'Cracking bit of kit',
		'Really pleased with this', 'Exactly as described', 'Fast delivery and great quality',
		'Best one I have used', 'Superb value for money', 'Highly recommended', 'Faultless',
		'Quality tool at a fair price', 'Does the job perfectly', 'Cannot fault it',
		'Works a treat', 'Very happy with it', 'Will be ordering again', 'First class',
		'Proper professional kit', 'Five stars from me', 'Great service, great product',
		'Exactly what I needed', 'Solid bit of kit', 'Better than expected', 'Lovely job',
	);
}

/**
 * Short one-liner reviews (4-star, with a tiny niggle).
 *
 * @return array
 */
function ats_reviews_oneliners_four() {
	return array(
		'Good product, delivery was a bit slow', 'Very good but a touch pricey',
		'Does the job, packaging could be better', 'Happy with it overall',
		'Good quality, would have liked more in the pack', 'Solid product, nothing to complain about really',
		'Works well, took a couple of days to arrive', 'Decent kit for the money',
		'Pleased with it, just wish it came with instructions', 'Good but not perfect',
	);
}

/**
 * Openers.
 *
 * @return array
 */
function ats_reviews_openers() {
	return array(
		'Ordered this for a job last week and', 'Bought this to replace an old one and',
		'Used it on site the other day and', 'Been using these for years now and',
		'First time trying this brand and', 'Picked one of these up and',
		'Got this delivered next day and', 'Needed something reliable and',
		'Used it over the weekend and', 'Tried it out on a big job and',
		'My old one finally gave up so I got this and', 'Bought a couple of these and',
		'Turned up quickly and', 'Had my doubts at first but',
	);
}

/**
 * General body fragments.
 *
 * @return array
 */
function ats_reviews_bodies_general() {
	return array(
		'it has held up really well', 'the quality is spot on', 'it does exactly what I needed',
		'it feels well made and solid', 'it has not let me down once', 'it was well worth the money',
		'it performs better than the more expensive ones I have tried', 'it is built to last',
		'it made the job so much easier', 'the finish is excellent', 'it works just as well as the big brands',
		'I was impressed with how well it performed', 'it is exactly what a professional needs',
	);
}

/**
 * Flavour-specific body fragments.
 *
 * @param string $flavour Flavour key.
 * @return array
 */
function ats_reviews_bodies_flavour( $flavour ) {
	$pools = array(
		'blade' => array(
			'it cut through porcelain like butter', 'clean cuts with no chipping',
			'still sharp after a full day of cutting', 'it sliced through engineering bricks no problem',
			'much less dust than my old blade', 'cuts fast and stays cool',
		),
		'core'  => array(
			'it drilled clean holes through concrete', 'went through the wall with no drama',
			'the holes came out neat and to size', 'it cored through brick really cleanly',
			'no wandering, it stayed exactly where I wanted it',
		),
		'grind' => array(
			'it took the old coating off in no time', 'left a lovely smooth finish',
			'it ground the concrete back flat with ease', 'made short work of the render',
			'no clogging and it cuts fast',
		),
		'drill' => array(
			'it punched straight through the masonry', 'stayed sharp through dozens of holes',
			'no overheating even under load', 'the holes were clean and accurate',
		),
		'saw'   => array(
			'it cut through the timber cleanly', 'powered through everything I threw at it',
			'nice straight cuts every time',
		),
		'hand'  => array(
			'it feels great in the hand', 'good weight and balance to it',
			'comfortable to use all day', 'the finish on it is really good',
		),
	);
	return isset( $pools[ $flavour ] ) ? $pools[ $flavour ] : ats_reviews_bodies_general();
}

/**
 * Closers.
 *
 * @return array
 */
function ats_reviews_closers() {
	return array(
		'Would definitely buy again.', 'Highly recommend to anyone in the trade.',
		'Will be ordering more.', 'Cannot recommend it enough.', 'Very happy customer.',
		'Great value for the money.', 'Does the job and then some.', 'No complaints at all.',
		'Exactly what I was after.', 'Would buy from here again.', 'Top marks.',
		'Saved me a fortune on hire costs.', 'My go-to from now on.',
	);
}

/**
 * Product-name mention templates. {p} is replaced with a tidy product label.
 *
 * @return array
 */
function ats_reviews_name_templates() {
	return array(
		'The {p} is exactly what I needed.', 'Really impressed with the {p}.',
		'The {p} turned up quickly and works a treat.', 'Cannot fault the {p} at all.',
		'The {p} has made my job so much easier.', 'Bought the {p} and it has not let me down.',
		'The {p} is well worth the money.', 'Been using the {p} all week with no issues.',
	);
}

/**
 * Tidy a product title into something that reads naturally in a sentence.
 *
 * @param string $title Product title.
 * @return string
 */
function ats_reviews_tidy_product_label( $title ) {
	$label = wp_strip_all_tags( $title );
	$label = preg_replace( '/^(ATS|Diamond)\s+/i', '', $label );
	$words = preg_split( '/\s+/', trim( $label ) );
	if ( count( $words ) > 5 ) {
		$words = array_slice( $words, 0, 5 );
	}
	return trim( implode( ' ', $words ) );
}

/**
 * Common-misspelling swaps for light typo injection.
 *
 * @return array word => misspelling
 */
function ats_reviews_typo_swaps() {
	return array(
		'definitely'  => 'definately',
		'recommend'   => 'recomend',
		'received'    => 'recieved',
		'quality'     => 'qualty',
		'brilliant'   => 'brillant',
		'really'      => 'realy',
		'bought'      => 'bougth',
		'recommended' => 'recomended',
		'excellent'   => 'excelent',
		'arrived'     => 'arived',
		'professional'=> 'proffessional',
		'separate'    => 'seperate',
		'product'     => 'prodcut',
		'delivery'    => 'delivary',
		'perfect'     => 'perfic',
		'because'     => 'becuase',
		'better'      => 'beter',
		'through'     => 'throught',
		'cutting'     => 'cuting',
		'service'     => 'servce',
		'happy'       => 'hapy',
		'would'       => 'wuld',
		'easy'        => 'easey',
	);
}

/**
 * Inject at most one light, human-looking typo into a string.
 *
 * @param string $text Text.
 * @return string
 */
function ats_reviews_inject_typo( $text ) {
	// Sometimes one mistake, sometimes two.
	$max     = ( mt_rand( 1, 100 ) <= 40 ) ? 2 : 1;
	$applied = 0;

	$swaps = ats_reviews_typo_swaps();
	// Randomise which swaps get tried first so it is not always the same words.
	$keys = array_keys( $swaps );
	shuffle( $keys );
	foreach ( $keys as $correct ) {
		if ( $applied >= $max ) {
			break;
		}
		if ( false !== stripos( $text, $correct ) ) {
			$text = str_ireplace( $correct, $swaps[ $correct ], $text );
			$applied++;
		}
	}

	// Drop an apostrophe if we still have room.
	if ( $applied < $max ) {
		$apos = array(
			"don't" => 'dont', "it's" => 'its', "can't" => 'cant', "I've" => 'Ive',
			"didn't" => 'didnt', "doesn't" => 'doesnt', "wouldn't" => 'wouldnt',
		);
		foreach ( $apos as $correct => $wrong ) {
			if ( $applied >= $max ) {
				break;
			}
			if ( false !== strpos( $text, $correct ) ) {
				$text = str_replace( $correct, $wrong, $text );
				$applied++;
			}
		}
	}

	return $text;
}

/**
 * Apply human "couldn't be bothered" capitalisation: all-lowercase, a lowercase
 * sentence start, lowercase standalone "i", or no capital after a full stop.
 *
 * @param string $text Text.
 * @return string
 */
function ats_reviews_mangle_caps( $text ) {
	// Some people just type everything in lowercase.
	if ( mt_rand( 1, 100 ) <= 25 ) {
		return strtolower( $text );
	}

	// Otherwise apply one or two smaller slips.
	$text = lcfirst( $text );

	if ( mt_rand( 0, 1 ) ) {
		$text = preg_replace( '/\bI\b/', 'i', $text );
	}
	if ( mt_rand( 0, 1 ) ) {
		$text = preg_replace_callback(
			'/\. ([A-Z])/',
			static function ( $m ) {
				return '. ' . strtolower( $m[1] );
			},
			$text,
			1
		);
	}

	return $text;
}

/**
 * Ensure a string ends with sentence-terminating punctuation.
 *
 * @param string $text Text.
 * @return string
 */
function ats_reviews_end_sentence( $text ) {
	$text = rtrim( $text );
	if ( '' !== $text && ! preg_match( '/[.!?]$/', $text ) ) {
		$text .= '.';
	}
	return $text;
}

/**
 * Compose one review.
 *
 * @param WC_Product $product       Product.
 * @param int        $rating        4 or 5.
 * @param string     $flavour       Flavour key.
 * @param array      $config        ats_reviews_config().
 * @return string
 */
function ats_reviews_compose( $product, $rating, $flavour, array $config ) {
	$bucket = mt_rand( 1, 100 );

	if ( 4 === $rating && $bucket <= 35 ) {
		$text = ats_reviews_pick( ats_reviews_oneliners_four() );
	} elseif ( $bucket <= 25 ) {
		// Short one-liner.
		$text = ats_reviews_pick( ats_reviews_oneliners_five() );
	} else {
		$opener  = ats_reviews_pick( ats_reviews_openers() );
		$body    = ats_reviews_pick( ats_reviews_bodies_flavour( $flavour ) );
		$text    = $opener . ' ' . $body . '.';

		// Medium vs long.
		if ( $bucket > 70 ) {
			$text .= ' ' . ucfirst( ats_reviews_pick( ats_reviews_bodies_general() ) ) . '.';
			$text .= ' ' . ats_reviews_pick( ats_reviews_closers() );
		} elseif ( mt_rand( 0, 1 ) ) {
			$text .= ' ' . ats_reviews_pick( ats_reviews_closers() );
		}

		if ( 4 === $rating ) {
			$niggles = array(
				' Only downside was the delivery took a few days.',
				' Bit pricey but you get what you pay for.',
				' Packaging could have been a little sturdier.',
				' Would have liked a couple more in the pack.',
			);
			$text .= ats_reviews_pick( $niggles );
		}
	}

	// Weave in the product name on a share of reviews.
	if ( ( mt_rand( 1, 100 ) / 100 ) <= $config['name_mention_pct'] ) {
		$label = ats_reviews_tidy_product_label( $product->get_name() );
		if ( '' !== $label ) {
			$text = ats_reviews_end_sentence( $text );
			$tmpl = ats_reviews_pick( ats_reviews_name_templates() );
			$text .= ' ' . str_replace( '{p}', $label, $tmpl );
		}
	}

	// Light typo injection on a share of reviews.
	if ( ( mt_rand( 1, 100 ) / 100 ) <= $config['typo_pct'] ) {
		$text = ats_reviews_inject_typo( $text );
	}

	// Sloppy capitalisation on a share of reviews.
	$caps_pct = isset( $config['caps_ignore_pct'] ) ? $config['caps_ignore_pct'] : 0.3;
	if ( ( mt_rand( 1, 100 ) / 100 ) <= $caps_pct ) {
		$text = ats_reviews_mangle_caps( $text );
	}

	return trim( $text );
}
