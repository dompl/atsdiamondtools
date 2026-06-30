<?php
/**
 * Review Generator — Identity
 *
 * Synthetic reviewer names + emails. UK-weighted, mixed male/female, with a
 * small share of foreign-sounding names. Output never reuses a real customer
 * address and never repeats the same name on one product.
 *
 * @package skylinewp-dev-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Male first names (UK-common).
 *
 * @return array
 */
function ats_reviews_first_names_male() {
	return array(
		'James', 'John', 'David', 'Paul', 'Mark', 'Andrew', 'Steve', 'Steven', 'Gary',
		'Lee', 'Craig', 'Darren', 'Wayne', 'Neil', 'Ian', 'Chris', 'Kevin', 'Dean',
		'Tom', 'Tommy', 'Jack', 'Harry', 'George', 'Charlie', 'Ben', 'Sam', 'Joe',
		'Daniel', 'Matt', 'Matthew', 'Rob', 'Robert', 'Richard', 'Phil', 'Simon',
		'Nathan', 'Ryan', 'Carl', 'Tony', 'Alan', 'Keith', 'Barry', 'Graham', 'Stuart',
		'Scott', 'Adam', 'Luke', 'Liam', 'Callum', 'Jordan', 'Dave', 'Mike',
	);
}

/**
 * Female first names (UK-common).
 *
 * @return array
 */
function ats_reviews_first_names_female() {
	return array(
		'Sarah', 'Emma', 'Claire', 'Lisa', 'Joanne', 'Nicola', 'Rachel', 'Sam',
		'Kelly', 'Donna', 'Lucy', 'Hannah', 'Charlotte', 'Sophie', 'Amy', 'Rebecca',
		'Laura', 'Katie', 'Jessica', 'Megan', 'Chloe', 'Holly', 'Gemma', 'Hayley',
		'Kerry', 'Tracy', 'Sharon', 'Karen', 'Julie', 'Sue', 'Susan', 'Helen',
		'Joanna', 'Michelle', 'Andrea', 'Paula', 'Dawn', 'Jade', 'Leanne', 'Stacey',
		'Abbie', 'Ellie', 'Grace', 'Olivia', 'Jenny', 'Beth', 'Natalie',
	);
}

/**
 * Foreign-sounding first names (small share).
 *
 * @return array
 */
function ats_reviews_first_names_foreign() {
	return array(
		'Tomasz', 'Mateusz', 'Piotr', 'Kasia', 'Andrzej', 'Marek', 'Dimitri',
		'Sergei', 'Oleksandr', 'Andrei', 'Mihai', 'Stefan', 'Lukas', 'Janusz',
		'Hassan', 'Amir', 'Omar', 'Raj', 'Sanjay', 'Arjun', 'Chen', 'Wei',
		'Joao', 'Diogo', 'Marco', 'Luca', 'Pierre', 'Henrik', 'Bjorn',
	);
}

/**
 * Surnames (UK-common, plus a few foreign for the foreign branch).
 *
 * @param bool $foreign Whether to return a foreign-leaning surname pool.
 * @return array
 */
function ats_reviews_surnames( $foreign = false ) {
	if ( $foreign ) {
		return array(
			'Kowalski', 'Nowak', 'Wojcik', 'Kaminski', 'Petrov', 'Ivanov', 'Popescu',
			'Novak', 'Horvath', 'Kovac', 'Schmidt', 'Muller', 'Silva', 'Santos',
			'Rossi', 'Ferrari', 'Patel', 'Singh', 'Khan', 'Ahmed', 'Nguyen',
		);
	}
	return array(
		'Smith', 'Jones', 'Taylor', 'Brown', 'Williams', 'Wilson', 'Johnson',
		'Davies', 'Robinson', 'Wright', 'Thompson', 'Evans', 'Walker', 'White',
		'Roberts', 'Green', 'Hall', 'Wood', 'Jackson', 'Clarke', 'Clark', 'Turner',
		'Hill', 'Harris', 'Cooper', 'Ward', 'Morris', 'Moore', 'King', 'Baker',
		'Hughes', 'Edwards', 'Bell', 'Murphy', 'Bailey', 'Cox', 'Richardson',
		'Mitchell', 'Marshall', 'Shaw', 'Holmes', 'Webb', 'Gibson', 'Fox', 'Knight',
	);
}

/**
 * Email providers (UK-leaning).
 *
 * @return array
 */
function ats_reviews_email_domains() {
	return array(
		'gmail.com', 'gmail.com', 'gmail.com', 'hotmail.co.uk', 'hotmail.com',
		'outlook.com', 'yahoo.co.uk', 'btinternet.com', 'sky.com', 'icloud.com',
		'live.co.uk',
	);
}

/**
 * Generate one reviewer identity.
 *
 * @param array $config ats_reviews_config().
 * @return array { name, email, display } where display is "First L." / "First Last".
 */
