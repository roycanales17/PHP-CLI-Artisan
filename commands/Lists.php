<?php

	namespace Commands;

	use App\Console\Command;
	use App\Console\Terminal;

	class Lists extends Command
	{
		protected string $signature = 'lists';
		protected string $description = 'Displays all the available methods';

		public function handle(): void
		{
			$grouped = [];
			$commands = Terminal::fetchAllCommands();

			foreach ($commands as $command) {
				$signature = $command['signature'];
				$description = $command['description'];

				if ($signature == 'lists')
					continue;

				if (strpos($signature, ':') !== false) {
					[$group, $sub] = explode(':', $signature, 2);
					if ($sub) {
						$padded = str_pad($signature, 35);
						$grouped[$group][] = "{$padded}{$description}";
					}
				} else {
					$grouped[$signature] = str_pad($signature, 35) . $description;
				}
			}

			$this->info("\nAvailable Commands:", Terminal::YELLOW);

			foreach ($grouped as $group => $subCommands) {
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
