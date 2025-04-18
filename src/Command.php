<?php

	namespace App\Console;

	abstract class Command
	{
		protected function info(string $message, int $code = 0, bool $return = false): string
		{
			return Terminal::info($message, $code, $return);
		}

		protected function error(string $message): void
		{
			Terminal::error("$message");
		}

		protected function success(string $message): void
		{
			Terminal::success("$message");
		}

		protected function warn(string $message): void
		{
			Terminal::warn("$message");
		}

		protected function root(): string
		{
			return dirname(__DIR__);
		}

		protected function perform(string $command, array $args = [], bool $execute = false): void
		{
			if (!Terminal::handle($command, $args, $execute)) {
				$this->error("Command {$command} not found.");

				if (method_exists($this, 'execute')) {
					$this->execute();
				}
			}
		}

		public function getSignature(): string
		{
			return $this->signature;
		}

		public function getDescription(): string
		{
			return $this->description;
		}

		abstract public function handle(): void;
	}