function ats_reviews_generate_identity( array $config ) {
	$foreign = ( mt_rand( 1, 1000 ) / 1000 ) <= $config['foreign_pct'];

	$male_pct = isset( $config['male_pct'] ) ? $config['male_pct'] : 0.8;

	if ( $foreign ) {
		$first   = ats_reviews_pick( ats_reviews_first_names_foreign() );
		$surname = ats_reviews_pick( ats_reviews_surnames( true ) );
	} elseif ( ( mt_rand( 1, 100 ) / 100 ) <= $male_pct ) {
		$first   = ats_reviews_pick( ats_reviews_first_names_male() );
		$surname = ats_reviews_pick( ats_reviews_surnames() );
	} else {
		$first   = ats_reviews_pick( ats_reviews_first_names_female() );
		$surname = ats_reviews_pick( ats_reviews_surnames() );
	}

	// Display name in a wide mix of human formats — see ats_reviews_format_display.
	$display = ats_reviews_format_display( $first, $surname );

	// Synthetic email — varied local-part shapes.
	$seps  = array( '.', '_', '', '' );
	$local = strtolower( $first . ats_reviews_pick( $seps ) . $surname );
	if ( mt_rand( 0, 1 ) ) {
		$local .= mt_rand( 1, 99 );
	}
	$local = preg_replace( '/[^a-z0-9._]/', '', $local );
	$email = $local . '@' . ats_reviews_pick( ats_reviews_email_domains() );

	return array(
		'name'    => $first . ' ' . $surname,
		'display' => $display,
		'email'   => $email,
	);
}

/**
 * Format a display name in one of many human shapes — full names look "too
 * good", so most reviewers show as just a first name, an initial, a lowercase
 * scrawl, or a username-style handle.
 *
 * @param string $first   First name.
 * @param string $surname Surname.
 * @return string
 */
function ats_reviews_format_display( $first, $surname ) {
	$fi = substr( $first, 0, 1 );
	$si = substr( $surname, 0, 1 );
	$r  = mt_rand( 1, 100 );

	if ( $r <= 14 ) {
		// Nickname, sometimes with a surname initial, sometimes lowercase.
		$nick = ats_reviews_pick( ats_reviews_nicknames() );
		if ( mt_rand( 1, 100 ) <= 25 ) {
			$nick = strtolower( $nick );
		}
		return ( mt_rand( 0, 1 ) ) ? $nick : $nick . ' ' . $si;
	}
	if ( $r <= 26 ) {
		return $first . ' ' . $si . '.';                 // First L.
	}
	if ( $r <= 34 ) {
		return $first . ' ' . $surname;                  // First Last
	}
	if ( $r <= 50 ) {
		return $first;                                   // First
	}
	if ( $r <= 60 ) {
		return strtolower( $first );                     // first
	}
	if ( $r <= 67 ) {
		return $first . ' ' . $si;                       // First S
	}
	if ( $r <= 74 ) {
		return mt_rand( 0, 1 ) ? $fi : $fi . '.';        // J  or  J.
	}
	if ( $r <= 80 ) {
		return $fi . '.' . $si . '.';                    // J.S.
	}
	if ( $r <= 88 ) {
		return strtolower( $first ) . ' ' . strtolower( $si ); // first s
	}
	return ats_reviews_handle( $first );                 // username / random word
}

/**
 * UK-style nicknames.
 *
 * @return array
 */
function ats_reviews_nicknames() {
	return array(
		'Gaz', 'Baz', 'Daz', 'Del', 'Tel', 'Macca', 'Robbo', 'Smudge', 'Chalky',
		'Nobby', 'Budgie', 'Sparky', 'Bucko', 'Jonno', 'Stevo', 'Az', 'Kez', 'Loz',
		'Shaz', 'Bazza', 'Gazza', 'Tonka', 'Digger', 'Tommo', 'Wozza', 'Jonesy',
		'Tank', 'Bruno', 'Chappy', 'H', 'Moose', 'Rocky', 'Ginge', 'Taz', 'Benno',
	);
}

/**
 * A username-style handle or random word.
 *
 * @param string $first First name (sometimes embedded).
 * @return string
 */
function ats_reviews_handle( $first ) {
	$words = array(
		'tools', 'blade', 'grinder', 'diy', 'builder', 'tiler', 'sparky', 'chippy',
		'site', 'pro', 'works', 'trade', 'workshop', 'garage', 'speedy', 'topcat',
		'jobber', 'fixit', 'handy', 'dusty', 'sharp', 'cutter', 'stone', 'tile',
		'brick', 'mason', 'craft', 'geezer', 'lad', 'gaffer',
	);
	$f = strtolower( $first );

	switch ( mt_rand( 1, 6 ) ) {
		case 1:
			return ats_reviews_pick( $words ) . mt_rand( 1, 99 );
		case 2:
			return $f . ats_reviews_pick( $words );
		case 3:
			return ats_reviews_pick( $words ) . '_' . ats_reviews_pick( $words );
		case 4:
			return $f . mt_rand( 60, 99 );
		case 5:
			return ats_reviews_pick( $words );
		default:
			return $f . '_' . ats_reviews_pick( $words );
	}
}

/**
 * Pick a random element from an array.
 *
 * @param array $arr Source.
 * @return mixed
 */
function ats_reviews_pick( array $arr ) {
	return $arr[ array_rand( $arr ) ];
}
