<?php

	namespace App\Console;

	/**
	 * Class Command
	 *
	 * An abstract base class for creating custom console commands.
	 * Provides convenience methods for styled console output,
	 * filesystem operations, user prompts, and command execution.
	 *
	 * Extend this class and implement the `handle()` method
	 * to define your own command logic.
	 *
	 * Example:
	 * ```php
	 * class SendEmails extends Command
	 * {
	 *     protected string $signature = 'emails:send';
	 *     protected string $description = 'Send queued emails to users';
	 *
	 *     public function handle(): void
	 *     {
	 *         $this->info('Starting email send process...');
	 *         // custom logic
	 *         $this->success('All emails sent successfully!');
	 *     }
	 * }
	 * ```
	 */
	abstract class Command
	{
		private string $defaultDirectoryView = 'views';

		/**
		 * Write an informational message to the console.
		 *
		 * @param string $message The message to display.
		 * @param int $code Exit code (optional).
		 * @param bool $return Return the formatted string instead of printing (optional).
		 * @return string Formatted message if $return = true.
		 */
		protected function info(string $message, int $code = 0, bool $return = false): string
		{
			return Terminal::info($message, $code, $return);
		}

		/**
		 * Write an error message to the console.
		 *
		 * @param string $message The error message.
		 * @param bool $newLine Append a newline at the end (default true).
		 * @return void
		 */
		protected function error(string $message, bool $newLine = false): void
		{
			Terminal::error($message, $newLine);
		}

		/**
		 * Write a success message to the console.
		 *
		 * @param string $message The success message.
		 * @param bool $newLine Append a newline at the end (default true).
		 * @return void
		 */
		protected function success(string $message, bool $newLine = false): void
		{
			Terminal::success($message, $newLine);
		}

		/**
		 * Write a warning message to the console.
		 *
		 * @param string $message The warning message.
		 * @param bool $newLine Append a newline at the end (default true).
		 * @return void
		 */
		protected function warn(string $message, bool $newLine = false): void
		{
			Terminal::warn($message, $newLine);
		}

		/**
		 * Get the project root path, optionally appending a relative path.
		 *
		 * @param string $path Relative path to append to the root.
		 * @return string Full resolved path.
		 */
		protected function root(string $path): string
		{
			return dirname(__DIR__) . ($path ? "/" . trim($path, '/') : '');
		}

		/**
		 * Perform another command by delegating to the Terminal handler.
		 *
		 * If the command is not found, it outputs an error and, if available,
		 * falls back to this command's `execute()` method.
		 *
		 * @param string $command The command to execute.
		 * @param array $args Arguments to pass to the command.
		 * @param bool $execute Whether to execute immediately.
		 * @return void
		 */
		protected function perform(string $command, array $args = [], bool $execute = false): void
		{
			if (!Terminal::handle($command, $args, $execute)) {
				$this->error("Command {$command} not found.");
				if (method_exists($this, 'execute')) {
					$this->execute();
				}
			}
		}

		/**
		 * Create a new file with the given content inside a directory.
		 *
		 * @param string $filename The name of the file.
		 * @param string $content The file contents.
		 * @param string $directory Target directory path.
		 * @return bool True if file created successfully, false otherwise.
		 */
		protected function create(string $filename, string $content, string $directory): bool
		{
			if (!$this->createDirectory($directory)) {
				return false;
			}

			$filename = trim($filename, '/');
			$filePath = $directory . '/' . $filename;
			if (file_exists($filePath)) {
				$this->error("File '{$filename}' already exists.");
				return false;
			}

			if (file_put_contents($filePath, $content) !== false) {
				$this->success("File '{$filename}' has been created.");
				return true;
			}

			$this->error("File '{$filename}' is not writable.");
			return false;
		}

		/**
		 * Ask the user to confirm an action with a set of options.
		 *
		 * @param string $message The question to ask.
		 * @param array $opt Available options (default: ['no', 'yes']).
		 * @return int Index of the selected option.
		 */
		protected function confirm(string $message, array $opt = ['no', 'yes']): int
		{
			return Terminal::question($message, $opt);
		}

		/**
		 * Extracts class metadata (name, namespace, directory) from a prefix and class string.
		 *
		 * @param string $class The base class name (e.g. 'UserController')
		 * @param string $prefix Path or namespace prefix (e.g. 'Admin/Controllers')
		 * @return array{class: string, namespace: string, buildNamespace: string, directory: string}
		 */
		protected function extractClassInfo(string $class, string $prefix): array
		{
			$prefix = trim($prefix, '/');
			$path = preg_replace('/[^A-Za-z0-9_\/]/', '', "{$prefix}/{$class}");
			$path = trim($path, '/');

			$segments = explode('/', $path);
			$className = ucfirst(array_pop($segments));
			$namespace = implode('\\', array_map('ucfirst', $segments));
			$directory = implode('/', $segments);

			return [
				'class' => $className,
				'namespace' => $namespace,
				'buildNamespace' => "$namespace\\$className",
				'directory' => $directory,
			];
		}

		/**
		 * Ensure a directory exists, creating it if necessary.
		 *
		 * @param string $directory The directory path to create.
		 * @return bool True if directory exists or was created successfully, false otherwise.
		 */
		protected function createDirectory(string $directory): bool
		{
			$this->info("Creating directory '{$directory}'...");
			if (!is_dir($directory)) {
				if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
					$this->error("Failed to create directory '{$directory}'");
					return false;
				}
				$this->success("Created directory: {$directory}");
			} else {
				$this->info("Directory '{$directory}' already exists.");
			}
			return true;
		}

		/**
		 * Resolve the real absolute path for a given relative path.
		 *
		 * @param string $path Relative path to resolve.
		 * @return string Absolute path.
		 */
		protected function getRealPath(string $path): string
		{
			$path = trim($path, '/');
			return realpath(__DIR__ . "/../$path");
		}

		/**
		 * Get the default directory for views.
		 *
		 * @return string Default views directory path.
		 */
		protected function getDefaultDirectoryView(): string
		{
			return trim($this->defaultDirectoryView, '/') . "/";
		}

		/**
		 * Move or copy a file from source to destination safely.
		 *
		 * @param string $source Path to source file.
		 * @param string $destination Path to destination file.
		 * @return bool True on success, false on failure.
		 */
		protected function moveFile(string $source, string $destination): bool
		{
			$this->info("Moving file '{$source}' to '{$destination}'");

			if (!file_exists($source)) {
				$this->error("Source file '{$source}' not found.");
				return false;
			}

			$destDir = dirname($destination);
			if (!$this->createDirectory($destDir)) {
				return false;
			}

			if (file_exists($destination)) {
				$this->warn("Destination file '{$destination}' already exists, skipping copy.");
				return true;
			}

			if (copy($source, $destination)) {
				$this->success("File copied successfully to: {$destination}");
				return true;
			}

			$this->error("File copy failed to: {$destination}");
			return false;
		}

		/**
		 * Recursively delete a directory and all its contents.
		 *
		 * @param string $dir Directory path to delete.
		 * @return bool True if deleted successfully or directory didn't exist, false on failure.
		 */
		protected function deleteDirectory(string $dir): bool
		{
			if (!is_dir($dir)) {
				return true;
			}

			try {
				$items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
				foreach ($items as $item) {
					$path = $item->getPathname();
					if ($item->isDir()) {
						if (!$this->deleteDirectory($path)) {
							return false;
						}
					} else {
						if (!@unlink($path)) {
							$this->error("Failed to delete file: {$path}");
							return false;
						}
						$this->info("Deleted file: {$path}");
					}
				}

				if (!@rmdir($dir)) {
					$this->error("Failed to remove directory: {$dir}");
					return false;
				}
				$this->info("Deleted directory: {$dir}");
				return true;
			} catch (\Throwable $e) {
				$this->error("⚠Error deleting directory {$dir}: " . $e->getMessage());
				return false;
			}
		}

		/**
		 * Create a symbolic link safely.
		 *
		 * @param string $target Actual directory path (source).
		 * @param string $link Symbolic link path (destination).
		 * @return bool True if link exists or was created successfully, false otherwise.
		 */
		protected function createSymlink(string $target, string $link): bool
		{
			if (is_link($link) && readlink($link) === $target) {
				$this->info("Symlink already exists: {$link}");
				return true;
			}

			if (!is_link($link)) {
				if (@symlink($target, $link)) {
					$this->info("Symbolic link created: {$link} → {$target}");
					return true;
				} else {
					$this->error("Failed to create symbolic link: {$link}");
					return false;
				}
			}

			$this->warn("Existing symlink points elsewhere: {$link}");
			return false;
		}

		/**
		 * Get the command signature (name and options).
		 *
		 * @return string
		 */
		public function getSignature(): string
		{
			return $this->signature;
		}

		/**
		 * Get the command description.
		 *
		 * @return string
		 */
		public function getDescription(): string
		{
			return $this->description;
		}

		/**
		 * Execute the command logic.
		 *
		 * Must be implemented by concrete commands.
		 *
		 * @return void
		 */
		abstract public function handle(): void;
	}