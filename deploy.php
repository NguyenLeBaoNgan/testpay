<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'https://github.com/NguyenLeBaoNgan/testpay.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('103.20.96.114')
    ->set('remote_user', 'deployer')
    ->set('ssh_multiplexing', false)
    ->set('deploy_path', '~/var/www/testpay');

// Hooks

after('deploy:failed', 'deploy:unlock');
