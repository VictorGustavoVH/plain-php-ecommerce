<?php

/**
 * Security engine selector:
 * - auto: Ninja (only in WP context) else BitFire
 * - bitfire: force BitFire
 * - phpids: force PHPIDS (local IDS for plain PHP apps)
 * - ninja: force NinjaFirewall WP Edition (requires WordPress context)
 * - off: disable both
 */
return [
    // 'engine' => 'bitfire',
    'engine' => 'phpids',

    
    'phpids_impact_threshold' => 10,
];
