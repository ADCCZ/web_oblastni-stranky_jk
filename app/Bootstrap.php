<?php

declare(strict_types=1);

namespace App;

use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	private readonly Configurator $configurator;
	private readonly string $rootDir;


	public function __construct()
	{
		$this->rootDir = dirname(__DIR__);
		$this->configurator = new Configurator;
		$this->configurator->setTempDirectory($this->rootDir . '/temp');
	}


	public function bootWebApplication(): Nette\DI\Container
	{
		$this->initializeEnvironment();
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function initializeEnvironment(): void
	{
		//$this->configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
		$this->configurator->enableTracy($this->rootDir . '/log');

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
	}


	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addStaticParameters([
			'env' => $this->readEnvParameters(),
		]);
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');
		if (file_exists($configDir . '/local.neon')) {
			$this->configurator->addConfig($configDir . '/local.neon');
		}
	}


	/**
	 * Reads deployment configuration (database, mail, OAuth) from environment variables,
	 * so secrets never need to live in a committed file. Used by Docker Compose (.env)
	 * and Railway (dashboard variables). Falls back to sensible local-dev defaults.
	 */
	private function readEnvParameters(): array
	{
		$env = static fn (string $name, string $default = '') => getenv($name) !== false ? getenv($name) : $default;

		$dbHost = $env('DB_HOST', '127.0.0.1');
		$dbPort = $env('DB_PORT', '3306');
		$dbName = $env('DB_DATABASE', 'pathfinder_jk');

		return [
			'database' => [
				'dsn' => $env('DB_DSN', "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4"),
				'user' => $env('DB_USERNAME', 'root'),
				'password' => $env('DB_PASSWORD', ''),
			],
			'mail' => [
				'smtp' => filter_var($env('MAIL_SMTP', 'false'), FILTER_VALIDATE_BOOL),
				'host' => $env('MAIL_HOST', 'localhost'),
				'port' => (int) $env('MAIL_PORT', '25'),
				'username' => $env('MAIL_USERNAME'),
				'password' => $env('MAIL_PASSWORD'),
				'secure' => $env('MAIL_SECURE') !== '' ? $env('MAIL_SECURE') : null,
			],
			'oauth' => [
				'google' => [
					'clientId' => $env('OAUTH_GOOGLE_ID'),
					'clientSecret' => $env('OAUTH_GOOGLE_SECRET'),
				],
				'facebook' => [
					'clientId' => $env('OAUTH_FACEBOOK_ID'),
					'clientSecret' => $env('OAUTH_FACEBOOK_SECRET'),
					'graphApiVersion' => $env('OAUTH_FACEBOOK_GRAPH_VERSION', 'v24.0'),
				],
				'discord' => [
					'clientId' => $env('OAUTH_DISCORD_ID'),
					'clientSecret' => $env('OAUTH_DISCORD_SECRET'),
				],
			],
		];
	}
}
