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

		protected function root(string $path): string
		{
			return dirname(__DIR__) . ( $path ? "/" . trim($path, '/') : '' );
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

		protected function create(string $filename, string $content, string $directory): bool
		{
			if (!is_dir($directory)) {
				if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
					return false;
				}
			}

			$filePath = $directory . '/' . $filename;

			if (file_exists($filePath)) {
				$this->error("File '{$filename}' already exists.");
				return false;
			}

			return file_put_contents($filePath, $content) !== false;
		}

		protected function confirm(string $message, array $opt = ['no', 'yes']): int
		{
			return Terminal::question($message, $opt);
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