<?php

return [
    'bootstrap_secret' => env('EMPRENDIMIENTOOS_BOOTSTRAP_SECRET'),
    'invitation_expiry_days' => (int) env('ACCESS_INVITATION_EXPIRY_DAYS', 7),
];
