<?php

	namespace App\Console\Commands;

	use App\Console\Command;

	class SendBirthdayEmails extends Command
	{
		// This is the command you run in the terminal: `php artisan emails:birthday`
		protected $signature = 'emails:birthday';

		// This is what shows in the `php artisan list`
		protected $description = 'Send birthday emails to users';

		public function handle(): int
		{
			// Your logic goes here
			\Log::info('Sending birthday emails...');
			// e.g., dispatch a job or service

			$this->info('Birthday emails sent!');
			return Command::SUCCESS;
		}
	}
