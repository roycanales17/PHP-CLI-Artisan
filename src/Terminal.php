<?php

	namespace App\Console;

	use Closure;
	use ReflectionClass;

	class Terminal
	{
		private static array $commands = [];
		private static bool $configured = false;
		private static array $logs = [];

		public const RED = 31;
		public const GREEN = 32;
		public const YELLOW = 33;
		public const BLUE = 34;
		public const MAGENTA = 35;
		public const CYAN = 36;
		public const GRAY = 37;

		public static function config(array|string $paths, string $root = ''): void
		{
			if (!$root)
				$root = dirname(__DIR__);

			if (is_string($paths)) {
				$paths = [$paths];
			}

			foreach ($paths as $namespace) {

				$namespace = trim($namespace, '/');
				$directory = $root . DIRECTORY_SEPARATOR . $namespace;

				if (is_dir($directory)) {
					foreach (scandir($directory) as $file) {
						if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
							require_once "$directory/$file";
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
			self::setupDefaultCommands();

			$command = $args[1] ?? '';
			$params = array_slice($args, 2);

			if (!$reset && $command) {
				if (self::handle($command, $params))
					return;
			} else {
				if ($reset) {
					if (self::handle($command, $params, true)) {
						self::input(function($args) {
							self::capture($args, true);
						}, true);
						return;
					}
				} else {
					if (self::handle('list', $params, true)) {
						self::input(function($args) {
							self::capture($args, true);
						}, true);
						return;
					}
				}
			}

			self::error('Invalid action.');

			if ($reset) {
				self::input(function($args) {
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

		public static function input(Closure $callback, bool $format = false): void
		{
			$input = '';
			$historyIndex = null;
			$cursorPosition = 0;
			$logs = self::$logs ?? [];

			system('stty -icanon -echo');

			while (true) {
				$char = fgetc(STDIN);

				if ($char === "\033") {
					$char2 = fgetc(STDIN);
					$char3 = fgetc(STDIN);
					$seq = $char . $char2 . $char3;

					switch ($seq) {
						case "\033[A": // Arrow Up
							if (!empty($logs)) {
								if ($historyIndex === null) {
									$historyIndex = count($logs) - 1;
								} elseif ($historyIndex > 0) {
									$historyIndex--;
								}

								echo "\r\033[K";
								$input = $logs[$historyIndex];
								$cursorPosition = strlen($input);
								echo $input;
							}
							break;

						case "\033[B": // Arrow Down
							if (!empty($logs) && $historyIndex !== null) {
								if ($historyIndex < count($logs) - 1) {
									$historyIndex++;
									$input = $logs[$historyIndex];
								} else {
									$historyIndex = null;
									$input = '';
								}

								echo "\r\033[K";
								$cursorPosition = strlen($input);
								echo $input;
							}
							break;

						case "\033[C": // Arrow Right
							if ($cursorPosition < strlen($input)) {
								echo "\033[1C";
								$cursorPosition++;
							}
							break;

						case "\033[D": // Arrow Left
							if ($cursorPosition > 0) {
								echo "\033[1D";
								$cursorPosition--;
							}
							break;

						default:
							echo "\nUnknown sequence: " . bin2hex($seq) . "\n";
					}
				} elseif ($char === "\n") { // Enter key
					echo "\n";
					break;
				} elseif (ord($char) === 127) { // Backspace/Delete key
					if (strlen($input) > 0 && $cursorPosition > 0) {
						$cursorPosition--;
						$input = substr($input, 0, -1);
						echo "\033[1D \033[1D";
					}
				} else { // Regular characters
					$input .= $char;
					$cursorPosition++;
					echo $char;
				}
			}

			system('stty icanon echo');
			self::$logs[] = $input;

			if ($format) {
				preg_match_all('/("[^"]*"|\'[^\']*\'|\S+)/', trim($input), $matches);
				$input = array_map(fn($v) => trim($v, '\'"'), $matches[0]);
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

		public static function question(string $message, array $options = ['no', 'yes']): int {
			echo $message . PHP_EOL . PHP_EOL;

			foreach ($options as $index => $option) {
				echo "  [$index] $option" . PHP_EOL;
			}

			echo PHP_EOL . "Select an option (0-" . (count($options) - 1) . "): ";

			while (true) {
				$input = trim(fgets(STDIN));

				if (is_numeric($input) && isset($options[(int)$input])) {
					return (int)$input;
				}

				echo "Invalid selection. Try again: ";
			}
		}

		private static function setupDefaultCommands(): void
		{
			if (!self::$configured) {
				self::config('commands');
				self::$configured = true;
			}
		}
	}