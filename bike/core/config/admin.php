<?php

return [
    'allowed_ips' => array_filter(explode(',', env('ADMIN_ALLOWED_IPS', ''))),
];
