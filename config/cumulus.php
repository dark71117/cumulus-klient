<?php

return [
    'login_ip' => filter_var(env('CUMULUS_LOGIN_IP', true), FILTER_VALIDATE_BOOLEAN),
    'save_ip' => filter_var(env('CUMULUS_SAVE_IP', true), FILTER_VALIDATE_BOOLEAN),
    'save_usage' => filter_var(env('CUMULUS_SAVE_USAGE', true), FILTER_VALIDATE_BOOLEAN),
    'meteomax_active' => filter_var(env('CUMULUS_METEOMAX_ACTIVE', true), FILTER_VALIDATE_BOOLEAN),
    'meteomax_path' => env('CUMULUS_METEOMAX_PATH', '/home/meteomax/public_html/components/com_weathermax/works'),
    'php_path' => env('CUMULUS_PHP_PATH', '/bin'),
    'web_folder' => env('CUMULUS_WEB_FOLDER'),
    'ogimet_latest_url' => env(
        'CUMULUS_OGIMET_LATEST_URL',
        'https://www.ogimet.com/ultimos_synops2.php?lang=en&estado=Pola&fmt=txt&Send=Send'
    ),
    'ogimet_range_url' => env('CUMULUS_OGIMET_RANGE_URL', 'https://www.ogimet.com/cgi-bin/getsynop'),
    'ogimet_timeout' => (int) env('CUMULUS_OGIMET_TIMEOUT', 90),
    'ogimet_schedule' => filter_var(env('CUMULUS_OGIMET_SCHEDULE', false), FILTER_VALIDATE_BOOLEAN),
    'tv_files_dir' => env('CUMULUS_TV_FILES', '/pliki/tv'),
    'tv_files' => [
        'satPhotoSD' => ['file' => 'sat_SD_', 'a' => 'Zdjęcie satelitarne: sat_SD_'],
        'satPhotoHD' => ['file' => 'sat_HD_', 'a' => 'Zdjęcie satelitarne: sat_HD_'],
        'animationSD' => ['file' => 'animacja_SD_', 'a' => 'Animacja w jakości SD_'],
        'animationHD' => ['file' => 'animacja_HD_', 'a' => 'Animacja w jakości HD_'],
        'videoTV_HD' => ['file' => 'Polska_HD_', 'a' => 'Polska_HD_', 'desc' => 'Animacja w rozdzielczości Full HD – ', 'video' => true],
        'videoTV_SD' => ['file' => 'Polska_SD_', 'a' => 'Polska_SD_', 'desc' => 'Animacja w rozdzielczości SD – ', 'video' => true],
    ],
];
