<?php

declare(strict_types=1);

use Laminas\Mvc\Application;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

Application::init(require 'config/application.config.php')->run();
