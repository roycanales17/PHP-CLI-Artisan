<?php

	namespace App\Console;

	use Closure;
	use ReflectionClass;

	class Terminal
	{
		private static array $commands = [];

		public const RED = 31;
		public const GREEN = 32;
		public const YELLOW = 33;
		public const BLUE = 34;
		public const MAGENTA = 35;
		public const CYAN = 36;
		public const GRAY = 37;

		public static function config(array|string $paths): void
		{
			$root = self::getProjectRootPath();

			if (is_string($paths)) {
				$paths = [$paths];
			}

			foreach ($paths as $namespace) {

				$namespace = trim($namespace, '/');
				$directory = $root . DIRECTORY_SEPARATOR . $namespace;

				if (is_dir($directory)) {
					foreach (scandir($directory) as $file) {
						if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
							$class = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

							if (class_exists($class)) {
								$reflection = new ReflectionClass($class);
								if ($reflection->isSubclassOf(Command::class) && !$reflection->isAbstract()) {
									$obj = new $class();
									self::$commands[] = [
										'object' => $obj,
										'signature' => $obj->getSignature(),
										'description' => $obj->getDescription()
									];
								}
							}
						}
					}
				}
			}
		}

		public static function capture(array $args, bool $reset = false): void
		{


			$command = $args[1] ?? '';
			$params = array_slice($args, 2);

			if (!$reset && $command) {
				if (self::handle($command, $params))
					return;
			} else {
				if ($reset) {
					if (self::handle($command, $params, true)) {
						self::output(function($args) {
							self::capture($args, true);
						}, true);
						return;
					}
				} else {
					if (self::handle('list', $params, true)) {
						self::output(function($args) {
							self::capture($args, true);
						}, true);
						return;
					}
				}
			}

			self::error('Invalid action.');

			if ($reset) {
				self::output(function($args) {
					self::capture($args, true);
				}, true);
			}
		}

		public static function handle(string $command, array $args = [], bool $execute = false): bool
		{
			foreach (self::$commands as $attr) {
				$signature = $attr['signature'];
				$object = $attr['object'];

				if ($signature === $command && method_exists($object, 'handle')) {
					call_user_func_array([$object, 'handle'], $args);
					if ($execute) {
						if (method_exists($object, 'execute')) {
							$object->execute();
						}
					}
					return true;
				}
			}

			return false;
		}

		public static function info(string $message, int $code = 0, bool $return = false): string
		{
			if ($code < 0 || $code > 97)
				$code = 0;

			$formatted = '';
			$lines = explode("\n", $message);
			foreach ($lines as $line) {
				$formatted .= "\e[{$code}m{$line}\e[0m\n";
			}
			$formatted = rtrim($formatted, "\n");

			if ($return)
				return $formatted;

			echo "$formatted\n";
			return '';
		}

		public static function output(Closure $callback, bool $format = false): void
		{
			$input = trim(fgets(STDIN));

			if ($format) {
				$input = preg_split('/\s+/', trim($input));
				array_unshift($input, 'artisan');
			}

			if ($input)
				$callback($input);
		}

		public static function error(string $message): void
		{
			self::info("[ERROR] $message\n", self::RED);
		}

		public static function success(string $message): void
		{
			self::info("[SUCCESS] $message\n", self::GREEN);
		}

		public static function warn(string $message): void
		{
			self::info("[WARNING] $message\n", self::YELLOW);
		}

		public static function fetchAllCommands(): array
		{
			$commands = [];
			foreach (self::$commands as $command) {
				$commands[] = [
					'signature' => $command['signature'],
					'description' => $command['description']
				];
			}

			return $commands;
		}

		public static function getProjectRootPath(): string
		{
			$vendorPos = strpos(__DIR__, 'vendor');
			if ($vendorPos !== false) {
				return substr(__DIR__, 0, $vendorPos);
			}

			return dirname(__DIR__);
		}
	}