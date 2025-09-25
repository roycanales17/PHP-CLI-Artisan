<?php

	namespace App\Console;

	use Cron\CronExpression;
	use DateTime;
	use Exception;

	/**
	 * Class Schedule
	 *
	 * A lightweight task scheduler for running Artisan commands
	 * (or other PHP entry points) using cron-like expressions.
	 *
	 * Usage:
	 *  - Define the path to your Artisan file with setPath().
	 *  - Register commands with command() and chain frequency helpers.
	 *  - Call execute() to run all due commands.
	 *
	 * Example:
	 * ```php
	 * Schedule::setPath(base_path('artisan'));
	 *
	 * Schedule::command('emails:send')->everyMinute();
	 * Schedule::command('backup:run')->daily();
	 * Schedule::command('report:generate', ['--type=daily'])->at('14:30');
	 *
	 * Schedule::execute();
	 * ```
	 */
	class Schedule
	{
		/** @var string The Artisan command or script to execute. */
		private string $command;

		/** @var array The command arguments (passed as raw CLI arguments). */
		private array $args;

		/** @var string The cron expression defining when the task should run. */
		private string $expression = '* * * * *';

		/** @var Schedule[] Holds all registered schedules. */
		private static array $schedules = [];

		/** @var string Path to the PHP file that should be executed (usually artisan). */
		private static string $pathToExecute = '';

		/**
		 * Register a new command schedule.
		 *
		 * @param string $command The Artisan command name (e.g., "queue:work").
		 * @param array  $args    Optional command arguments (e.g., ["--force"]).
		 * @return self
		 */
		public static function command(string $command, array $args = []): self
		{
			$schedule = new self($command, $args);
			self::$schedules[] = $schedule;
			return $schedule;
		}

		/**
		 * Set the path to the PHP entry point (usually "artisan").
		 *
		 * @param string $pathToExecute
		 * @throws Exception If the file does not exist.
		 */
		public static function setPath(string $pathToExecute): void
		{
			if (!file_exists($pathToExecute)) {
				throw new Exception("Path to execute not found: {$pathToExecute}");
			}
			self::$pathToExecute = $pathToExecute;
		}

		/**
		 * Execute all due scheduled commands.
		 *
		 * This method should be triggered by a system cron job running every minute.
		 *
		 * Example cron entry:
		 * * * * * * cd /path/to/project && php run-scheduler.php >> /dev/null 2>&1
		 *
		 * @return void
		 */
		public static function execute(): void
		{
			$now = new DateTime();

			foreach (self::$schedules as $schedule) {
				$cron = new CronExpression($schedule->expression);

				if ($cron->isDue($now)) {
					// Escape arguments for safety
					$args = implode(' ', array_map('escapeshellarg', $schedule->args));

					$cmd = sprintf(
						'php %s %s %s > /dev/null 2>&1 &',
						escapeshellarg(self::$pathToExecute), // artisan path
						escapeshellarg($schedule->command),   // command name
						$args                                 // command args
					);

					exec($cmd);
				}
			}
		}

		/**
		 * Schedule constructor.
		 *
		 * @param string $command The command name.
		 * @param array  $args    Command arguments.
		 */
		public function __construct(string $command, array $args = [])
		{
			$this->command = $command;
			$this->args = $args;
		}

		// ─── Frequency Helpers ───────────────────────────────

		/** Run the task every minute. */
		public function everyMinute(): self { $this->expression = '* * * * *'; return $this; }

		/** Run the task every 5 minutes. */
		public function everyFiveMinutes(): self { $this->expression = '*/5 * * * *'; return $this; }

		/** Run the task every hour. */
		public function hourly(): self { $this->expression = '0 * * * *'; return $this; }

		/** Run the task daily at midnight. */
		public function daily(): self { $this->expression = '0 0 * * *'; return $this; }

		/** Run the task weekly on Sunday at midnight. */
		public function weekly(): self { $this->expression = '0 0 * * 0'; return $this; }

		/** Run the task monthly on the 1st at midnight. */
		public function monthly(): self { $this->expression = '0 0 1 * *'; return $this; }

		/** Run the task yearly on January 1st at midnight. */
		public function yearly(): self { $this->expression = '0 0 1 1 *'; return $this; }

		/**
		 * Run the task using a custom cron expression.
		 *
		 * @param string $expression
		 * @return $this
		 */
		public function cron(string $expression): self
		{
			$this->expression = $expression;
			return $this;
		}

		/**
		 * Run the task daily at a specific time.
		 *
		 * @param string $time Format "HH:MM" (24-hour).
		 * @return $this
		 */
		public function at(string $time): self
		{
			[$hour, $minute] = explode(':', $time);
			$this->expression = "{$minute} {$hour} * * *";
			return $this;
		}
	}