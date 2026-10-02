<?php
declare(strict_types=1);

// STUN alone (free, no signup, no account anywhere) is enough for most calls --
// it just tells each browser its own public IP/port so two peers behind
// ordinary home routers can connect directly. It isn't enough for stricter
// networks (symmetric NAT, corporate firewalls), which need a TURN server to
// relay the audio instead of connecting directly. TURN relays bandwidth, so
// free ones are rare and usually require signing up (the Open Relay Project
// at metered.ca has a real free tier, 20GB/month, as of this writing) -- not
// something to hardcode a shared secret for into a public repo. If you want
// that extra reliability, sign up and add SAD_TURN_HOST / SAD_TURN_USERNAME /
// SAD_TURN_CREDENTIAL to config.local.php; without it, calling still works,
// just with a narrower set of networks it can traverse.
function ice_servers(): array
{
    $servers = [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
    ];

    $turnHost = cfg('SAD_TURN_HOST');
    $turnUser = cfg('SAD_TURN_USERNAME');
    $turnCredential = cfg('SAD_TURN_CREDENTIAL');
    if ($turnHost && $turnUser && $turnCredential) {
        $servers[] = [
            'urls' => ["turn:{$turnHost}", "turns:{$turnHost}"],
            'username' => $turnUser,
            'credential' => $turnCredential,
        ];
    }

    return $servers;
}
