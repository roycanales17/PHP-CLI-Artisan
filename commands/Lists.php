<?php

	namespace Commands;

	use App\Console\Command;
	use App\Console\Terminal;

	class Lists extends Command
	{
		protected string $signature = 'list';
		protected string $description = 'Displays all the available methods';

		public function handle(): void
		{
			$grouped = [];
			$commands = Terminal::fetchAllCommands();

			foreach ($commands as $command) {
				$signature = $command['signature'];
				$description = $command['description'];

				if (strpos($signature, ':') !== false) {
					[$group, $sub] = explode(':', $signature, 2);
					if ($sub) {
						$padded = $this->info(str_pad($signature, 35), Terminal::GREEN, return: true);
						$grouped[$group][] = "{$padded}{$description}";
					}
				} else {
					$grouped["_$signature"] = str_pad($signature, 37) . $this->info($description, return: true);
				}
			}

			$this->info("\nAvailable Commands:", Terminal::YELLOW);

			$regrouped = [];
			foreach ($grouped as $group => $subCommands) {
				if (is_string($subCommands)) {
					$regrouped[$group] = $subCommands;
				}
			}

			if ($regrouped)
				$regrouped[' '] = '';

			foreach ($grouped as $group => $subCommands) {
				if (!is_string($subCommands)) {
					$regrouped[$group] = $subCommands;
				}
			}

			foreach ($regrouped as $group => $subCommands) {
				if (is_string($subCommands)) {
					$this->info("  {$subCommands}", Terminal::BLUE);
				} else {
					$this->info("  " . $group, Terminal::BLUE);
					foreach ($subCommands as $sub) {
						$this->info("    {$sub}");
					}
				}
			}

			echo "\n";
		}

		public function execute(): void
		{
			Terminal::output(function($args) {
				$args = preg_split('/\s+/', trim($args));
				$command = $args[0] ?? '';
				$params = array_slice($args, 1);

				$this->perform($command, $params, true);
			});
		}
	}
