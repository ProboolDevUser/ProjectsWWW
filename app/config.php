<?php
declare(strict_types=1);

return [
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'probool_projects',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
  ],
  'app' => [
    'tz' => 'Europe/Lisbon',
    'base_url' => '/probool-projects/public',
    'uploads_dir' => __DIR__ . '/../public/uploads'
  ]
];
