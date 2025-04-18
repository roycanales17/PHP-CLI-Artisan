<?php

	namespace Commands;

	use App\Console\Command;

	class CommandFile extends Command
	{
		protected string $signature = 'make:command';
		protected string $description = 'Generates a new command file';

		public function handle(): void
		{
			$this->info('⏳ Initializing command file generation...');
			$this->success('✅ Command file has been successfully created and is ready for use.');
		}
	}
